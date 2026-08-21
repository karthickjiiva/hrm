<template>
  <div style="padding: 24px; background: #f0f2f5; min-height: 100vh;">
    <a-card title="Gratuity Form Generation" :bordered="false" style="margin-bottom: 24px">
      <a-row :gutter="24" type="flex" align="bottom">
        <a-col :xs="24" :sm="12" :md="8">
          <label class="field-label">Select Employee (Resigned)</label>
          <a-select
            v-model:value="filters.employee_id"
            style="width: 100%"
            size="large"
            show-search
            allow-clear
            placeholder="Select resigned employee"
            optionFilterProp="title"
            :loading="employeesLoading"
            :not-found-content="employeesLoading ? undefined : 'No resigned employees found'"
          >
            <a-select-option
              v-for="emp in employees"
              :key="emp.xid"
              :value="emp.xid"
              :title="`${emp.name} ${emp.employee_number || ''}`"
            >
              {{ emp.name }}<span v-if="emp.employee_number" class="emp-no">({{ emp.employee_number }})</span>
            </a-select-option>
          </a-select>
        </a-col>
        <a-col :xs="24" :sm="8" :md="6">
          <a-button type="primary" :loading="generating" @click="handleGenerate" block size="large">
            <template #icon><DownloadOutlined /></template>
            Generate Gratuity Form
          </a-button>
        </a-col>
        <a-col v-if="selectedEmployee" :xs="24" :md="10" class="emp-meta">
          <span>Joined: <strong>{{ formatDate(selectedEmployee.joining_date) }}</strong></span>
          <span>Resigned: <strong>{{ formatDate(selectedEmployee.resignation_date) }}</strong></span>
        </a-col>
      </a-row>
    </a-card>

    <a-card title="Generation History" :bordered="false">
      <a-table
        :columns="columns"
        :data-source="history"
        :loading="loading"
        row-key="xid"
        :pagination="pagination"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'employee'">
            <span style="font-weight: 600; color: #1890ff;">
              {{ record.employee?.name }}
            </span>
            <span v-if="record.employee?.employee_number" class="emp-no">({{ record.employee.employee_number }})</span>
          </template>

          <template v-if="column.key === 'date_of_joining'">
            {{ formatDate(record.date_of_joining) }}
          </template>

          <template v-if="column.key === 'exit_date'">
            {{ formatDate(record.exit_date) }}
          </template>

          <template v-if="column.key === 'created_at'">
            {{ formatDate(record.created_at) }}
          </template>

          <template v-if="column.key === 'action'">
            <a-space>
              <a-button
                type="primary"
                :loading="downloadingIds.includes(record.xid)"
                @click="handleDownload(record)"
              >
                <template #icon><DownloadOutlined /></template>
                Download
              </a-button>

              <a-popconfirm title="Delete this gratuity form?" @confirm="handleDelete(record)">
                <a-button type="primary" danger size="small">
                  <template #icon><DeleteOutlined /></template>
                </a-button>
              </a-popconfirm>
            </a-space>
          </template>
        </template>
      </a-table>
    </a-card>
  </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import { message } from 'ant-design-vue';
import { DownloadOutlined, DeleteOutlined } from '@ant-design/icons-vue';
import axios from 'axios';
import dayjs from 'dayjs';

const API = '/api/v1/gratuity-forms';

// --- State ---
const loading = ref(false);
const generating = ref(false);
const employeesLoading = ref(false);
const employees = ref([]);
const history = ref([]);
const downloadingIds = ref([]);

const filters = reactive({
  employee_id: null,
});

const selectedEmployee = computed(() =>
  employees.value.find((e) => e.xid === filters.employee_id) || null
);

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0,
});

const columns = [
  { title: 'Employee', key: 'employee' },
  { title: 'Joining Date', key: 'date_of_joining' },
  { title: 'Exit Date', key: 'exit_date' },
  { title: 'Generated On', key: 'created_at' },
  { title: 'Action', key: 'action', width: '220px' },
];

// --- Methods ---

const fetchEmployees = async () => {
  employeesLoading.value = true;
  try {
    const res = await axios.get(`${API}/resigned-employees`);
    employees.value = res.data.data || [];
  } catch (err) {
    message.error("Could not load resigned employees.");
  } finally {
    employeesLoading.value = false;
  }
};

const fetchHistory = async () => {
  loading.value = true;
  try {
    const res = await axios.get(`${API}/history`, {
      params: {
        page: pagination.current,
        limit: pagination.pageSize,
      },
    });
    history.value = res.data.data;
    pagination.total = res.data.total;
  } catch (err) {
    message.error("Could not load generation history.");
  } finally {
    loading.value = false;
  }
};

const downloadFile = async (record) => {
  const res = await axios.get(`${API}/${record.xid}/download`, { responseType: 'blob' });
  const blob = new Blob([res.data], { type: 'application/pdf' });
  const url = window.URL.createObjectURL(blob);
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', record.filename);
  document.body.appendChild(link);
  link.click();
  window.URL.revokeObjectURL(url);
  document.body.removeChild(link);
};

const handleGenerate = async () => {
  if (!filters.employee_id) {
    message.error("Please select an employee.");
    return;
  }
  generating.value = true;
  try {
    const res = await axios.post(`${API}/generate`, { employee_id: filters.employee_id });
    await downloadFile(res.data.data);
    message.success(res.data.message || "Gratuity form generated successfully.");
    filters.employee_id = null;
    pagination.current = 1;
    fetchHistory(); // Refresh the list
  } catch (err) {
    message.error(err.response?.data?.message || "Failed to generate gratuity form.");
  } finally {
    generating.value = false;
  }
};

const handleDownload = async (record) => {
  downloadingIds.value.push(record.xid);
  try {
    await downloadFile(record);
    message.success("Gratuity form downloaded successfully.");
  } catch (err) {
    message.error("Failed to download gratuity form.");
  } finally {
    downloadingIds.value = downloadingIds.value.filter((x) => x !== record.xid);
  }
};

const handleDelete = async (record) => {
  try {
    await axios.delete(`${API}/${record.xid}/remove`);
    message.success("Gratuity form deleted successfully.");
    if (history.value.length === 1 && pagination.current > 1) {
      pagination.current -= 1;
    }
    fetchHistory();
  } catch (err) {
    message.error("Failed to delete the gratuity form.");
  }
};

const handleTableChange = (pag) => {
  pagination.current = pag.current;
  fetchHistory();
};

const formatDate = (date) => {
  return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

onMounted(() => {
  fetchEmployees();
  fetchHistory();
});
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #555;
}

.emp-no {
  color: #8c8c8c;
  margin-left: 4px;
}

.emp-meta {
  display: flex;
  gap: 24px;
  padding-bottom: 10px;
  color: #555;
}

:deep(.ant-card-head-title) {
  font-weight: bold;
  font-size: 18px;
}
</style>

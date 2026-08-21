<template>
  <div style="padding: 24px; background: #f0f2f5; min-height: 100vh;">
    <a-card title="ESI Report Generation" :bordered="false" style="margin-bottom: 24px">
      <a-row :gutter="24" type="flex" align="bottom">
        <a-col :xs="24" :sm="8" :md="6">
          <label class="field-label">Select Month</label>
          <a-select v-model:value="filters.month" style="width: 100%" size="large">
            <a-select-option v-for="m in months" :key="m.value" :value="m.value">
              {{ m.label }}
            </a-select-option>
          </a-select>
        </a-col>
        <a-col :xs="24" :sm="8" :md="6">
          <label class="field-label">Select Year</label>
          <a-select v-model:value="filters.year" style="width: 100%" size="large">
            <a-select-option v-for="y in yearRange" :key="y" :value="y">{{ y }}</a-select-option>
          </a-select>
        </a-col>
        <a-col :xs="24" :sm="8" :md="6">
          <a-button type="primary" :loading="generating" @click="handleGenerate" block size="large">
            <template #icon><DownloadOutlined /></template>
            Generate ESI Report
          </a-button>
        </a-col>
      </a-row>
    </a-card>

    <a-card title="Generation History" :bordered="false">
      <a-table 
        :columns="columns" 
        :data-source="history" 
        :loading="loading" 
        row-key="id"
        :pagination="pagination"
        @change="handleTableChange"
      >
        <template #bodyCell="{ column, record }">
          <template v-if="column.key === 'report_period'">
            <span style="font-weight: 600; color: #1890ff;">
              {{ getMonthLabel(record.month) }} {{ record.year }}
            </span>
          </template>

          <template v-if="column.key === 'created_at'">
            {{ formatDate(record.created_at) }}
          </template>

          <template v-if="column.key === 'action'">
            <a-space>
                 <a-button type="primary" :href="record.full_url" target="_blank">
                        <template #icon><DownloadOutlined /></template>
                        Download
                    </a-button>
             
              <!-- <a-popconfirm title="Delete this report?" @confirm="handleDelete(record.id)">
                <a-button type="primary" danger size="small">
                  <template #icon><DeleteOutlined /></template>
                </a-button>
              </a-popconfirm> -->
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

// --- State ---
const loading = ref(false);
const generating = ref(false);
const history = ref([]);

// Dynamic Year Range: 2 years back and 2 years forward
const currentYear = new Date().getFullYear();
const yearRange = computed(() => {
  const years = [];
  for (let i = currentYear - 2; i <= currentYear + 2; i++) {
    years.push(i);
  }
  return years;
});

const filters = reactive({
  month: new Date().getMonth() + 1,
  year: currentYear,
});

const pagination = reactive({
  current: 1,
  pageSize: 10,
  total: 0
});

const months = [
  { value: 1, label: 'January' }, { value: 2, label: 'February' },
  { value: 3, label: 'March' }, { value: 4, label: 'April' },
  { value: 5, label: 'May' }, { value: 6, label: 'June' },
  { value: 7, label: 'July' }, { value: 8, label: 'August' },
  { value: 9, label: 'September' }, { value: 10, label: 'October' },
  { value: 11, label: 'November' }, { value: 12, label: 'December' }
];

const columns = [
  { title: 'Report Period', key: 'report_period' },
  { title: 'File Name', dataIndex: 'filename', key: 'filename' },
  { title: 'Generated On', dataIndex: 'created_at', key: 'created_at' },
  { title: 'Action', key: 'action', width: '220px' },
];

// --- Methods ---

const fetchHistory = async () => {
  loading.value = true;
  try {
    const res = await axios.get('/api/v1/reports/history/esi', {
      params: { 
        page: pagination.current, 
        limit: pagination.pageSize 
      }
    });
    // Adjust these based on your API response structure
    history.value = res.data.data;
    pagination.total = res.data.total;
  } catch (err) {
    message.error("Could not load generation history.");
  } finally {
    loading.value = false;
  }
};

const handleGenerate = async () => {
  generating.value = true;
  try {
    const res = await axios.post('/api/v1/reports/generate/office_esi', filters);
    message.success(res.data.message);
    fetchHistory(); // Refresh the list
  } catch (err) {
    message.error(err.response?.data?.message || "Failed to generate report.");
  } finally {
    generating.value = false;
  }
};

const handleDelete = async (id) => {
  try {
    await axios.delete(`/api/v1/reports/history/${id}`);
    message.success("Report record deleted successfully.");
    fetchHistory();
  } catch (err) {
    message.error("Failed to delete the report.");
  }
};

const handleTableChange = (pag) => {
  pagination.current = pag.current;
  fetchHistory();
};

const getMonthLabel = (m) => {
  return months.find(item => item.value === parseInt(m))?.label || m;
};

const formatDate = (date) => {
  return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

onMounted(fetchHistory);
</script>

<style scoped>
.field-label {
  display: block;
  margin-bottom: 8px;
  font-weight: 600;
  color: #555;
}

:deep(.ant-card-head-title) {
  font-weight: bold;
  font-size: 18px;
}
</style>
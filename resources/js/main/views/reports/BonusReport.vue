<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Annual Bonus Report" class="p-0" />
    </template>

    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t("menu.dashboard") }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>reports</a-breadcrumb-item>
        <a-breadcrumb-item>Annual Bonus Report</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <admin-page-filters>
    <a-row :gutter="[16, 16]" align="middle">
      <a-col :span="24">
        <a-space>
          <span style="font-weight: 500">Select Year:</span>

          <a-select v-model:value="filters.year" style="width: 150px">
            <a-select-option
              v-for="year in yearOptions"
              :key="year"
              :value="year"
            >
              {{ year }}
            </a-select-option>
          </a-select>

          <a-button type="primary" :loading="generating" @click="confirmGenerate">
            <template #icon><FileAddOutlined /></template>
            Generate Bonus Report
          </a-button>
        </a-space>
      </a-col>
    </a-row>
  </admin-page-filters>

  <admin-page-table-content>
    <a-table
      :columns="columns"
      :data-source="history"
      :loading="loading"
      :pagination="pagination"
      @change="handleTableChange"
      bordered
      row-key="id"
    >
      <template #bodyCell="{ column, record }">
        <template v-if="column.dataIndex === 'created_at'">
          {{ record.created_at_formatted }}
        </template>

        <template v-if="column.dataIndex === 'action'">
          <a-button type="primary" @click="downloadReport(record)">
            <template #icon><DownloadOutlined /></template>
            Download
          </a-button>
        </template>
      </template>
    </a-table>
  </admin-page-table-content>
</template>

<script>
import { ref, reactive, onMounted, computed, createVNode } from "vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { DownloadOutlined, FileAddOutlined, ExclamationCircleOutlined } from "@ant-design/icons-vue";
import { Modal, message } from "ant-design-vue";
import axios from "axios";

export default {
  components: {
    AdminPageHeader,      // ✅ THIS WAS MISSING
    DownloadOutlined,
    FileAddOutlined
  },
  setup() {
    const loading = ref(false);
    const generating = ref(false);
    const history = ref([]);
    const filters = reactive({ year: new Date().getFullYear() });
    const pagination = reactive({ current: 1, pageSize: 10, total: 0 });

    const columns = [
      { title: "Report Year", dataIndex: "year" },
      { title: "Filename", dataIndex: "filename" },
      { title: "Generated On", dataIndex: "created_at" },
      { title: "Action", dataIndex: "action", width: "180px" }
    ];

    const yearOptions = computed(() => {
      const current = new Date().getFullYear();
      return Array.from({ length: 5 }, (_, i) => current - i);
    });

    const formatDate = (date) => {
  return date ? dayjs(date).format('DD MMM YYYY') : '-';
};

    const fetchHistory = async () => {
      loading.value = true;
      try {
        const res = await axios.get(
          "/api/v1/reports/history/bonus",
          { params: { page: pagination.current, limit: pagination.pageSize } }
        );
        history.value = res.data.data;
        pagination.total = res.data.total;
      } catch (err) {
        message.error("Failed to load history");
      } finally {
        loading.value = false;
      }
    };

    const confirmGenerate = () => {
      Modal.confirm({
        title: `Generate Bonus Report for ${filters.year}?`,
        icon: createVNode(ExclamationCircleOutlined),
        content: "Are you sure you want to generate this report?",
        okText: "Generate",
        async onOk() {
          await generateReport();
        }
      });
    };

    const generateReport = async () => {
      generating.value = true;
      try {
        const res = await axios.post(
          "/api/v1/reports/generate/bonus",
          { year: filters.year }
        );
        message.success(res.data.message);
        fetchHistory();
      } catch (err) {
        message.error("Generation failed");
      } finally {
        generating.value = false;
      }
    };

    const downloadReport = (record) => {
      const link = document.createElement("a");
      link.href = record.full_url;
      link.setAttribute("download", record.filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
    };

    const handleTableChange = (pager) => {
      pagination.current = pager.current;
      fetchHistory();
    };

    onMounted(fetchHistory);

    return {
      filters,
      yearOptions,
      history,
      loading,
      generating,
      columns,
      pagination,
      handleTableChange,
      confirmGenerate,
      downloadReport
    };
  }
};
</script>

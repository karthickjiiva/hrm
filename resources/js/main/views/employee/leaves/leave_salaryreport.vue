<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Leave Salary Report" class="p-0" />
    </template>
    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t("menu.dashboard") }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>reports</a-breadcrumb-item>
        <a-breadcrumb-item>Leave Salary Report</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

     <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="12" :xl="8">
                        <a-space>
                            <a-select
          v-model:value="filters.month"
          placeholder="Select Month"
          @change="fetchSalaryLeaves"
            style="width: 150px"
        >
          <a-select-option v-for="m in 12" :key="m" :value="m">
            {{ new Date(0, m - 1).toLocaleString("default", { month: "long" }) }}
          </a-select-option>
        </a-select>

                            <!-- Year Selector (last 5 years) -->
                            <a-select style="width: 100px" v-model:value="filters.year" placeholder="Select year"
                                @change="fetchSalaryLeaves">
                                <a-select-option v-for="year in Array.from(
                                    { length: 5 },
                                    (_, i) => new Date().getFullYear() - i
                                )" :key="year" :value="year">
                                    {{ year }}
                                </a-select-option>
                            </a-select>
                        </a-space>
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

  <admin-page-table-content>
    <a-row>
      <a-col :span="24">
        <div class="table-responsive">
            <a-table
  :columns="columns"
  :row-key="(record) => record.month + '-' + record.year"
  :data-source="salaryLeaves"
  :pagination="pagination"
  :loading="loading"
  @change="handleTableChange"
  bordered
  size="middle"
>
  <template #bodyCell="{ column, record }">
    <!-- Month -->
    <template v-if="column.dataIndex === 'month'">
      {{ record.month_name }}
    </template>

    <!-- Year -->
    <template v-if="column.dataIndex === 'year'">
      {{ record.year }}
    </template>

    <!-- Action -->
    <template v-if="column.dataIndex === 'action'">
      <a-button type="primary" @click="downloadLeaveSummary(record)">
        {{ $t("common.download") }}
      </a-button>
    </template>
  </template>
</a-table>
        </div>
      </a-col>
    </a-row>
  </admin-page-table-content>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { message } from "ant-design-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import crud from "@/common/composable/crud";

export default {
  components: { AdminPageHeader },
 setup() {
  const { t } = useI18n();
  const loading = ref(false);
  const salaryLeaves = ref([]);
  const pagination = reactive({ current: 1, pageSize: 10, total: 0 });

  const filters = reactive({
    month: new Date().getMonth() + 1,
    year: new Date().getFullYear(),
  });

  const columns = [
    { title: t("Month"), dataIndex: "month" },
    { title: t("Year"), dataIndex: "year" },
    { title: t("common.action"), dataIndex: "action" },
  ];

 // inside setup()
const fetchSalaryLeaves = async () => {
  try {
    loading.value = true;
    const token = localStorage.getItem("auth_token");

    const params = {
      month: filters.month,
      year: filters.year,
      page: pagination.current,
      limit: pagination.pageSize,
      fields: "id,month,year", // adjust if needed
    };

    const response = await axios.get("/api/v1/salary_leave", {
      params,
      headers: { Authorization: `Bearer ${token}` },
    });

    salaryLeaves.value = response.data.data;
    pagination.total = response.data.meta?.paging?.total || 0;
  } catch (error) {
    console.error("Error fetching salary leave:", error);
  } finally {
    loading.value = false;
  }
};

// 👇 update handlers to call fetchSalaryLeaves
const handleTableChange = (pager) => {
  pagination.current = pager.current;
  pagination.pageSize = pager.pageSize;
  fetchSalaryLeaves();
};


  const downloadLeaveSummary = async (record) => {
    try {
      const token = localStorage.getItem("auth_token");
      const params = { month: record.month, year: record.year };

      const response = await axios.get("/api/v1/salary_leave/export", {
        params,
        headers: { Authorization: `Bearer ${token}` },
      });

      const link = document.createElement("a");
      link.href = response.data.download_url;
      link.setAttribute("download", response.data.filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      message.success("Leave Summary downloaded successfully.");
    } catch (error) {
      console.error("Error downloading leave summary:", error);
      message.error("Failed to download leave summary.");
    }
  };

  onMounted(() => fetchSalaryLeaves());

  return {
    t,
    loading,
    salaryLeaves,
    pagination,
    filters,
    columns,
    fetchSalaryLeaves,
    handleTableChange,
    downloadLeaveSummary,
  };
}
}
</script>
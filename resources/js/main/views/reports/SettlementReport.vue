<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Settlement Report" class="p-0" />
    </template>

    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t("menu.dashboard") }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>Reports</a-breadcrumb-item>
        <a-breadcrumb-item>Settlement Report</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <admin-page-table-content>
    <a-card :bordered="false">
      <a-form layout="vertical" @submit.prevent="confirmGenerate">
        <a-row :gutter="16">
          <a-col :xs="24" :sm="24" :md="12" :lg="8">
            <a-form-item label="Select Employee" class="required">
              <a-select
                v-model:value="formData.user_id"
                show-search
                placeholder="Search and select employee"
                :options="employeeOptions"
                :loading="fetchingEmployees"
                option-filter-prop="label"
              >
              </a-select>
            </a-form-item>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="4">
            <a-form-item label="From Date" class="required">
              <a-date-picker
                v-model:value="formData.from_date"
                style="width: 100%"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </a-form-item>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="4">
            <a-form-item label="To Date" class="required">
              <a-date-picker
                v-model:value="formData.to_date"
                style="width: 100%"
                format="YYYY-MM-DD"
                value-format="YYYY-MM-DD"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row :gutter="16">
          <a-col :xs="24" :sm="12" :md="6" :lg="8">
            <a-form-item label="Site Advance">
              <a-input-number
                v-model:value="formData.site_advance"
                style="width: 100%"
                :min="0"
                placeholder="Enter amount"
              />
            </a-form-item>
          </a-col>

          <a-col :xs="24" :sm="12" :md="6" :lg="8">
            <a-form-item label="Others">
              <a-input-number
                v-model:value="formData.others"
                style="width: 100%"
                :min="0"
                placeholder="Enter amount"
              />
            </a-form-item>
          </a-col>
        </a-row>

        <a-row>
          <a-col :span="24">
            <a-space>
              <a-button type="primary" :loading="generating" @click="confirmGenerate">
                <template #icon><FileAddOutlined /></template>
                Generate Report
              </a-button>
              <a-button @click="resetForm">Reset</a-button>
            </a-space>
          </a-col>
        </a-row>
      </a-form>
    </a-card>
  </admin-page-table-content>
</template>

<script>
import { ref, reactive, onMounted, createVNode } from "vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { FileAddOutlined, ExclamationCircleOutlined } from "@ant-design/icons-vue";
import { Modal, message } from "ant-design-vue";
import axios from "axios";

export default {
  components: {
    AdminPageHeader,
    FileAddOutlined,
  },
  setup() {
    const generating = ref(false);
    const fetchingEmployees = ref(false);
    const employeeOptions = ref([]);

    const formData = reactive({
      user_id: null,
      from_date: null,
      to_date: null,
      site_advance: 0,
      others: 0,
    });

    const fetchEmployees = async () => {
    fetchingEmployees.value = true;
    try {
        // Replace with your actual route URL
        const res = await axios.get("/api/v1/employees/all");
        
        if (res.data.success) {
        // Mapping to Ant Design Select option format { label, value }
        employeeOptions.value = res.data.data.map((user) => ({
            label: user.name,
            value: user.id,
        }));
        }
    } catch (err) {
        message.error("Failed to load employees");
    } finally {
        fetchingEmployees.value = false;
    }
    };

    const confirmGenerate = () => {
      if (!formData.user_id || !formData.from_date || !formData.to_date) {
        message.warning("Please fill in all required fields");
        return;
      }

      Modal.confirm({
        title: "Generate Settlement Report?",
        icon: createVNode(ExclamationCircleOutlined),
        content: "Are you sure you want to generate settlement report for the selected employee?",
        okText: "Generate",
        async onOk() {
          await generateReport();
        },
      });
    };

        const generateReport = async () => {
  generating.value = true;
  try {
    const res = await axios.post("/api/v1/reports/generate/settlement", formData);
    
    if (res.data.success) {
      const link = document.createElement("a");
      link.href = res.data.download_url;
      link.setAttribute("download", res.data.filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);
      message.success(res.data.message);
    }
  } catch (err) {
    message.error(err.response?.data?.message || "Generation failed");
  } finally {
    generating.value = false;
  }
};

    const resetForm = () => {
      formData.user_id = null;
      formData.from_date = null;
      formData.to_date = null;
      formData.site_advance = 0;
      formData.others = 0;
    };

    onMounted(fetchEmployees);

    return {
      formData,
      employeeOptions,
      fetchingEmployees,
      generating,
      confirmGenerate,
      resetForm,
    };
  },
};
</script>
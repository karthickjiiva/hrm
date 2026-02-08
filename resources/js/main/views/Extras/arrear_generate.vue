<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Arrears" class="p-0" />
    </template>
    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t(`menu.dashboard`) }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>reports</a-breadcrumb-item>
        <a-breadcrumb-item>Arrears</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <a-card class="page-content-container arrears-container">
    <a-row :gutter="[32, 32]" justify="center" align="top"> 
      <a-col :xs="24" :md="10">
         <div class="upload-card">
    <h3 class="upload-title">📂 Upload Arrears File</h3>
    <p class="upload-instructions">
      Please upload the arrears Excel/CSV file here. Make sure the file format matches the template provided.
    </p>
        <a-upload-dragger
        name="file"
        action="/api/v1/upload_arrears"  
        :multiple="false"
        :maxCount="1" 
        @change="handleUploadChange"
        accept=".xlsx,.xls,.csv"
        class="modern-uploader"
        >
      <div class="upload-inner">
        <div class="upload-icon">
          <UploadOutlined style="font-size: 40px; color: #000" />
        </div>
        <p class="ant-upload-text">Drag & Drop your file here</p>
        <p class="ant-upload-hint">or click to browse from your computer</p>
      </div>
    </a-upload-dragger>
  </div>
      </a-col>

      <!-- Right side compact form -->
      <a-col :xs="24" :md="10">
        <div class="panel form-panel">
          <h3 class="section-title">📝 Generate Report</h3>
          <a-form layout="vertical">
            <a-form-item label="Select Year" name="year">
              <a-select
                v-model:value="formData.year"
                placeholder="Select Year"
                :options="yearOptions"
                allow-clear
              />
            </a-form-item>

            <a-form-item label="Select Month" name="month">
              <a-select
                v-model:value="formData.month"
                placeholder="Select Month"
                :options="monthOptions"
                allow-clear
              />
            </a-form-item>

            <div class="text-center mt-3">
              <a-button
                type="primary"
                :loading="loading"
                @click="handleGenerateArrears"
                block
                size="large"
              >
                <template #icon>
                  <template v-if="!loading">
                    <FileDoneOutlined />
                  </template>
                </template>
                Generate Arrears Report
              </a-button>
            </div>
          </a-form>
        </div>
      </a-col>
    </a-row>
  </a-card>
</template>

<script>
import { ref } from "vue";
import { message } from "ant-design-vue";
import { InboxOutlined, FileDoneOutlined } from "@ant-design/icons-vue";
import { UploadOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";
import axios from "axios";

export default {
  components: {
    AdminPageHeader,
    InboxOutlined,
    UploadOutlined,
    FileDoneOutlined,
  },
  setup() {
    const loading = ref(false);
    const formData = ref({ year: null, month: null });

    const yearOptions = ref([]);
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
      yearOptions.value.push({
        value: currentYear - i,
        label: currentYear - i,
      });
    }

    const monthOptions = ref([
      { value: 1, label: "January" },
      { value: 2, label: "February" },
      { value: 3, label: "March" },
      { value: 4, label: "April" },
      { value: 5, label: "May" },
      { value: 6, label: "June" },
      { value: 7, label: "July" },
      { value: 8, label: "August" },
      { value: 9, label: "September" },
      { value: 10, label: "October" },
      { value: 11, label: "November" },
      { value: 12, label: "December" },
    ]);
    
    const handleGenerateArrears = async () => {
  if (!formData.value.year || !formData.value.month) {
    message.error("Please select year and month");
    return;
  }

  loading.value = true;
  try {
    const token = localStorage.getItem("auth_token");
    const params = {
      month: formData.value.month,
      year: formData.value.year,
    };

    const response = await axios.post("/api/v1/arrears/generate", params, {
      headers: { Authorization: `Bearer ${token}` },
    });

    const data = response.data;

    if (data.success) {
      if (data.data?.error_count > 0) {
        // Map backend errors to user-friendly messages
        const friendlyErrors = data.data.errors.map(err => {
          if (err.error.includes("Payroll not found")) {
            return `Payroll not found for ${params.month}/${params.year}.`;
          } else if (err.error.includes("Employee not found")) {
            return `Employee with code ${err.emp_code} does not exist.`;
          } else if (err.error.includes("Employee type not set")) {
            return `Employee type not configured for ${err.emp_code}.`;
          } else {
            return `${err.emp_code}: ${err.error}`;
          }
        });

        // Show first error in toast
        message.error(friendlyErrors[0]);

        // Optionally: log or display all errors somewhere
        console.table(friendlyErrors);
      } else {
        message.success(data.message || "Arrears generated successfully.");
      }
    } else {
      message.warning(data.message || "Arrears generation finished with warnings.");
    }
  } catch (error) {
    console.error("Error generating arrears:", error);
    message.error(
      error.response?.data?.message || "Failed to generate arrears. Please try again."
    );
  } finally {
    loading.value = false;
  }
}


    const handleUploadChange = (info) => {
        if (info.file.status === "uploading") {
            return;
        }

        if (info.file.status === "done") {
            const response = info.file.response;
            if (response.success) {
            message.success(
                `${info.file.name} uploaded successfully. Rows inserted: ${response.rows_inserted}`
            );
            } else {
            message.error(response.message || "Upload failed.");
            }
            } else if (info.file.status === "error") {
                message.error(`${info.file.name} upload failed.`);
            }
    }

    return {
      loading,
      formData,
      yearOptions,
      monthOptions,
      handleGenerateArrears,
      handleUploadChange,
    };
  },
};
</script>

<style scoped>
.arrears-container {
  max-width: 1200px;
  margin: 0 auto;
}

.panel {
  background: #fff;
  padding: 24px;
  border-radius: 12px;
  border: 1px solid #c8bebe;
}

.section-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 8px;
}

.section-desc {
  font-size: 14px;
  color: #666;
  margin-bottom: 16px;
}

.upload-card {
  background: #ffffff;
  border-radius: 16px;
  padding: 30px 20px;
  border: 1px solid #c8bebe;
  text-align: center;
  transition: all 0.3s ease;
}


.upload-title {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 8px;
  color: #333;
}

.upload-instructions {
  font-size: 14px;
  color: #666;
  margin-bottom: 20px;
}
 
 :deep(.ant-upload-wrapper .ant-upload-drag) {
  border: 2px dashed #000 !important;
  border-radius: 12px !important;
  background: #fafafa;
  transition: all 0.3s ease;
}

:deep(.ant-upload-wrapper .ant-upload-drag:hover) {
  border-color: #096dd9 !important;
  background: #f0faff;
}

:deep(.ant-upload-wrapper .ant-upload-drag.ant-upload-drag-hover) {
  border-color: #096dd9 !important;
  background: #e6f7ff !important;
  box-shadow: 0 0 12px rgba(24, 144, 255, 0.3);
}

.upload-inner {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
}

.upload-icon {
  background: #e6f7ff;
  border-radius: 50%;
  margin-bottom: 12px; 
}

</style>

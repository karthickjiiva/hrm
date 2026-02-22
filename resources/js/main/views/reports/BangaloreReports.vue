<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Bangalore Reports" class="p-0" />
    </template>

    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item>
          <router-link :to="{ name: 'admin.dashboard.index' }">
            {{ $t(`menu.dashboard`) }}
          </router-link>
        </a-breadcrumb-item>
        <a-breadcrumb-item>reports</a-breadcrumb-item>
        <a-breadcrumb-item>Bangalore Reports</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <a-card class="page-content-container">
    <div class="bangalore-form-wrapper">
      <a-row :gutter="[32, 32]" justify="center" align="stretch" class="relative-row">
        
        <a-col :xs="24" :lg="10">
          <div class="report-card payroll-card">
            <div class="card-gradient-top payroll-gradient"></div>
            <div class="card-content">
              <div class="icon-wrapper">
                <div class="icon-circle payroll-bg">
                  <FileTextOutlined style="font-size: 24px; color: #1890ff" />
                </div>
              </div>
              <h3 class="section-title">Payroll Report</h3>
              <p class="section-subtitle">Generate and export monthly employee payroll data for Bangalore branch.</p>

              <a-form layout="vertical" class="custom-form">
                <a-form-item label="Select Fiscal Year">
                  <a-select v-model:value="payrollForm.year" placeholder="Choose Year" :options="yearOptions" allow-clear />
                </a-form-item>

                <a-form-item label="Select Report Month">
                  <a-select v-model:value="payrollForm.month" placeholder="Choose Month" :options="monthOptions" allow-clear />
                </a-form-item>

                <div class="button-wrapper">
                  <a-button type="primary" size="large" :loading="loadingPayroll" @click="handlePayrollDownload" block class="download-btn">
                    <template #icon><DownloadOutlined v-if="!loadingPayroll" /></template>
                    Download Report
                  </a-button>
                </div>
              </a-form>
            </div>
          </div>
        </a-col>

        <a-col :md="2" class="divider-col">
          <div class="vertical-divider-modern">
            <div class="divider-dot top"></div>
            <div class="divider-line"></div>
            <div class="divider-dot bottom"></div>
          </div>
        </a-col>

        <a-col :xs="24" :lg="10">
          <div class="report-card tax-card">
            <div class="card-gradient-top tax-gradient"></div>
            <div class="card-content">
              <div class="icon-wrapper">
                <div class="icon-circle tax-bg">
                  <SafetyCertificateOutlined style="font-size: 24px; color: #52c41a" />
                </div>
              </div>
              <h3 class="section-title">Professional Tax</h3>
              <p class="section-subtitle">Access professional tax compliance reports and statutory deductions.</p>

              <a-form layout="vertical" class="custom-form">
                <a-form-item label="Select Fiscal Year">
                  <a-select v-model:value="profTaxForm.year" placeholder="Choose Year" :options="yearOptions" allow-clear />
                </a-form-item>

              <a-form-item label="Select Period" name="month">
  <a-select 
    v-model:value="profTaxForm.month" 
    placeholder="Select PT Period" 
    :options="ptMonthOptions" 
    allow-clear 
  />
</a-form-item>
                <div class="button-wrapper">
                  <a-button type="primary" size="large" :loading="loadingProfTax" @click="handleProfTaxDownload" block class="download-btn tax-btn">
                    <template #icon><DownloadOutlined v-if="!loadingProfTax" /></template>
                    Download Report
                  </a-button>
                </div>
              </a-form>
            </div>
          </div>
        </a-col>
      </a-row>
    </div>
  </a-card>
</template>

<script>
import { ref } from "vue";
import { message } from "ant-design-vue";
import axios from "axios";
import { DownloadOutlined, FileTextOutlined, SafetyCertificateOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";

export default {
  components: { AdminPageHeader, DownloadOutlined, FileTextOutlined, SafetyCertificateOutlined },
  setup() {
    const loadingPayroll = ref(false);
    const loadingProfTax = ref(false);

    const payrollForm = ref({ year: new Date().getFullYear(), month: new Date().getMonth() + 1 });
    const profTaxForm = ref({ year: new Date().getFullYear(), month: new Date().getMonth() + 1 });

    const yearOptions = ref([]);
    const currentYear = new Date().getFullYear();
    for (let i = 0; i < 5; i++) {
      yearOptions.value.push({ value: currentYear - i, label: currentYear - i });
    }

    const monthOptions = ref([
      { value: 1, label: "January" }, { value: 2, label: "February" },
      { value: 3, label: "March" }, { value: 4, label: "April" },
      { value: 5, label: "May" }, { value: 6, label: "June" },
      { value: 7, label: "July" }, { value: 8, label: "August" },
      { value: 9, label: "September" }, { value: 10, label: "October" },
      { value: 11, label: "November" }, { value: 12, label: "December" },
    ]);

    const ptMonthOptions = ref([
  { value: "4-9", label: "April - September" },
  { value: "10-3", label: "October - March" },
]);

    const handlePayrollDownload = async () => {
      if (!payrollForm.value.year || !payrollForm.value.month) {
        message.error("Please select year and month");
        return;
      }
      loadingPayroll.value = true;
      try {
        const token = localStorage.getItem("auth_token");
        const response = await axios.get("/api/v1/payroll/export", {
          params: { month: payrollForm.value.month, year: payrollForm.value.year, type: "bangalore" },
          headers: { Authorization: `Bearer ${token}` },
        });
        const { download_url, filename } = response.data;
        const link = document.createElement("a");
        link.href = download_url;
        link.setAttribute("download", filename);
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        message.success("Payroll Report downloaded successfully.");
      } catch (error) {
        message.error("Failed to download report.");
      } finally {
        loadingPayroll.value = false;
      }
    };

     const handleProfTaxDownload = async () => {
            if (!profTaxForm.value.year || !profTaxForm.value.month) {
                message.error("Please select year and month");
                return;
            }
            loadingProfTax.value = true;
            try {
                const token = localStorage.getItem('auth_token');
                const params = {
                    month: profTaxForm.value.month,
                    year: profTaxForm.value.year,
                    type: "bangalore",
                };
                const response = await axios.get("/api/v1/prof_tax/export", {
                    params,
                    headers: { Authorization: `Bearer ${token}` },
                });

                if (response.data.success) {
                    const downloadUrl = response.data.download_url;
                    const filename = response.data.filename;
                    const link = document.createElement("a");
                    link.href = downloadUrl;
                    link.setAttribute("download", filename);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    message.success("Report downloaded successfully.");
                } else {
                    message.error(response.data.message || "No data available for the selected month/year.");
                }

            } catch (error) {
                console.error("Error downloading report:", error);
                if (error.response && error.response.data && error.response.data.message) {
                    message.error(error.response.data.message);
                } else {
                    message.error("Failed to download report.");
                }
            } finally {
                loadingProfTax.value = false;
            }
        };

    return {
      payrollForm, profTaxForm, yearOptions, monthOptions,ptMonthOptions,
      loadingPayroll, loadingProfTax, handlePayrollDownload, handleProfTaxDownload,
    };
  },
};
</script>

<style scoped>
/* NO HARDCODED BACKGROUND HERE - LET THE CARD DO ITS JOB */
.page-content-container {
  min-height: 80vh;
}

.bangalore-form-wrapper {
  max-width: 1000px;
  margin: 0 auto;
  padding: 20px 0;
}

/* Internal Cards inherit theme container color */
.report-card {
  background: var(--ant-color-bg-container);
  border-radius: 16px;
  overflow: hidden;
  position: relative;
  box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
  transition: all 0.3s ease;
  height: 100%;
}

 /* Specific Border for Payroll - Lighter Blue */
.payroll-card {
  border: 1px solid rgba(24, 144, 255, 0.3);
}

/* Specific Border for Tax - Lighter Green */
.tax-card {
  border: 1px solid rgba(82, 196, 26, 0.3);
}

.card-gradient-top { height: 4px; width: 100%; }
.payroll-gradient { background: linear-gradient(90deg, #1890ff, #69c0ff); }
.tax-gradient { background: linear-gradient(90deg, #52c41a, #95de64); }

.card-content { padding: 40px; }

.icon-wrapper { display: flex; justify-content: center; margin-bottom: 20px; }
.icon-circle {
  width: 60px; height: 60px; border-radius: 50%;
  display: flex; align-items: center; justify-content: center;
}
.payroll-bg { background-color: rgba(24, 144, 255, 0.1); }
.tax-bg { background-color: rgba(82, 196, 26, 0.1); }

.section-title {
  text-align: center; font-size: 22px; font-weight: 700; margin-bottom: 8px;
  color: var(--ant-color-text-heading);
}

.section-subtitle {
  text-align: center; color: var(--ant-color-text-description);
  font-size: 14px; margin-bottom: 32px;
}

.custom-form :deep(.ant-form-item-label > label) {
  font-weight: 600;
  color: var(--ant-color-text);
}

.custom-form :deep(.ant-select-selector) {
  border-radius: 8px !important;
  height: 40px !important;
  display: flex; align-items: center;
}

.button-wrapper { margin-top: 40px; }
.download-btn { border-radius: 8px; font-weight: 600; }
.tax-btn { background-color: #52c41a; border-color: #52c41a; }

.divider-col { display: flex; justify-content: center; align-items: center; }
.vertical-divider-modern { height: 80%; display: flex; flex-direction: column; align-items: center; }
.divider-line {
  width: 1px; flex-grow: 1;
  background: linear-gradient(to bottom, transparent, var(--ant-color-border-secondary), var(--ant-color-border-secondary), transparent);
}
.divider-dot { width: 6px; height: 6px; border-radius: 50%; background-color: var(--ant-color-border); }

@media (max-width: 991px) {
  .divider-col { display: none; }
  .report-card { margin-bottom: 24px; }
}
</style>
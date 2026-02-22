<template>
  <AdminPageHeader>
    <template #header>
      <a-page-header title="Master Data Hub" class="p-0" />
    </template>
    <template #breadcrumb>
      <a-breadcrumb separator="-" style="font-size: 12px">
        <a-breadcrumb-item><router-link :to="{ name: 'admin.dashboard.index' }">Dashboard</router-link></a-breadcrumb-item>
        <a-breadcrumb-item>Reports</a-breadcrumb-item>
        <a-breadcrumb-item>Master Reports</a-breadcrumb-item>
      </a-breadcrumb>
    </template>
  </AdminPageHeader>

  <div class="master-reports-container">
    <a-row :gutter="[32, 32]" justify="center" align="stretch">
      
      <a-col :xs="24" :lg="11">
        <div class="master-card employee-master">
          <div class="card-overlay"></div>
          <div class="card-content">
            <div class="icon-box">
              <TeamOutlined class="master-icon" />
            </div>
            <h2 class="master-title">Employee Master</h2>
            <p class="master-desc">
              Comprehensive database export of all staff members. Includes personal details, 
              bank information, statutory IDs, and employment history in a single master sheet.
            </p>
            
            <a-button type="primary" size="large" block class="master-download-btn" :loading="loadingEmp" @click="downloadEmpMaster">
              <template #icon><CloudDownloadOutlined /></template>
              Download Employee Master
            </a-button>
          </div>
        </div>
      </a-col>

      <a-col :lg="2" class="divider-wrapper">
        <div class="glowing-divider">
          <div class="dot top"></div>
          <div class="line"></div>
          <div class="dot bottom"></div>
        </div>
      </a-col>

      <a-col :xs="24" :lg="11">
        <div class="master-card payroll-master">
          <div class="card-overlay"></div>
          <div class="card-content">
            <div class="icon-box">
              <WalletOutlined class="master-icon" />
            </div>
            <h2 class="master-title">Payroll Master</h2>
            <p class="master-desc">
              Centralized financial report containing annual salary structures, 
              statutory deductions, and net payouts aggregated for the selected year.
            </p>

            <div class="year-selector-wrapper">
               <label class="selector-label">Select Financial Year</label>
               <a-select 
                v-model:value="selectedYear" 
                placeholder="Choose Year" 
                class="custom-year-select"
                :options="yearOptions"
              />
            </div>
        
            <a-button type="primary" size="large" block class="master-download-btn payroll-btn" :loading="loadingPayroll" @click="downloadPayrollMaster">
              <template #icon><CloudDownloadOutlined /></template>
              Download {{ selectedYear }} Report
            </a-button>
          </div>
        </div>
      </a-col>

    </a-row>
  </div>
</template>

<script>
import { ref, onMounted } from "vue";
import { TeamOutlined, WalletOutlined, CloudDownloadOutlined, CheckCircleFilled } from "@ant-design/icons-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { message } from "ant-design-vue";
import axios from "axios";

export default {
components: { AdminPageHeader, TeamOutlined, WalletOutlined, CloudDownloadOutlined, CheckCircleFilled },
setup() {
        const loadingEmp = ref(false);
        const loadingPayroll = ref(false);
        
        // Dynamic Year Logic
        const currentYear = new Date().getFullYear();
        const selectedYear = ref(currentYear);
        const yearOptions = ref([]);

        const generateYearOptions = () => {
            const years = [];
            for (let i = 0; i < 5; i++) {
                const year = currentYear - i;
                years.push({ value: year, label: `${year}` });
            }
            yearOptions.value = years;
        };

        const handleDownload = async (type) => {
            const isEmp = type === 'employee';
            isEmp ? (loadingEmp.value = true) : (loadingPayroll.value = true);

            try {
                const token = localStorage.getItem('auth_token');
                const endpoint = isEmp ? 'employee-export' : 'payroll-export';
                
                // Pass the selected year as a query parameter for payroll
                const params = isEmp ? {} : { year: selectedYear.value };

                const response = await axios.get(`/api/v1/master-reports/${endpoint}`, {
                    headers: { Authorization: `Bearer ${token}` },
                    params: params
                });

                if (response.data.success) {
                    const link = document.createElement("a");
                    link.href = response.data.download_url;
                    link.setAttribute("download", response.data.filename);
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    message.success(`${isEmp ? 'Employee' : 'Payroll'} Master downloaded successfully.`);
                }
            } catch (error) {
                console.error(`Error downloading ${type} master:`, error);
                message.error(error.response?.data?.message || `Failed to download ${type} report.`);
            } finally {
                isEmp ? (loadingEmp.value = false) : (loadingPayroll.value = false);
            }
        };

        const downloadEmpMaster = () => handleDownload('employee');
        const downloadPayrollMaster = () => handleDownload('payroll');

        onMounted(() => {
            generateYearOptions();
        });

        return { 
            loadingEmp, 
            loadingPayroll, 
            selectedYear,
            yearOptions,
            downloadEmpMaster, 
            downloadPayrollMaster 
        };
    }
};
</script>
<style scoped>
.master-reports-container {
  padding: 40px;
  background: #f0f2f5;
  min-height: 80vh;
}

.master-card {
  position: relative;
  background: #fff;
  border-radius: 24px;
  padding: 40px;
  overflow: hidden;
  height: 100%;
  box-shadow: 0 10px 30px rgba(0,0,0,0.05);
  transition: transform 0.3s ease;
  border: 1px solid rgba(255,255,255,0.8);
}
 
.card-content { position: relative; z-index: 2; }

.icon-box {
  width: 60px;
  height: 60px;
  border-radius: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 24px;
}

.employee-master .icon-box { background: #e6f7ff; color: #1890ff; }
.payroll-master .icon-box { background: #f6ffed; color: #52c41a; }

.master-icon { font-size: 30px; }

.master-title {
  font-size: 24px;
  font-weight: 700;
  color: #1a1a1a;
  margin-bottom: 16px;
}

.master-desc {
  color: #666;
  font-size: 15px;
  line-height: 1.6;
  margin-bottom: 24px;
  min-height: 72px;
}

.feature-list {
  list-style: none;
  padding: 0;
  margin-bottom: 32px;
}

.feature-list li {
  margin-bottom: 10px;
  color: #444;
  display: flex;
  align-items: center;
  gap: 10px;
}

.feature-list span { color: #52c41a; }

.year-selector-wrapper {
    margin-bottom: 20px;
    text-align: left;
}
.selector-label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 8px;
    opacity: 0.8;
}
.custom-year-select {
    width: 100%;
} 
:deep(.ant-select-selector) {
    border-radius: 8px !important;
    height: 40px !important;
    display: flex;
    align-items: center;
}

.master-download-btn {
  height: 50px;
  border-radius: 12px;
  font-weight: 600;
  font-size: 16px;
  background: #1890ff;
  border: none;
}

.payroll-btn { background: #52c41a; }

/* Glowing Divider */
.divider-wrapper {
  display: flex;
  justify-content: center;
  align-items: center;
}

.glowing-divider {
  display: flex;
  flex-direction: column;
  align-items: center;
  height: 100%;
}

.divider-line {
  width: 2px;
  flex-grow: 1;
  background: linear-gradient(to bottom, transparent, #d9d9d9, transparent);
}

.dot {
  width: 8px;
  height: 8px;
  border-radius: 50%;
  background: #d9d9d9;
}
</style>
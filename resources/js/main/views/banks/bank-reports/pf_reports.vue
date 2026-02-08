<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="PF Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>reports</a-breadcrumb-item>
                <a-breadcrumb-item> PF Report </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <div class="payroll-form-wrapper">
            <div class="payroll-form-box">
                <a-form layout="vertical">
                    <a-row justify="center">
                        <a-col :xs="18" :sm="12" :md="12">
                            <a-form-item label="Select Year" name="year">
                                <a-select v-model:value="formData.year" placeholder="Select Year" :options="yearOptions"
                                    allow-clear />
                            </a-form-item>

                            <a-form-item label="Select Month" name="month">
                                <a-select v-model:value="formData.month" placeholder="Select Month"
                                    :options="monthOptions" allow-clear />
                            </a-form-item>

                            <div class="text-center mt-3">
                                <a-button type="primary" :loading="loading" @click="handleExportToExcel"
                                    style="width: 100%">
                                    <template #icon>
                                        <template v-if="!loading">
                                            <DownloadOutlined />
                                        </template>
                                    </template>
                                    Download PF Report
                                </a-button>
                            </div>
                        </a-col>
                    </a-row>
                </a-form>
            </div>
        </div>
    </a-card>

</template>

<script>
import { ref, onMounted } from "vue";
import { message, Modal } from "ant-design-vue";
import axios from "axios";
import { DownloadOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        AdminPageHeader,
        DownloadOutlined,
    },
    setup() {
        const loading = ref(false);
        const showConfirmation = ref(false);
        const formData = ref({
            year: null,
            month: null,
        });

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


        const handleExportToExcel = async () => {
            if (!formData.value.year || !formData.value.month) {
                message.error("Please select year and month");
                return;
            }
            loading.value = true;
            try {
                const token = localStorage.getItem('auth_token');
                const params = {
                    month: formData.value.month,
                    year: formData.value.year,
                };
                const response = await axios.get("/api/v1/pf_report/export", {
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
                    message.success("PF Report downloaded successfully.");
                } else {
                    message.error(response.data.message || "No data available for the selected month/year.");
                }

            } catch (error) {
                console.error("Error downloading PF report:", error);
                if (error.response && error.response.data && error.response.data.message) {
                    message.error(error.response.data.message);
                } else {
                    message.error("Failed to download report.");
                }
            } finally {
                loading.value = false;
            }
        };



        return {
            loading,
            formData,
            yearOptions,
            monthOptions,
            showConfirmation,
            handleExportToExcel,
        };
    },
};
</script>
<style scoped>
.payroll-form-wrapper {
    display: flex;
    justify-content: center;
    padding: 40px 16px;
}

.payroll-form-box {
    border: 1px solid #dcdcdc;
    border-radius: 12px;
    padding: 32px;
    width: 100%;
    max-width: 600px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
    margin: 0 auto;
}
</style>

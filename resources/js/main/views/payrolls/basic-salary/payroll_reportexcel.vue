<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Payroll Report" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    {{ $t(`menu.payrolls`) }}
                </a-breadcrumb-item>
                <a-breadcrumb-item> Payroll Report </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <a-form layout="vertical">
            <a-row justify="center">
                <a-col :xs="18" :sm="12" :md="8">
                    <a-form-item label="Select Year" name="year">
                        <a-select v-model:value="formData.year" placeholder="Select Year" :options="yearOptions"
                            allow-clear />
                    </a-form-item>

                    <a-form-item label="Select Month" name="month">
                        <a-select v-model:value="formData.month" placeholder="Select Month" :options="monthOptions"
                            allow-clear />
                    </a-form-item>

                    <div class="text-center mt-3">
                        <a-button type="primary" :loading="loading" @click="handleExportToExcel">
                           Download Payroll Report                           
                        </a-button>
                    </div>
                </a-col>
            </a-row>
        </a-form>
    </a-card>

</template>

<script>
import { ref, onMounted } from "vue";
import { message, Modal } from "ant-design-vue";
import axios from "axios";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        AdminPageHeader,
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
                message.error("Please select both year and month");
                return;
            }

            loading.value = true;

            try {
                const token = localStorage.getItem('auth_token');
                const params = {
                    month: formData.value.month,
                    year: formData.value.year,
                };

                const response = await axios.get("/api/v1/payroll/export", {
                    params,
                    headers: {
                        Authorization: `Bearer ${token}`,
                    },
                    responseType: "blob",
                });

                const url = window.URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement("a");
                link.href = url;

                const monthName = monthOptions.value.find(m => m.value === formData.value.month)?.label || formData.value.month;
                link.setAttribute("download", `Payroll_${monthName}_${formData.value.year}.xlsx`);
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                message.success("Payroll Report downloaded successfully.");
            } catch (error) {
                console.error("Error downloading payroll report:", error);
                message.error("Failed to download report.");
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

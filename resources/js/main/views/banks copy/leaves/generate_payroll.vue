<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Generate Payroll" class="p-0" />
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
                <a-breadcrumb-item> Generate Payroll </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <a-card class="page-content-container">
        <div class="payroll-form-wrapper">
        <div class="payroll-form-box">
        <a-form layout="vertical">           
            <a-row justify="center">
                 <a-col :xs="12" :sm="12" :md="8" :lg="12">
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
                        @click="handleSubmit"
                        style="width: 100%"
                        >
                        <template #icon>
                            <template v-if="!loading">
                            <FileTextOutlined  />
                            </template>
                        </template>
                        Generate Payroll
                        </a-button>
                    </div>
                </a-col>
            </a-row>
        </a-form>
        </div> 
        </div>
        <!-- Confirmation Modal -->
        <a-modal
            v-model:visible="showConfirmation"
            title="Confirm Payroll Generation"
            @ok="confirmGenerate"
            @cancel="showConfirmation = false"
        >
            <p>
                Are you sure you want to generate payroll for
                {{
                    monthOptions.find((m) => m.value === formData.month)?.label
                }}, {{ formData.year }}?
            </p>
            <p class="text-danger">
                Note: This action cannot be undone. Payroll for this period will
                be generated for all eligible employees.
            </p>
        </a-modal>
    </a-card>
</template>

<script>
import { ref, onMounted } from "vue";
import { message, Modal } from "ant-design-vue";
import axios from "axios";
import { FileTextOutlined  } from '@ant-design/icons-vue';
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        AdminPageHeader,
        FileTextOutlined ,
    },
    setup() {
        const loading = ref(false);
        const showConfirmation = ref(false);
        const formData = ref({
            year: null,
            month: null,
        });

        // Generate year options (current year and past 10 years)
        const yearOptions = ref([]);
        const currentYear = new Date().getFullYear();
        for (let i = 0; i < 5; i++) {
            yearOptions.value.push({
                value: currentYear - i,
                label: currentYear - i,
            });
        }

        // Month options
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

        // Check if payroll already exists for the selected period
        const checkPayrollExists = async () => {
            try {
                const response = await axios.get(
                    "/api/v1/payroll-check-exists",
                    {
                        params: {
                            year: formData.value.year,
                            month: formData.value.month,
                        },
                    }
                );
                return response.data.exists;
            } catch (error) {
                console.error("Error checking payroll:", error);
                message.error("Failed to check payroll existence");
                return false;
            }
        };

        // Handle form submission
        const handleSubmit = async () => {
            if (!formData.value.year || !formData.value.month) {
                message.error("Please select both year and month");
                return;
            }

            loading.value = true;

            try {
                // Check if payroll already exists
                const exists = await checkPayrollExists();

                if (exists) {
                    Modal.confirm({
                        title: "Payroll Already Exists",
                        content:
                            "Payroll for the selected period already exists. Do you want to regenerate it?",
                        okText: "Regenerate",
                        cancelText: "Cancel",
                        onOk: () => generatePayroll(),
                    });
                } else {
                    generatePayroll();
                }
            } finally {
                loading.value = false;
            }
        };

        // Generate payroll function
        const generatePayroll = async () => {
            loading.value = true;

            try {
                const response = await axios.post("/api/v1/payroll-generate", {
                    year: formData.value.year,
                    month: formData.value.month,
                });

                if (response.data.success) {
                    const monthName = monthOptions.value.find(
                        (m) => m.value === formData.value.month
                    )?.label;
                    message.success(
                        `Payroll for ${monthName}, ${formData.value.year} generated successfully!`
                    );
                } else {
                    message.error(
                        response.data.message || "Failed to generate payroll"
                    );
                }
            } catch (error) {
                console.error("Error generating payroll:", error);
                message.error(
                    error.response?.data?.message ||
                        "An error occurred while generating payroll"
                );
            } finally {
                loading.value = false;
                showConfirmation.value = false;
            }
        };

        return {
            loading,
            formData,
            yearOptions,
            monthOptions,
            showConfirmation,
            handleSubmit,
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
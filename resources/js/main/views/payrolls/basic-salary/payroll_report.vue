<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Payslip" class="p-0" />
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
                <a-breadcrumb-item> Payslip </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="12" :xl="8">
                        <a-space>
                            <!-- Month Selector -->
                            <a-select style="width: 120px" v-model:value="filters.month" placeholder="Select month"
                                @change="fetchPayrollData">
                                <a-select-option v-for="month in 12" :key="month" :value="month">
                                    {{
                                        new Date(
                                            2000,
                                            month - 1,
                                            1
                                        ).toLocaleString("default", {
                                            month: "long",
                                        })
                                    }}
                                </a-select-option>
                            </a-select>

                            <!-- Year Selector (last 5 years) -->
                            <a-select style="width: 100px" v-model:value="filters.year" placeholder="Select year"
                                @change="fetchPayrollData">
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
                    <a-table :columns="columns" :row-key="(record) => record.xid" :data-source="payrollData"
                        :pagination="pagination" :loading="loading" @change="handleTableChange" bordered size="middle">
                        <template #bodyCell="{ column, record }">
                             <template v-if="column.dataIndex === 'id'">
                                {{ record.employee.id }}
                            </template>
                            <template v-if="column.dataIndex === 'employee'">
                                {{ record.employee?.name }}
                            </template>
                            <template v-if="column.dataIndex === 'period'">
                                {{ getMonthName(record.month) }}
                                {{ record.year }}
                            </template>
                            <!-- <template v-if="column.dataIndex === 'earnings'">
                                <div>
                                    Basic: {{ formatCurrency(record.basic) }}
                                </div>
                                <div>HRA: {{ formatCurrency(record.hra) }}</div>
                                <div>
                                    Allowance:
                                    {{ formatCurrency(record.allowance) }}
                                </div>
                                <div>
                                    Total:
                                    {{ formatCurrency(record.total_earnings) }}
                                </div>
                            </template>
                            <template v-if="column.dataIndex === 'deductions'">
                                <div>
                                    PF: {{ formatCurrency(record.pf_employee) }}
                                </div>
                                <div>
                                    ESI:
                                    {{ formatCurrency(record.esi_employee) }}
                                </div>
                                <div>Tax: {{ formatCurrency(record.tds) }}</div>
                                <div>
                                    Total:
                                    {{
                                        formatCurrency(
                                            record.total_taxes_deductions
                                        )
                                    }}
                                </div>
                            </template>
                            <template v-if="column.dataIndex === 'net_pay'">
                                {{ formatCurrency(record.net_salary) }}
                                <div class="text-sm text-gray-500">
                                    {{ record.net_salary_in_words }}
                                </div>
                            </template> -->
                            <template v-if="column.dataIndex === 'action'">
                                <a-button
                                    type="primary"
                                    :loading="downloadLoadingIds.includes(record.xid)"
                                    @click="downloadPayslip(record)"
                                    style="margin-left: 1px; width: 120px;"
                                >
                                    <template #icon>
                                    <template v-if="!downloadLoadingIds.includes(record.xid)">
                                        <DownloadOutlined />
                                    </template>
                                    </template>
                                    Download
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
import { ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import { DownloadOutlined } from '@ant-design/icons-vue';
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import api from "../../../../common/composable/api";
import axios from "axios";
export default {
    components: {
        AdminPageHeader,
        DownloadOutlined,
    },
    setup() {
        const { t } = useI18n();
        const { get, download } = api();
        const loading = ref(false);
        const payrollData = ref([]);
        const filters = ref({
            month: new Date().getMonth() + 1,
            year: new Date().getFullYear(),
        });
        const pagination = ref({
            current: 1,
            pageSize: 10,
            total: 0,
        });
        
        const downloadLoadingIds = ref([]);

        const columns = ref([
             {
                title: t("ID"),
                dataIndex: "id",
                width: 200,
            },
            {
                title: t("Employee Name"),
                dataIndex: "employee",
                width: 200,
            },
            {
                title: t("Payroll Period"),
                dataIndex: "period",
                width: 150,
            },
            // {
            //     title: t("payroll.earnings"),
            //     dataIndex: "earnings",
            // },
            // {
            //     title: t("payroll.deductions"),
            //     dataIndex: "deductions",
            // },
            // {
            //     title: t("payroll.net_pay"),
            //     dataIndex: "net_pay",
            //     width: 180,
            // },
            {
                title: t("common.action"),
                dataIndex: "action",
                width: 120,
            },
        ]);

        const formatCurrency = (amount) => {
            return new Intl.NumberFormat("en-US", {
                style: "currency",
                currency: "USD",
            }).format(amount);
        };

        const getMonthName = (month) => {
            return new Date(2000, month - 1, 1).toLocaleString("default", {
                month: "short",
            });
        };

        const fetchPayrollData = async () => {
            try {
                loading.value = true;
                const token = localStorage.getItem('auth_token');
                const params = {
                    month: filters.value.month,
                    year: filters.value.year,
                    page: pagination.value.current,
                    limit: pagination.value.pageSize,
                    fields:
                        "id,xid,employee_id,x_employee_id,employee.name,month,year," +
                        "basic,hra,allowance,food_allowance,total_earnings," +
                        "pf_employee,esi_employee,professional_tax,tds,total_taxes_deductions," +
                        "net_salary,net_salary_in_words",
                };
                const response = await axios.get("/api/v1/payroll_new", {
                    params,
                    headers: {
                        Authorization: `Bearer ${token}`,
                    }
                });
                // const response = await get("payroll_new", params);
                payrollData.value = response.data.data;
                pagination.value.total = response.data.meta?.pagination?.total || 0;

            } catch (error) {
                console.error("Error fetching payroll data:", error);
            } finally {
                loading.value = false;
            }
        };

       const downloadPayslip = async (record) => {
            const token = localStorage.getItem('auth_token');
            downloadLoadingIds.value.push(record.xid);
            try {
                const response = await axios.get(`/api/v1/payroll_new/${record.xid}/download`, {
                headers: {
                    Authorization: `Bearer ${token}`,
                },
                responseType: 'blob',
                });

                const blob = new Blob([response.data], { type: 'application/pdf' });
                const url = window.URL.createObjectURL(blob);

                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', `payslip-${record.employee.name}.pdf`);
                document.body.appendChild(link);
                link.click();

                window.URL.revokeObjectURL(url);
                document.body.removeChild(link);
            } catch (error) {
                console.error("Error downloading payslip:", error);
            } finally {
                downloadLoadingIds.value = downloadLoadingIds.value.filter(
                (x) => x !== record.xid
                );
            }
        };

        const handleTableChange = (pag) => {
            pagination.value = pag;
            fetchPayrollData();
        };

        // Fetch initial data
        fetchPayrollData();

        return {
            columns,
            payrollData,
            loading,
            pagination,
            filters,
            formatCurrency,
            getMonthName,
            downloadPayslip,
            downloadLoadingIds,
            handleTableChange,
            fetchPayrollData,
        };
    },
};
</script>

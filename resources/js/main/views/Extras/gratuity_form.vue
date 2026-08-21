<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Gratuity Form" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>reports</a-breadcrumb-item>
                <a-breadcrumb-item>Gratuity Form</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- ===== Top: select resigned employee + generate ===== -->
    <a-card class="page-content-container">
        <div class="payroll-form-wrapper">
            <div class="payroll-form-box">
                <a-form layout="vertical">
                    <a-row justify="center">
                        <a-col :xs="24" :sm="18" :md="14">
                            <a-form-item label="Select Employee (Resigned)" name="employee_id">
                                <a-select
                                    v-model:value="formData.employee_id"
                                    placeholder="Select resigned employee"
                                    show-search
                                    allow-clear
                                    style="width: 100%"
                                    :loading="employeesLoading"
                                    optionFilterProp="title"
                                    :not-found-content="employeesLoading ? undefined : 'No resigned employees found'"
                                >
                                    <a-select-option
                                        v-for="emp in employees"
                                        :key="emp.xid"
                                        :value="emp.xid"
                                        :title="`${emp.name} ${emp.employee_number || ''}`"
                                    >
                                        {{ emp.name }}
                                        <span v-if="emp.employee_number" class="emp-no">({{ emp.employee_number }})</span>
                                    </a-select-option>
                                </a-select>
                            </a-form-item>

                            <div v-if="selectedEmployee" class="emp-meta">
                                <span>Joined: <strong>{{ fmtDate(selectedEmployee.joining_date) }}</strong></span>
                                <span>Resigned: <strong>{{ fmtDate(selectedEmployee.resignation_date) }}</strong></span>
                            </div>

                            <div class="text-center mt-3">
                                <a-button type="primary" :loading="generating" @click="generateForm" style="width: 100%">
                                    <template #icon>
                                        <template v-if="!generating">
                                            <FilePdfOutlined />
                                        </template>
                                    </template>
                                    Generate &amp; Download Gratuity Form
                                </a-button>
                            </div>
                        </a-col>
                    </a-row>
                </a-form>
            </div>
        </div>
    </a-card>

    <!-- ===== Bottom: history of generated forms ===== -->
    <admin-page-table-content>
        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :columns="columns"
                        :row-key="(record) => record.xid"
                        :data-source="forms"
                        :pagination="pagination"
                        :loading="tableLoading"
                        @change="handleTableChange"
                        bordered
                        size="middle"
                    >
                        <template #bodyCell="{ column, record, index }">
                            <template v-if="column.dataIndex === 'sno'">
                                {{ (pagination.current - 1) * pagination.pageSize + index + 1 }}
                            </template>
                            <template v-if="column.dataIndex === 'employee'">
                                {{ record.employee?.name }}
                            </template>
                            <template v-if="column.dataIndex === 'employee_number'">
                                {{ record.employee?.employee_number || '-' }}
                            </template>
                            <template v-if="column.dataIndex === 'date_of_joining'">
                                {{ fmtDate(record.date_of_joining) }}
                            </template>
                            <template v-if="column.dataIndex === 'exit_date'">
                                {{ fmtDate(record.exit_date) }}
                            </template>
                            <template v-if="column.dataIndex === 'salary_on_exit'">
                                {{ fmtAmount(record.salary_on_exit) }}
                            </template>
                            <template v-if="column.dataIndex === 'gratuity_amount'">
                                {{ fmtAmount(record.gratuity_amount) }}
                            </template>
                            <template v-if="column.dataIndex === 'created_at'">
                                {{ record.created_at_formatted }}
                            </template>
                            <template v-if="column.dataIndex === 'action'">
                                <a-space>
                                    <a-button
                                        type="primary"
                                        size="small"
                                        :loading="downloadLoadingIds.includes(record.xid)"
                                        @click="downloadForm(record)"
                                    >
                                        <template #icon>
                                            <template v-if="!downloadLoadingIds.includes(record.xid)">
                                                <DownloadOutlined />
                                            </template>
                                        </template>
                                        Download
                                    </a-button>
                                    <a-popconfirm
                                        title="Delete this generated form?"
                                        ok-text="Yes"
                                        cancel-text="No"
                                        @confirm="deleteForm(record)"
                                    >
                                        <a-button type="primary" danger size="small">
                                            <template #icon><DeleteOutlined /></template>
                                        </a-button>
                                    </a-popconfirm>
                                </a-space>
                            </template>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
</template>

<script>
import { ref, computed, onMounted } from "vue";
import { message } from "ant-design-vue";
import axios from "axios";
import dayjs from "dayjs";
import { DownloadOutlined, DeleteOutlined, FilePdfOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "../../../common/layouts/AdminPageHeader.vue";

export default {
    components: {
        AdminPageHeader,
        DownloadOutlined,
        DeleteOutlined,
        FilePdfOutlined,
    },
    setup() {
        const API = "/api/v1/gratuity-forms";
        const authHeaders = () => ({
            Authorization: `Bearer ${localStorage.getItem("auth_token")}`,
        });

        /* ---------- employee select ---------- */
        const employees = ref([]);
        const employeesLoading = ref(false);
        const formData = ref({ employee_id: null });
        const selectedEmployee = computed(() =>
            employees.value.find((e) => e.xid === formData.value.employee_id) || null
        );

        const fetchEmployees = async () => {
            employeesLoading.value = true;
            try {
                const res = await axios.get(`${API}/resigned-employees`, { headers: authHeaders() });
                employees.value = res.data.data || [];
            } catch (error) {
                console.error("Error loading resigned employees:", error);
                message.error("Failed to load resigned employees.");
            } finally {
                employeesLoading.value = false;
            }
        };

        /* ---------- history table ---------- */
        const forms = ref([]);
        const tableLoading = ref(false);
        const pagination = ref({ current: 1, pageSize: 10, total: 0, showSizeChanger: true });

        const columns = [
            { title: "#", dataIndex: "sno", width: 60 },
            { title: "Employee", dataIndex: "employee" },
            { title: "Emp. No", dataIndex: "employee_number" },
            { title: "Date of Joining", dataIndex: "date_of_joining" },
            { title: "Exit Date", dataIndex: "exit_date" },
            { title: "Service", dataIndex: "total_service" },
            { title: "Salary on Exit", dataIndex: "salary_on_exit" },
            { title: "Gratuity Amount", dataIndex: "gratuity_amount" },
            { title: "Generated On", dataIndex: "created_at" },
            { title: "Action", dataIndex: "action", width: 190 },
        ];

        const fetchForms = async (page = 1, pageSize = pagination.value.pageSize) => {
            tableLoading.value = true;
            try {
                const res = await axios.get(API, {
                    params: { page, limit: pageSize },
                    headers: authHeaders(),
                });
                forms.value = res.data.data || [];
                pagination.value = {
                    ...pagination.value,
                    current: res.data.current_page || page,
                    pageSize: res.data.per_page || pageSize,
                    total: res.data.total || 0,
                };
            } catch (error) {
                console.error("Error loading gratuity forms:", error);
                message.error("Failed to load generated forms.");
            } finally {
                tableLoading.value = false;
            }
        };

        const handleTableChange = (pag) => {
            fetchForms(pag.current, pag.pageSize);
        };

        /* ---------- actions ---------- */
        const generating = ref(false);
        const downloadLoadingIds = ref([]);

        const saveBlob = (data, filename) => {
            const blob = new Blob([data], { type: "application/pdf" });
            const url = window.URL.createObjectURL(blob);
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", filename);
            document.body.appendChild(link);
            link.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(link);
        };

        const downloadByXid = async (xid, filename) => {
            const res = await axios.get(`${API}/${xid}/download`, {
                headers: authHeaders(),
                responseType: "blob",
            });
            saveBlob(res.data, filename);
        };

        const generateForm = async () => {
            if (!formData.value.employee_id) {
                message.error("Please select an employee");
                return;
            }
            generating.value = true;
            try {
                const res = await axios.post(
                    `${API}/generate`,
                    { employee_id: formData.value.employee_id },
                    { headers: authHeaders() }
                );
                const form = res.data.data;
                await downloadByXid(form.xid, form.filename);
                message.success(res.data.message || "Gratuity form generated successfully.");
                formData.value.employee_id = null;
                fetchForms(1);
            } catch (error) {
                console.error("Error generating gratuity form:", error);
                message.error(error.response?.data?.message || "Failed to generate gratuity form.");
            } finally {
                generating.value = false;
            }
        };

        const downloadForm = async (record) => {
            downloadLoadingIds.value.push(record.xid);
            try {
                await downloadByXid(record.xid, record.filename);
            } catch (error) {
                console.error("Error downloading gratuity form:", error);
                message.error("Failed to download gratuity form.");
            } finally {
                downloadLoadingIds.value = downloadLoadingIds.value.filter((x) => x !== record.xid);
            }
        };

        const deleteForm = async (record) => {
            try {
                await axios.delete(`${API}/${record.xid}`, { headers: authHeaders() });
                message.success("Gratuity form deleted.");
                const page = forms.value.length === 1 && pagination.value.current > 1
                    ? pagination.value.current - 1
                    : pagination.value.current;
                fetchForms(page);
            } catch (error) {
                console.error("Error deleting gratuity form:", error);
                message.error("Failed to delete gratuity form.");
            }
        };

        /* ---------- formatters ---------- */
        const fmtDate = (v) => (v ? dayjs(v).format("DD-MM-YYYY") : "-");
        const fmtAmount = (v) =>
            v === null || v === undefined ? "-" : Number(v).toLocaleString("en-IN", { maximumFractionDigits: 2 });

        onMounted(() => {
            fetchEmployees();
            fetchForms();
        });

        return {
            employees,
            employeesLoading,
            formData,
            selectedEmployee,
            forms,
            tableLoading,
            pagination,
            columns,
            generating,
            downloadLoadingIds,
            handleTableChange,
            generateForm,
            downloadForm,
            deleteForm,
            fmtDate,
            fmtAmount,
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

.emp-no {
    color: #8c8c8c;
    margin-left: 4px;
}

.emp-meta {
    display: flex;
    justify-content: space-between;
    font-size: 12px;
    color: #595959;
    margin-top: -8px;
    margin-bottom: 8px;
}
</style>

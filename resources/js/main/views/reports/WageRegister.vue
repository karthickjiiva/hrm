<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Wage Register (Form B)" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Reports</a-breadcrumb-item>
                <a-breadcrumb-item>Wage Register</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :span="24">
                <a-space>
                    <span style="font-weight: 500">Select Month:</span>
                    <a-select v-model:value="filters.month" style="width: 150px">
                        <a-select-option v-for="m in monthOptions" :key="m.value" :value="m.value">
                            {{ m.label }}
                        </a-select-option>
                    </a-select>

                    <span style="font-weight: 500">Year:</span>
                    <a-select v-model:value="filters.year" style="width: 120px">
                        <a-select-option v-for="year in yearOptions" :key="year" :value="year">
                            {{ year }}
                        </a-select-option>
                    </a-select>

                    <a-button type="primary" :loading="generating" @click="confirmGenerate">
                        <template #icon><FileAddOutlined /></template>
                        Generate Wage Register
                    </a-button>
                </a-space>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-table 
            :columns="columns" 
            :data-source="history" 
            :loading="loading"
            :pagination="pagination"
            @change="handleTableChange"
            bordered 
            row-key="id"
            size="middle"
        >
            <template #bodyCell="{ column, record }">
                <template v-if="column.dataIndex === 'month'">
                    <span style="font-weight: 600; color: #1890ff;">
                        {{ getMonthName(record.month) }} {{ record.year }}
                    </span>
                </template>

                <template v-if="column.dataIndex === 'created_at'">
                    {{ record.created_at_formatted || record.created_at }}
                </template>

                <template v-if="column.dataIndex === 'action'">
                    <a-space>
                           <a-button type="primary" @click="downloadReport(record)">
            <template #icon><DownloadOutlined /></template>
            Download
          </a-button>
                      
                        <!-- <a-popconfirm title="Delete this record?" @confirm="deleteReport(record.id)">
                            <a-button type="primary" danger size="small">
                                <template #icon><DeleteOutlined /></template>
                            </a-button>
                        </a-popconfirm> -->
                    </a-space>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>
</template>

<script>
import { ref, reactive, onMounted, computed, createVNode } from "vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { DownloadOutlined, FileAddOutlined, ExclamationCircleOutlined, DeleteOutlined } from "@ant-design/icons-vue";
import { Modal, message } from "ant-design-vue";
import axios from "axios";

export default {
    components: { DownloadOutlined, FileAddOutlined, DeleteOutlined, AdminPageHeader },
    setup() {
        const loading = ref(false);
        const generating = ref(false);
        const history = ref([]);
        const filters = reactive({ 
            month: new Date().getMonth() + 1, 
            year: new Date().getFullYear() 
        });
        const pagination = reactive({ current: 1, pageSize: 10, total: 0 });

        const monthOptions = [
            { value: 1, label: 'January' }, { value: 2, label: 'February' },
            { value: 3, label: 'March' }, { value: 4, label: 'April' },
            { value: 5, label: 'May' }, { value: 6, label: 'June' },
            { value: 7, label: 'July' }, { value: 8, label: 'August' },
            { value: 9, label: 'September' }, { value: 10, label: 'October' },
            { value: 11, label: 'November' }, { value: 12, label: 'December' }
        ];

        const columns = [
            { title: "Period", dataIndex: "month" },
            { title: "Filename", dataIndex: "filename" },
            { title: "Generated On", dataIndex: "created_at" },
            { title: "Action", dataIndex: "action", width: "200px" },
        ];

        const yearOptions = computed(() => {
            const current = new Date().getFullYear();
            return Array.from({ length: 5 }, (_, i) => current - i);
        });

        const fetchHistory = async () => {
            loading.value = true;
            try {
                const res = await axios.get(`/api/v1/reports/history/wage_register`, {
                    params: { page: pagination.current, limit: pagination.pageSize }
                });
                history.value = res.data.data;
                pagination.total = res.data.total;
            } catch (err) {
                message.error("Failed to load history");
            } finally {
                loading.value = false;
            }
        };

        const confirmGenerate = () => {
            Modal.confirm({
                title: `Generate Wage Register for ${getMonthName(filters.month)} ${filters.year}?`,
                icon: createVNode(ExclamationCircleOutlined),
                content: "This report includes all earnings, deductions, and attendance details for the selected month.",
                okText: 'Generate',
                async onOk() {
                    await generateReport();
                }
            });
        };

        const generateReport = async () => {
            generating.value = true;
            try {
                const res = await axios.post(`/api/v1/reports/generate/wage_register`, filters);
                message.success(res.data.message);
                fetchHistory();
            } catch (err) {
                message.error(err.response?.data?.message || "Generation failed");
            } finally {
                generating.value = false;
            }
        };

        const deleteReport = async (id) => {
            try {
                await axios.delete(`/api/v1/reports/history/${id}`);
                message.success("Record deleted");
                fetchHistory();
            } catch (err) {
                message.error("Delete failed");
            }
        };

        const downloadReport = (record) => {
            // Using the full_url if you implemented the accessor, 
            // otherwise using a relative path logic.
            const url = record.file_path.startsWith('http') 
                        ? record.file_path 
                        : `https://bc.shakthicorp.com/storage/${record.file_path}`;
                        
            const link = document.createElement("a");
            link.href = url;
            link.setAttribute("download", record.filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        const handleTableChange = (pager) => {
            pagination.current = pager.current;
            fetchHistory();
        };

        const getMonthName = (m) => {
            return monthOptions.find(item => item.value === parseInt(m))?.label || m;
        };

        onMounted(fetchHistory);

        return { 
            filters, 
            yearOptions, 
            monthOptions, 
            history, 
            loading, 
            generating, 
            columns, 
            pagination, 
            handleTableChange, 
            confirmGenerate, 
            downloadReport, 
            getMonthName,
            deleteReport
        };
    }
};
</script>
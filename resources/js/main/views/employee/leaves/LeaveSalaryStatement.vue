<template>
    <AdminPageHeader>
          <template #header>
      <a-page-header title="Leave Salary Statement" class="p-0" />
    </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>reports</a-breadcrumb-item>
                <a-breadcrumb-item>Leave Salary Statement</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]" align="middle">
            <a-col :xs="24" :sm="24" :md="12" :lg="14" :xl="14">
                <a-space>
                    <span style="font-weight: 500">Select Period:</span>
                    <a-select v-model:value="filters.year" style="width: 220px" placeholder="Select Year">
                        <a-select-option v-for="year in yearOptions" :key="year" :value="year">
                            Jan {{ year }} - Dec {{ year }}
                        </a-select-option>
                    </a-select>

                    <a-button type="primary" :loading="generating" @click="generateReport">
                        <template #icon>
                            <FileAddOutlined />
                        </template>
                        Generate Statement
                    </a-button>
                </a-space>
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table :columns="columns" :data-source="history" :pagination="pagination" :loading="loading"
                        @change="handleTableChange" bordered size="middle" row-key="id">
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'created_at'">
                                {{ record.created_at_formatted }}
                            </template>

                            <template v-if="column.dataIndex === 'status'">
                                <a-tag color="success">Completed</a-tag>
                            </template>

                         <template v-if="column.dataIndex === 'action'">
    <a-space size="middle">
        <a-button 
            type="primary" 
            size="large" 
            @click="downloadFile(record, 'salary')"
            class="action-btn"
        >
            <template #icon><DownloadOutlined /></template>
            Salary Statement
        </a-button>

        <a-button 
            size="large" 
            @click="downloadFile(record, 'bank')" 
            class="action-btn bank-btn"
        >
            <template #icon><DownloadOutlined /></template>
            Bank Statement
        </a-button>
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
import { ref, reactive, onMounted, computed } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { DownloadOutlined, FileAddOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { Modal, message } from "ant-design-vue";
import { ExclamationCircleOutlined } from "@ant-design/icons-vue";
import { createVNode } from "vue";

export default {
    components: { AdminPageHeader, DownloadOutlined, FileAddOutlined },
    setup() {
        const { t } = useI18n();
        const loading = ref(false);
        const generating = ref(false);
        const history = ref([]);

        const pagination = reactive({
            current: 1,
            pageSize: 10,
            total: 0
        });

        const filters = reactive({
            year: new Date().getFullYear(),
        });

        const yearOptions = computed(() => {
            const currentYear = new Date().getFullYear();
            const startYear = 2023;
            const years = [];
            for (let y = currentYear + 1; y >= startYear; y--) {
                years.push(y);
            }
            return years;
        });

        const columns = [
            { title: "Statement Period", dataIndex: "period_label" },
            { title: "Generated On", dataIndex: "created_at" },
            { title: "Status", dataIndex: "status" },
            { title: t("common.action"), dataIndex: "action", width: "150px" },
        ];

        const generateReport = () => {
            Modal.confirm({
                title: 'Are you sure you want to generate this statement?',
                icon: createVNode(ExclamationCircleOutlined),
                content: `This action will calculate salaries for ${filters.year} and RESET all Earned and Casual leaves to zero. This cannot be undone.`,
                okText: 'Yes, Generate',
                okType: 'danger',
                cancelText: 'No, Cancel',
                async onOk() {
                    try {
                        generating.value = true;
                        const token = localStorage.getItem("auth_token");

                        const response = await axios.post("/api/v1/leave-salary-statement/generate",
                            { year: filters.year },
                            { headers: { Authorization: `Bearer ${token}` } }
                        );

                        message.success(response.data.message);
                        fetchHistory();
                    } catch (error) {
                        const errorMsg = error.response?.data?.message || "Failed to generate report.";
                        message.error(errorMsg);
                    } finally {
                        generating.value = false;
                    }
                },
                onCancel() {
                    console.log('Generation cancelled');
                },
            });
        };

        const fetchHistory = async () => {
            try {
                loading.value = true;
                const token = localStorage.getItem("auth_token");

                const response = await axios.get("/api/v1/leave-salary-statement/history", {
                    params: {
                        page: pagination.current,
                        limit: pagination.pageSize
                    },
                    headers: { Authorization: `Bearer ${token}` },
                });

                history.value = response.data.data;
                pagination.total = response.data.meta?.paging?.total || 0;
            } catch (error) {
                console.error("Error fetching history:", error);
                message.error("Failed to load report history.");
            } finally {
                loading.value = false;
            }
        };

        const generateReportxx = async () => {
            try {
                generating.value = true;
                const token = localStorage.getItem("auth_token");

                await axios.post("/api/v1/leave-salary-statement/generate",
                    { year: filters.year },
                    { headers: { Authorization: `Bearer ${token}` } }
                );

                message.success(`Report generation for ${filters.year} successful!`);
                fetchHistory();
            } catch (error) {
                const errorMsg = error.response?.data?.message || "Failed to generate report.";
                message.error(errorMsg);
            } finally {
                generating.value = false;
            }
        };

        const downloadFilexx = (record) => {
            if (!record.download_url) {
                message.error("Download URL not found.");
                return;
            }
            const link = document.createElement("a");
            link.href = record.download_url;
            link.setAttribute("download", record.filename);
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        };

        const downloadFile = (record, type) => { 
    const fileUrl = type === 'bank' ? record.bank_download_url : record.download_url;
    const fileName = type === 'bank' ? record.bank_filename : record.filename;

    if (!fileUrl) {
        message.error(`${type === 'bank' ? 'Bank' : 'Salary'} file not found.`);
        return;
    }

    const link = document.createElement("a");
    link.href = fileUrl;
    link.setAttribute("download", fileName);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
};

        const handleTableChange = (pager) => {
            pagination.current = pager.current;
            pagination.pageSize = pager.pageSize;
            fetchHistory();
        };

        onMounted(() => {
            fetchHistory();
        });

        return {
            t,
            loading,
            generating,
            history,
            pagination,
            filters,
            columns,
            yearOptions,
            handleTableChange,
            generateReport,
            downloadFile,
        };
    },
};
</script>
<style scoped>
.action-btn {
    border-radius: 6px;
    display: flex;
    align-items: center;
    transition: all 0.3s ease;
}

/* Specific styling for the Bank Button */
.bank-btn {
    background-color: #2e7d32; /* Deep green */
    color: white;
    border: none;
}

.bank-btn:hover {
    background-color: #1b5e20;
    color: white;
    box-shadow: 0 4px 10px rgba(46, 125, 50, 0.3);
}

.action-btn:hover {
    transform: translateY(-1px);
}
</style>
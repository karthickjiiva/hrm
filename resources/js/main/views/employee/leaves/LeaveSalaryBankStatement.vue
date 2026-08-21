<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Leave Salary Bank Statement" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t("menu.dashboard") }}
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>reports</a-breadcrumb-item>
                <a-breadcrumb-item>Leave Salary Bank Statement</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-table-content>
        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table 
                        :columns="columns" 
                        :data-source="history" 
                        :pagination="pagination" 
                        :loading="loading"
                        @change="handleTableChange" 
                        bordered 
                        size="middle" 
                        row-key="id"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.dataIndex === 'created_at'">
                                {{ record.created_at_formatted }}
                            </template>

                            <template v-if="column.dataIndex === 'action'">
                                <a-button type="primary" size="small" @click="downloadFile(record)">
                                    <template #icon><DownloadOutlined /></template>
                                    {{ $t("common.download") }}
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
import { ref, reactive, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import axios from "axios";
import { DownloadOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import { message } from "ant-design-vue";

export default {
    components: { AdminPageHeader, DownloadOutlined },
    setup() {
        const { t } = useI18n();
        const loading = ref(false);
        const history = ref([]);

        const pagination = reactive({
            current: 1,
            pageSize: 10,
            total: 0
        });

        const columns = [
            { title: "Statement Name", dataIndex: "filename" }, // or period_label depending on API
            { title: "Generated On", dataIndex: "created_at" },
            { title: t("common.action"), dataIndex: "action", width: "150px" },
        ];

        const fetchHistory = async () => {
            try {
                loading.value = true;
                const token = localStorage.getItem("auth_token");

                const response = await axios.get("/api/v1/leave-salary-bank-statement/history", {
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
                message.error("Failed to load bank statements.");
            } finally {
                loading.value = false;
            }
        };

        const downloadFile = (record) => {
            if (!record.download_url) {
                message.error("Download URL not found.");
                return;
            }
            window.open(record.download_url, '_blank');
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
            loading,
            history,
            pagination,
            columns,
            handleTableChange,
            downloadFile,
        };
    },
};
</script>
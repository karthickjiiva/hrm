<template>
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Rejoin Employee" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        Dashboard
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>Rejoin Employee</a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="6">
                <a-button type="primary" @click="addItem">
                    <PlusOutlined />
                    Add Rejoin Record
                </a-button>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="18">
                <a-input-search
                    v-model:value="table.searchString"
                    show-search
                    :allowClear="true"
                    @change="onTableSearch"
                    @search="onTableSearch"
                    :loading="table.filterLoading"
                    placeholder="Search by Employee"
                    style="width: 100%"
                />
            </a-col>
        </a-row>
    </admin-page-filters>

    <admin-page-table-content>
        <AddEdit
            :addEditType="addEditType"
            :visible="addEditVisible"
            :url="addEditUrl"
            @addEditSuccess="addEditSuccess"
            @closed="onCloseAddEdit"
            :formData="formData"
            :data="viewData"
            :pageTitle="pageTitle"
            :successMessage="successMessage"
        />

        <a-table
            :columns="columns"
            :row-key="(record) => record.xid"
            :data-source="table.data"
            :pagination="table.pagination"
            :loading="table.loading"
            @change="handleTableChange"
            bordered
            size="middle"
        >
            <template #bodyCell="{ column, text, record }">
                <template v-if="column.dataIndex === 'user_id'">
                    <UserInfo :user="record.user" />
                </template>
                <template
                    v-if="
                        ['resignated_date', 'rejoined_date'].includes(
                            column.dataIndex
                        )
                    "
                >
                    {{ formatDateTime(text) }}
                </template>
                <template v-if="column.dataIndex === 'action'">
                    <a-button type="primary" @click="editItem(record)">
                        Edit
                    </a-button>
                    <a-button danger @click="showDeleteConfirm(record.xid)">
                        Delete
                    </a-button>
                </template>
            </template>
        </a-table>
    </admin-page-table-content>
</template>

<script>
import { onMounted } from "vue";
import {
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
} from "@ant-design/icons-vue";
import fields from "./fields";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import AddEdit from "./AddEdit.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import UserInfo from "../../../../common/components/user/UserInfo.vue";

export default {
    components: {
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        AddEdit,
        AdminPageHeader,
        UserInfo,
    },
    setup() {
        const {
            url,
            addEditUrl,
            initData,
            columns,
            filterableColumns,
            hashableColumns,
        } = fields();

        const crudVariables = crud();
        const { formatDateTime } = common();

        onMounted(() => {
            crudVariables.tableUrl.value = { url };
            crudVariables.table.filterableColumns = filterableColumns;
            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "rejoining";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
            crudVariables.hashableColumns.value = [...hashableColumns];
            crudVariables.fetch({ page: 1 });
        });

        return {
            columns,
            formatDateTime,
            ...crudVariables,
        };
    },
};
</script>

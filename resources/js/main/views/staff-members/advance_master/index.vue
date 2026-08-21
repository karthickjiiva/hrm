<template>
    <AdminPageHeader>
        <template #header>
            <!-- <a-page-header :title="$t(`menu.users`)" class="p-0" /> -->
            <a-page-header title="Advance Master" class="p-0" />
        </template>
        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        {{ $t(`menu.dashboard`) }}
                    </router-link>
                </a-breadcrumb-item>
                <!-- <a-breadcrumb-item>
                    {{ $t(`menu.users`) }}
                </a-breadcrumb-item> -->
                <a-breadcrumb-item> 
                Advance Master
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <admin-page-filters>
        <a-row :gutter="[16, 16]">
            <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                <a-space>
                    <template
                        v-if="
                            permsArray.includes('users_create') ||
                            permsArray.includes('admin')
                        "
                    >
                        <a-space>
                            <a-button type="primary" @click="addItem">
                                <PlusOutlined />
                                <!-- {{ $t("user.add") }} -->
                                Add New
                            </a-button>
                            <!-- <a-button type="primary" @click="addItems">
                                <PlusOutlined />
                                {{ $t("user.quick_add") }}
                            </a-button> -->
                        </a-space>
                    </template>
                    <a-button
                        v-if="
                            table.selectedRowKeys.length > 0 &&
                            (permsArray.includes(`${userType}_delete`) ||
                                permsArray.includes('admin'))
                        "
                        type="primary"
                        @click="showSelectedDeleteConfirm"
                        danger
                    >
                        <template #icon><DeleteOutlined /></template>
                        {{ $t("common.delete") }}
                    </a-button>
                </a-space>
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="18" :xl="18">
                <a-row :gutter="[16, 16]" justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="6" :xl="6">
                        <!-- <a-input-search
                            style="width: 100%"
                            v-model:value="table.searchString"
                            show-search
                            :allowClear="true"
                            @change="onTableSearch"
                            @search="onTableSearch"
                            :loading="table.filterLoading"
                            placeholder="Search By Employee Name"
                        /> -->
                    </a-col>
                </a-row>
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
            @addListSuccess="reSetFormData"
        />

        <a-row>
            <a-col :span="24">
                <a-tabs
                    v-model:activeKey="all"
                    @change="setUrlData"
                >
                    <a-tab-pane key="all" :tab="`${$t('common.all')}`" />
                    <!-- <a-tab-pane key="active" :tab="`${$t('common.active')}`" />
                    <a-tab-pane
                        key="inactive"
                        :tab="`${$t('common.inactive')}`"
                    /> -->
                </a-tabs>
            </a-col>
        </a-row>

        <a-row>
            <a-col :span="24">
                <div class="table-responsive">
                    <a-table
                        :row-selection="{
                            selectedRowKeys: table.selectedRowKeys,
                            onChange: onRowSelectChange,
                            getCheckboxProps: (record) => ({
                                disabled: user.xid == record.xid ? true : false,
                                name: record.xid,
                            }),
                        }"
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
                            <template v-if="column.dataIndex === 'name'">
                                <a-button
                                    type="link"
                                    @click="openUserView(record)"
                                >
                                    <user-info :user="record" />
                                </a-button>
                            </template>
                            <template v-if="column.dataIndex === 'status'">
                                <a-tag :color="userStatusColors[text]">
                                    {{ $t(`common.${text}`) }}
                                </a-tag>
                            </template>
                            <template v-if="column.dataIndex === 'created_at'">
                                {{ formatDateTime(record.created_at) }}
                            </template>
                            <template v-if="column.dataIndex === 'action'">            
                                <a-button
                                    v-if="permsArray.includes('admin')"
                                    type="primary"
                                    @click="editItem(record)"
                                    style="margin-left: 4px"
                                >
                                    <template #icon><EditOutlined /></template>
                                </a-button>
                                <a-button
                                    v-if="
                                        permsArray.includes('users_delete') ||
                                        permsArray.includes('admin')
                                    "
                                    type="primary"
                                    @click="showDeleteConfirm(record.xid)"
                                    style="margin-left: 4px"
                                >
                                    <template #icon
                                        ><DeleteOutlined
                                    /></template>
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </div>
            </a-col>
        </a-row>
    </admin-page-table-content>
    <user-view-page :visible="userOpen" :userId="userId" @closed="closeUser" />
    <!-- <ViewVue
        :user="viewData"
        :visible="detailsVisible"
        @closed="onCloseDetails"
    /> -->
   <RepaymentModal
  :visible="isRepaymentModalVisible"
  :loan-xid="selectedLoanXid"
  @update:visible="isRepaymentModalVisible = $event"
  @updated="fetchData"
/>
</template>
<script>
import { onMounted, ref } from "vue";
import {
    PlusOutlined,
    EditOutlined,
    DeleteOutlined,
    EyeOutlined,
} from "@ant-design/icons-vue";
import fields from "./fields";
import crud from "../../../../common/composable/crud";
import common from "../../../../common/composable/common";
import AddEdit from "./AddEdit.vue";
import AdminPageHeader from "../../../../common/layouts/AdminPageHeader.vue";
import ImportUsers from "../../../../common/core/ui/Import.vue";
import UserInfo from "../../../../common/components/user/UserInfo.vue";
import RepaymentModal from "./RepaymentModal.vue";
import UserListDisplayVue from "@/common/components/user/UserListDisplay.vue";

export default {
    components: {
        PlusOutlined,
        EditOutlined,
        DeleteOutlined,
        EyeOutlined,
        UserListDisplayVue,
        AddEdit,
        RepaymentModal,
        AdminPageHeader,
        ImportUsers,
        UserInfo,
        // ViewVue,
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
        const { permsArray, userStatusColors, formatDateTime, user } = common();
        const sampleFileUrl = window.config.staff_member_sample_file;
        const extraFilters = ref({
            status: "active",
        });
        const detailsVisibles = ref(false);
        const userOpen = ref(false);
        const userId = ref(undefined);

        const { fetch } = crud();  

     function fetchData() {
    setUrlData();       
}


        const isRepaymentModalVisible = ref(false);
const selectedLoanXid = ref(null);

function openRepaymentModal(xid) { 
  selectedLoanXid.value = xid;
  isRepaymentModalVisible.value = true;
}

        const openUserView = (item) => {
            userId.value = item.xid;
            userOpen.value = true;
        };

        const closeUser = () => {
            crudVariables.viewData.value = {};
            userOpen.value = false;
        };

     

        onMounted(() => {
            setUrlData();
        });

        const addItems = () => {
            detailsVisibles.value = true;
        };

        const setUrlData = () => {
            detailsVisibles.value = false;
            crudVariables.tableUrl.value = {
                url: url,
                extraFilters,
            };
            crudVariables.table.filterableColumns = filterableColumns;

            crudVariables.fetch({
                page: 1,
            });

            crudVariables.crudUrl.value = addEditUrl;
            crudVariables.langKey.value = "user";
            crudVariables.initData.value = { ...initData };
            crudVariables.formData.value = { ...initData };
            crudVariables.hashableColumns.value = { ...hashableColumns };
        };

        return {
            columns,
            filterableColumns,
            permsArray,
            userStatusColors,
            formatDateTime,
            ...crudVariables,
            sampleFileUrl,
            setUrlData,
            user,
            selectedLoanXid,
            isRepaymentModalVisible,
            openRepaymentModal,
            extraFilters,
            fetchData,
            addItems,
            detailsVisibles,
            openUserView,
            closeUser,
            userOpen,
            userId,
        };
    },
};
</script>

<template>
    <!-- Page Header -->
    <AdminPageHeader>
        <template #header>
            <a-page-header title="Employee Insurance Details" class="p-0" />
        </template>

        <template #breadcrumb>
            <a-breadcrumb separator="-" style="font-size: 12px">
                <a-breadcrumb-item>
                    <router-link :to="{ name: 'admin.dashboard.index' }">
                        Dashboard
                    </router-link>
                </a-breadcrumb-item>
                <a-breadcrumb-item>
                    Employee Insurance
                </a-breadcrumb-item>
            </a-breadcrumb>
        </template>
    </AdminPageHeader>

    <!-- Filters -->
    <admin-page-filters>
        <a-row :gutter="[16,16]">
            <!-- Left Buttons -->
            <a-col :xs="24" :sm="24" :md="12" :lg="6">
                <a-space>
                    <a-button type="primary" @click="addItem">
                        <PlusOutlined />
                        Add New
                    </a-button>

                     <a-button type="primary" :loading="generating" @click="generateReport">
                <template #icon><FileAddOutlined /></template>
                Generate Report
              </a-button>
                </a-space>
            </a-col>

            <!-- Search -->
            <a-col :xs="24" :sm="24" :md="12" :lg="18">
                <a-row justify="end">
                    <a-col :xs="24" :sm="24" :md="12" :lg="8">
                        <a-input-search
                            v-model:value="table.searchString"
                            placeholder="Search employee..."
                            allow-clear
                            @search="onTableSearch"
                            @change="onTableSearch"
                        />
                    </a-col>
                </a-row>
            </a-col>
        </a-row>
    </admin-page-filters>

    <!-- Table Content -->
    <admin-page-table-content>

        <!-- Add/Edit Drawer -->
        <AddEdit
            :visible="addEditVisible"
            :addEditType="addEditType"
            :url="addEditUrl"
            :formData="formData"
            :data="viewData"
            @addEditSuccess="addEditSuccess"
            @closed="onCloseAddEdit"
        />

        <a-table
            :columns="columns"
            :row-key="record => record.id"
            :data-source="table.data"
            :pagination="table.pagination"
            :loading="table.loading"
            @change="handleTableChange"
            bordered
            size="middle"
        >
            <template #bodyCell="{ column, record }">

                <!-- Employee Name -->
                <template v-if="column.dataIndex === 'user'">
                    {{ record.user ? record.user.name : '-' }}
                </template>

                <!-- Actions -->
             <template v-if="column.dataIndex === 'action'">

  <a-button
    type="primary"
    size="medium"
    @click="editItem(record)"
    style="margin-left: 4px"
  >
    <template #icon>
      <EditOutlined />
    </template>
  </a-button>

  <a-button
    type="primary"
    size="medium"
    @click="deleteItem(record.id)"
    style="margin-left: 4px"
  >
    <template #icon>
      <DeleteOutlined />
    </template>
  </a-button>

</template>

            </template>
        </a-table>

    </admin-page-table-content>
</template>

<script>
import { ref, reactive, onMounted } from "vue";
import axios from "axios";
import { PlusOutlined, EditOutlined, DeleteOutlined, FileAddOutlined } from "@ant-design/icons-vue";
import AdminPageHeader from "@/common/layouts/AdminPageHeader.vue";
import AddEdit from "./AddEdit.vue";
import { Modal, message } from "ant-design-vue";

export default {
    components: { PlusOutlined, EditOutlined, DeleteOutlined,FileAddOutlined, AdminPageHeader, AddEdit },
    
    setup() {
        const table = reactive({
            data: [],
            loading: false,
            searchString: "",
            pagination: {
                current: 1,
                pageSize: 10,
                total: 0,
            }
        });

        const generating = ref(false);
        const addEditVisible = ref(false);
        const addEditType = ref("add");
        const formData = ref({});
        const viewData = ref({});
        const addEditUrl = "/api/v1/add-emp-insurances";

        // Define Columns properly
        const columns = [
            { title: "Employee", dataIndex: "user", key: "user" },
            { title: "Spouse Name", dataIndex: "spouse_name" },
            { title: "Father Name", dataIndex: "father_name" },
            { title: "Nominee", dataIndex: "nominee_name" },
            { title: "Nominee Relation", dataIndex: "nominee_relation" },
            { title: "Action", dataIndex: "action" }
        ];

        const fetchData = async () => {
            table.loading = true;
            try {
                const res = await axios.get("/api/v1/employee-insurances", {
                    params: {
                        page: table.pagination.current,
                        search: table.searchString
                    }
                });
                
                // Laravel response has the array in res.data.data
                table.data = res.data.data; 
                table.pagination.total = res.data.total;
            } catch (error) {
                console.error("Fetch error:", error);
            } finally {
                // Ensure loading is set to false so the table renders
                table.loading = false;
            }
        };

        const addItem = () => {
            addEditType.value = "add";
            formData.value = { 
                user_id: null, 
                spouse_name: "",
                father_name: "",
                nominee_name: "" 
            }; 
            addEditVisible.value = true;
        };

        const editItem = (record) => {
            addEditType.value = "edit";
            formData.value = { ...record };
            addEditVisible.value = true;
        };

        const deleteItem = (id) => {
  Modal.confirm({
    title: "Are you sure you want to delete this record?",
    okText: "Yes",
    cancelText: "No",
    okType: "danger",
    async onOk() {
      try {
        await axios.delete(
          `/api/v1/employee-insurances/${id}`
        );

        message.success("Deleted successfully");

     fetchData();

      } catch (error) {
        message.error("Delete failed");
      }
    }
  });
};
 

        const handleTableChange = (pagination) => {
            table.pagination.current = pagination.current;
            fetchData();
        };

        const onTableSearch = () => {
            table.pagination.current = 1;
            fetchData();
        };

      const generateReport = async () => {
  generating.value = true;

  try {
    const res = await axios.post(
      "/api/v1/employee-insurances/generate-report",      
    );

    if (res.data.success) {
      const link = document.createElement("a");
      link.href = res.data.download_url;
      link.setAttribute("download", res.data.filename);
      document.body.appendChild(link);
      link.click();
      document.body.removeChild(link);

      message.success(res.data.message || "Report generated successfully");
    } else {
      message.error("Report generation failed");
    }

  } catch (err) {
    message.error(
      err.response?.data?.message || "Report generation failed"
    );
  } finally {
    generating.value = false;
  }
};

        onMounted(fetchData);

        return {
            table,
            columns, // Required for the table to render
            addEditVisible,
            addEditType,
            formData,
            viewData,
            addItem,
            editItem,
            deleteItem,
            handleTableChange,
            onTableSearch,
            addEditSuccess: fetchData,
            onCloseAddEdit: () => { addEditVisible.value = false; },
            generateReport,
            generating,
            addEditUrl
        };
    }
};
</script>
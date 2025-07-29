<template>
    <a-drawer
        title="Edit Employee Type"
        :width="drawerWidth"
        :open="visible"
        :body-style="{ paddingBottom: '80px' }"
        :footer-style="{ textAlign: 'right' }"
        :maskClosable="false"
        @close="onClose"
    >
        <a-form layout="vertical" id="add_edit_user_form">
            <a-tabs v-model:activeKey="addEditActiveTab" @change="onTabChange">
                <a-tab-pane key="basic" tab="Employee Type">
                    <a-row>
                        <a-col :xs="24" :sm="24" :md="18" :lg="18">
                            <a-row :gutter="16">
                                <a-col :xs="24" :sm="24" :md="12" :lg="12">
                                    <a-form-item
                                        label="Type"
                                        name="type"
                                        :help="
                                            rules.type
                                                ? rules.type.message
                                                : null
                                        "
                                        :validateStatus="
                                            rules.type ? 'error' : null
                                        "
                                        class="required"
                                    >
                                        <a-input
                                            v-model:value="formData.type"
                                            placeholder="Enter the employee type"
                                        />
                                    </a-form-item>
                                </a-col>
                            </a-row>
                        </a-col>
                    </a-row>
                </a-tab-pane>
                <a-tab-pane key="personal" tab="Salary (Earnings)" force-render>
                    <a-row :gutter="16">
                        <a-col :xs="24" :sm="24" :md="12" :lg="12">
                            <a-form-item
                                label="Basic %"
                                name="basic_percent"
                                :help="
                                    rules.basic_percent
                                        ? rules.basic_percent.message
                                        : null
                                "
                                :validateStatus="
                                    rules.basic_percent ? 'error' : null
                                "
                                class="required"
                            >
                                <a-input-number
                                    v-model:value="formData.basic_percent"
                                    :min="0"
                                    :max="100"
                                    :formatter="(value) => `${value}`"
                                    :parser="(value) => value.replace('%', '')"
                                    style="width: 100%"
                                />
                            </a-form-item>
                        </a-col>

                        <a-col :xs="24" :sm="24" :md="12" :lg="12">
                            <a-form-item
                                label="HRA %"
                                name="hra_percent"
                                :help="
                                    rules.hra_percent
                                        ? rules.hra_percent.message
                                        : null
                                "
                                :validateStatus="
                                    rules.hra_percent ? 'error' : null
                                "
                                class="required"
                            >
                                <a-input-number
                                    v-model:value="formData.hra_percent"
                                    :min="0"
                                    :max="100"
                                    :formatter="(value) => `${value}`"
                                    :parser="(value) => value.replace('%', '')"
                                    style="width: 100%"
                                />
                            </a-form-item>
                        </a-col>
                    </a-row>

                    <a-row :gutter="16">
                        <a-col :xs="24" :sm="24" :md="12" :lg="12">
                            <a-form-item
                                label="Allowance %"
                                name="allowance_percent"
                                :help="
                                    rules.allowance_percent
                                        ? rules.allowance_percent.message
                                        : null
                                "
                                :validateStatus="
                                    rules.allowance_percent ? 'error' : null
                                "
                                class="required"
                            >
                                <a-input-number
                                    v-model:value="formData.allowance_percent"
                                    :min="0"
                                    :max="100"
                                    :formatter="(value) => `${value}`"
                                    :parser="(value) => value.replace('%', '')"
                                    style="width: 100%"
                                />
                            </a-form-item>
                        </a-col>

                        <a-col :xs="24" :sm="24" :md="12" :lg="12">
                            <a-form-item
                                label="Food Allowance %"
                                name="food_allowance_percent"
                                :help="
                                    rules.food_allowance_percent
                                        ? rules.food_allowance_percent.message
                                        : null
                                "
                                :validateStatus="
                                    rules.food_allowance_percent
                                        ? 'error'
                                        : null
                                "
                                class="required"
                            >
                                <a-input-number
                                    v-model:value="
                                        formData.food_allowance_percent
                                    "
                                    :min="0"
                                    :max="100"
                                    :formatter="(value) => `${value}`"
                                    :parser="(value) => value.replace('%', '')"
                                    style="width: 100%"
                                />
                            </a-form-item>
                        </a-col>
                    </a-row>
                </a-tab-pane>
                <a-tab-pane
                    key="contribution"
                    tab="Deduction (Contribution)"
                    force-render
                >
                <a-row :gutter="16">
    <!-- Provident Fund (PF) -->
    <a-col :xs="24" :sm="24" :md="8" :lg="4">
        <a-form-item label="Enable PF" name="pf_enabled">
            <a-switch
                v-model:checked="formData.pf_enabled"
                :checkedValue="true"
                :unCheckedValue="false"
            />
        </a-form-item>
    </a-col>

    <template v-if="formData.pf_enabled">
        <!-- Fix Amount Toggle -->
        <a-col :xs="24" :sm="24" :md="8" :lg="4">
            <a-form-item label="Fix Amount" name="pf_fix_amount">
                <a-switch
                    v-model:checked="formData.pf_fix_amount"
                    :checkedValue="true"
                    :unCheckedValue="false"
                />
            </a-form-item>
        </a-col>

        <!-- Conditional Input: Percentage OR Amount -->
 <a-col :xs="24" :sm="24" :md="8" :lg="8">
    <a-form-item
        :label="formData.pf_fix_amount ? 'PF Amount' : 'PF Percentage'"
        name="pf_percentage"
    >
        <a-input-number
            :key="formData.pf_fix_amount ? 'pf-amount' : 'pf-percentage'"
            v-model:value="formData.pf_percentage"
            :min="0"
            :max="formData.pf_fix_amount ? undefined : 100"
            :formatter="formData.pf_fix_amount ? undefined : (v => `${v}%`)"
            :parser="formData.pf_fix_amount ? undefined : (v => v.replace('%', ''))"
            style="width: 100%"
        />
    </a-form-item>
</a-col>


        <!-- PF Limit -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="PF Limit" name="pf_limit">
                <a-input-number
                    v-model:value="formData.pf_limit"
                    :min="0"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>

        <!-- PF Pension Scheme -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="PF Pension Scheme" name="pf_pension_scheme">
                <a-checkbox
                    v-model:checked="formData.pf_pension_scheme"
                    :checkedValue="true"
                    :unCheckedValue="false"
                >
                    Enable Pension Scheme
                </a-checkbox>
            </a-form-item>
        </a-col>
    </template>
</a-row>


                <!-- ESI -->
<a-row :gutter="16">
    <a-col :xs="24" :sm="24" :md="8" :lg="4">
        <a-form-item label="Enable ESI" name="esi_enabled">
            <a-switch
                v-model:checked="formData.esi_enabled"
                :checkedValue="true"
                :unCheckedValue="false"
            />
        </a-form-item>
    </a-col>

    <template v-if="formData.esi_enabled">
        <!-- ESI Fix Amount Toggle -->
        <a-col :xs="24" :sm="24" :md="8" :lg="4">
            <a-form-item label="Fix Amount" name="esi_fix_amount">
                <a-switch
                    v-model:checked="formData.esi_fix_amount"
                    :checkedValue="true"
                    :unCheckedValue="false"
                />
            </a-form-item>
        </a-col>

        <!-- ESI Percentage or Amount -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item
                :label="formData.esi_fix_amount ? 'ESI Amount' : 'ESI Percentage'"
                name="esi_percentage"
            >
                <a-input-number
                    :key="formData.esi_fix_amount ? 'esi-amount' : 'esi-percentage'"
                    v-model:value="formData.esi_percentage"
                    :min="0"
                    :max="formData.esi_fix_amount ? undefined : 100"
                    :formatter="formData.esi_fix_amount ? undefined : (v => `${v}%`)"
                    :parser="formData.esi_fix_amount ? undefined : (v => v.replace('%', ''))"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>

        <!-- ESI Limit -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="ESI Limit" name="esi_limit">
                <a-input-number
                    v-model:value="formData.esi_limit"
                    :min="0"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>
    </template>
</a-row>

                    <!-- TDS -->
<a-row :gutter="16">
    <a-col :xs="24" :sm="24" :md="8" :lg="4">
        <a-form-item label="Enable TDS" name="tds_enabled">
            <a-switch
                v-model:checked="formData.tds_enabled"
                :checkedValue="true"
                :unCheckedValue="false"
            />
        </a-form-item>
    </a-col>

    <template v-if="formData.tds_enabled">
        <!-- TDS Fix Amount Toggle -->
        <a-col :xs="24" :sm="24" :md="8" :lg="4">
            <a-form-item label="Fix Amount" name="tds_fix_amount">
                <a-switch
                    v-model:checked="formData.tds_fix_amount"
                    :checkedValue="true"
                    :unCheckedValue="false"
                />
            </a-form-item>
        </a-col>

        <!-- TDS Percentage or Amount -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item
                :label="formData.tds_fix_amount ? 'TDS Amount' : 'TDS Percentage'"
                name="tds_percentage"
            >
                <a-input-number
                    :key="formData.tds_fix_amount ? 'tds-amount' : 'tds-percentage'"
                    v-model:value="formData.tds_percentage"
                    :min="0"
                    :max="formData.tds_fix_amount ? undefined : 100"
                    :formatter="formData.tds_fix_amount ? undefined : (v => `${v}%`)"
                    :parser="formData.tds_fix_amount ? undefined : (v => v.replace('%', ''))"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>

        <!-- TDS Limit -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="TDS Limit" name="tds_limit">
                <a-input-number
                    v-model:value="formData.tds_limit"
                    :min="0"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>
    </template>
</a-row>

                  
<!-- Professional Tax -->
<a-row :gutter="16">
    <a-col :xs="24" :sm="24" :md="8" :lg="4">
        <a-form-item label="Enable Prof. Tax" name="prof_tax_enabled">
            <a-switch
                v-model:checked="formData.prof_tax_enabled"
                :checkedValue="true"
                :unCheckedValue="false"
            />
        </a-form-item>
    </a-col>

    <template v-if="formData.prof_tax_enabled">
        <!-- Prof. Tax Fix Amount Toggle -->
        <a-col :xs="24" :sm="24" :md="8" :lg="4">
            <a-form-item label="Fix Amount" name="prof_tax_fix_amount">
                <a-switch
                    v-model:checked="formData.prof_tax_fix_amount"
                    :checkedValue="true"
                    :unCheckedValue="false"
                />
            </a-form-item>
        </a-col>

        <!-- Prof. Tax Percentage or Amount -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item
                :label="formData.prof_tax_fix_amount ? 'Prof. Tax Amount' : 'Prof. Tax Percentage'"
                name="prof_tax_percentage"
            >
                <a-input-number
                    :key="formData.prof_tax_fix_amount ? 'prof-tax-amount' : 'prof-tax-percentage'"
                    v-model:value="formData.prof_tax_percentage"
                    :min="0"
                    :max="formData.prof_tax_fix_amount ? undefined : 100"
                    :formatter="formData.prof_tax_fix_amount ? undefined : (v => `${v}%`)"
                    :parser="formData.prof_tax_fix_amount ? undefined : (v => v.replace('%', ''))"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>

        <!-- Prof. Tax Limit -->
        <a-col :xs="24" :sm="24" :md="8" :lg="8">
            <a-form-item label="Prof. Tax Limit" name="prof_tax_limit">
                <a-input-number
                    v-model:value="formData.prof_tax_limit"
                    :min="0"
                    style="width: 100%"
                />
            </a-form-item>
        </a-col>
    </template>
</a-row>
                </a-tab-pane>
            </a-tabs>
        </a-form>
        <template #footer>
            <a-space>
                <a-button type="primary" @click="onSubmit" :loading="loading">
                    <template #icon> <SaveOutlined /> </template>
                    {{
                        addEditType == "add"
                            ? $t("common.create")
                            : $t("common.update")
                    }}
                </a-button>
                <a-button @click="onClose">
                    {{ $t("common.cancel") }}
                </a-button>
            </a-space>
        </template>
    </a-drawer>
</template>

<script>
import { defineComponent, ref, onMounted, watch, nextTick } from "vue";
import {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
} from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import Upload from "../../../../common/core/ui/file/Upload.vue";
import RoleAddButton from "../../settings/roles/AddButton.vue";
import common from "../../../../common/composable/common";
import DepartmentAddButton from "../departments/AddButton.vue";
import DesignationAddButton from "../designations/AddButton.vue";
import ShiftAddButton from "../shifts/AddButton.vue";
import LocationAddButton from "../../settings/location/AddButton.vue";
import FormItemHeading from "../../../../common/components/common/typography/FormItemHeading.vue";
import { forEach } from "lodash-es";
import BasicSalary from "../../payrolls/basic-salary/BasicSalary.vue";
import EmployeeWorkStatusAddButton from "../../settings/employee-work-status/AddButton.vue";
import { useAuthStore } from "../../../../main/store/authStore";

export default defineComponent({
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "reFetchListOfData",
        "successMessage",
    ],
    components: {
        PlusOutlined,
        LoadingOutlined,
        SaveOutlined,
        Upload,
        RoleAddButton,
        DepartmentAddButton,
        DesignationAddButton,
        ShiftAddButton,
        LocationAddButton,
        FormItemHeading,
        BasicSalary,
        EmployeeWorkStatusAddButton,
    },
    setup(props, { emit }) {
        const { permsArray, user, appSetting, dayjs } = common();
        const { addEditRequestAdmin, loading, rules, addEditActiveTab } =
            apiAdmin("basic");
        const roles = ref([]);
        const roleUrl = "roles?limit=10000";
        const authStore = useAuthStore();
        const departments = ref([]);
        const designations = ref([]);
        const selectedVisibility = ref("manager");
        const locations = ref([]);
        const shifts = ref([]);
        const joiningDate = ref("");
        const showVisibilty = ref(false);
        const isManager = ref(0);
        const staffRole = ref(undefined);
        const departmentUrl = "departments?limit=10000";
        const designationUrl = "designations?limit=10000";
        const locationUrl = "locations?limit=10000";
        const shiftUrl = "shifts?limit=10000";
        const allMembers = ref([]);
        const allStaffMemberUrl = "users?limit=10000";
        const employeeId = ref("");
        const adjustedVisible = ref(false);
        const adjustedUser = ref({});
        const newData = ref({});
        const basicSalaryRef = ref(null);
        const employeeWorkStatusUrl = "employee-work-status?limit=10000";
        const employeeWorkStatus = ref([]);

        onMounted(() => {
            const rolesPromise = axiosAdmin.get(roleUrl);
            const shiftsPromise = axiosAdmin.get(shiftUrl);
            const locationPromise = axiosAdmin.get(locationUrl);
            const departmentsPromise = axiosAdmin.get(departmentUrl);
            const designationsPromise = axiosAdmin.get(designationUrl);
            const employeeWorkStatusPromise = axiosAdmin.get(
                employeeWorkStatusUrl
            );

            Promise.all([
                rolesPromise,
                departmentsPromise,
                designationsPromise,
                shiftsPromise,
                locationPromise,
                employeeWorkStatusPromise,
            ]).then(
                ([
                    rolesResponse,
                    departmentsResponse,
                    designationsResponse,
                    shiftsResponse,
                    locationResponse,
                    employeeWorkStatusResponse,
                ]) => {
                    roles.value = rolesResponse.data;
                    departments.value = departmentsResponse.data;
                    designations.value = designationsResponse.data;
                    shifts.value = shiftsResponse.data;
                    locations.value = locationResponse.data;
                    employeeWorkStatus.value = employeeWorkStatusResponse.data;
                }
            );

            employeeId.value =
                appSetting.value.employee_id_prefix +
                "-" +
                appSetting.value.employee_id_start;
        });
        

        const onSubmit = () => {
            var newFormData = {
                ...props.formData,
                joining_date: joiningDate.value,
                is_manager: isManager.value,
                role_id: staffRole.value,
                visibility: selectedVisibility.value,
                status: "active",
                ...newData.value,
            };
            addEditRequestAdmin({
                id: "add_edit_user_form",
                url: props.url,
                data: newFormData,
                successMessage: props.successMessage,
                success: (res) => {
                    emit("addEditSuccess", res.xid);
                    authStore.updateAppAction();

                    if (user.value.xid == res.xid) {
                        authStore.updateUserAction();
                    }
                },
            });
        };

        const assignRole = (id) => {
            if (id) {
                forEach(roles.value, (role) => {
                    if (role.xid == id) {
                        if (role.name == "admin") {
                            showVisibilty.value = false;
                        } else {
                            showVisibilty.value = true;
                        }
                    }
                });
            } else {
                showVisibilty.value = false;
            }
        };
        const updateSalaryData = (updatedData) => {
            Object.assign(newData.value, updatedData);
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        const roleAdded = (xid) => {
            axiosAdmin.get(roleUrl).then((response) => {
                roles.value = response.data;
                staffRole.value = xid;
                assignRole(xid);
            });
        };

        const departmentAdded = (xid) => {
            axiosAdmin.get(departmentUrl).then((response) => {
                departments.value = response.data;
                emit("addListSuccess", {
                    type: "department_id",
                    id: xid,
                });
            });
        };

        const designationAdded = (xid) => {
            axiosAdmin.get(designationUrl).then((response) => {
                designations.value = response.data;
                emit("addListSuccess", { type: "designation_id", id: xid });
            });
        };

        const shiftAdded = (xid) => {
            axiosAdmin.get(shiftUrl).then((response) => {
                shifts.value = response.data;
                emit("addListSuccess", { type: "shift_id", id: xid });
            });
        };
        const locationAdded = (xid) => {
            axiosAdmin.get(locationUrl).then((response) => {
                locations.value = response.data;
                emit("addListSuccess", { type: "location_id", id: xid });
            });
        };

        const employeeWorkStatusAdded = (xid) => {
            axiosAdmin.get(employeeWorkStatusUrl).then((response) => {
                employeeWorkStatus.value = response.data;
                emit("addListSuccess", { type: "employee_status_id", id: xid });
            });
        };

        watch(
            () => staffRole.value,
            (newVal, oldVal) => {
                if (newVal === undefined) {
                    selectedVisibility.value = "individual";
                }
            }
        );

        watch(
            () => props.visible,
            (newVal, oldVal) => {
                addEditActiveTab.value = "basic";
                if (newVal) {
                    const allMembersPromise = axiosAdmin.get(allStaffMemberUrl);
                    Promise.all([allMembersPromise]).then(
                        ([allMembersResponse]) => {
                            allMembers.value = allMembersResponse.data;
                        }
                    );
                    staffRole.value = undefined;
                    isManager.value = 0;
                    showVisibilty.value = false;

                    if (props.addEditType == "add") {
                        joiningDate.value = dayjs().format("YYYY-MM-DD");

                        employeeId.value =
                            appSetting.value.employee_id_prefix +
                            "-" +
                            appSetting.value.employee_id_start;
                    }

                    if (props.addEditType == "edit") {
                        joiningDate.value = props.data.joining_date;
                        isManager.value = props.data.is_manager;
                        staffRole.value = props.data.x_role_id;
                        employeeId.value = props.data.employee_number;

                        if (
                            props.data.visibility != "" &&
                            props.data.visibility != null &&
                            props.data.role &&
                            props.data.role.name != "admin"
                        ) {
                            selectedVisibility.value = props.data.visibility;
                            showVisibilty.value = true;
                        }

                        if (props.data.is_manager == 1) {
                            isManager.value = 1;
                        }
                    }

                    if (!newVal) {
                        adjustedVisible.value = false;
                    }
                }
            }
        );

        const onTabChange = async (addEditActiveTab) => {
            adjustedUser.value = {};
            adjustedVisible.value = false;
            await nextTick();
            if (addEditActiveTab === "salary_details" && props.visible) {
                if (basicSalaryRef.value) {
                    adjustedVisible.value = true;
                    adjustedUser.value = { ...props.data };
                }
            } else {
                adjustedVisible.value = false;
            }
        };

        watch(
            () => isManager.value,
            (newVal, oldVal) => {
                if (newVal == 0) {
                    staffRole.value = undefined;
                    showVisibilty.value = false;
                    selectedVisibility.value = undefined;
                }
            }
        );

        return {
            loading,
            rules,
            onClose,
            onSubmit,
            roles,

            roleAdded,
            permsArray,
            appSetting,
            designationAdded,
            departmentAdded,
            departments,
            designations,
            shifts,
            shiftAdded,
            locations,
            locationAdded,
            assignRole,
            joiningDate,
            allMembers,
            showVisibilty,
            isManager,
            staffRole,
            selectedVisibility,
            addEditActiveTab,
            employeeId,
            updateSalaryData,
            adjustedVisible,
            adjustedUser,
            onTabChange,
            basicSalaryRef,
            employeeWorkStatusAdded,
            employeeWorkStatus,
            drawerWidth: window.innerWidth <= 991 ? "90%" : "65%",
        };
    },
});
</script>

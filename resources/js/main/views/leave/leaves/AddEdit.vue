<template>
    <a-drawer
        :title="pageTitle"
        :width="drawerWidth"
        :open="visible"
        :body-style="{ paddingBottom: '80px' }"
        :footer-style="{ textAlign: 'right' }"
        :maskClosable="false"
        @close="onClose"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <a-col
                    :xs="24"
                    :sm="24"
                    :md="24"
                    :lg="24"
                    v-if="
                        permsArray.includes('admin') ||
                        permsArray.includes('leaves_edit')
                    "
                >
                    <a-form-item
                        :label="$t('leave.user_id')"
                        name="user_id"
                        :help="rules.user_id ? rules.user_id.message : null"
                        :validateStatus="rules.user_id ? 'error' : null"
                        class="required"
                    >
                        <span style="display: flex">
                            <a-select
                                v-model:value="formData.user_id"
                                :placeholder="
                                    $t('common.select_default_text', [
                                        $t('leave.user_id'),
                                    ])
                                "
                                :allowClear="true"
                                optionFilterProp="title"
                                show-search
                            >
                                <a-select-option
                                    v-for="allStaffMember in allStaffMembers"
                                    :key="allStaffMember.xid"
                                    :value="allStaffMember.xid"
                                    :title="allStaffMember.name"
                                >
                                    <user-list-display
                                        :user="allStaffMember"
                                        whereToShow="select"
                                    />
                                </a-select-option>
                            </a-select>
                            <StaffMemberAddButton
                                @onAddSuccess="staffMemberAdded"
                            />
                        </span>
                    </a-form-item>
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <a-form-item label="Select Multiple Dates (Same Month)">
                        <div
                            style="
                                border: 1px solid #d9d9d9;
                                padding: 12px;
                                border-radius: 6px;
                                background: #fafafa;
                            "
                        >
                            <a-date-picker
                                v-model:value="tempDate"
                                :disabledDate="disableCrossMonthDates"
                                @change="onAddDate"
                                style="width: 100%"
                            />

                            <div style="margin-top: 12px">
                                <a-tag
                                    v-for="(d, index) in selectedDates"
                                    :key="d.date"
                                    closable
                                    @close="removeDateRow(d.date)"
                                    style="margin-bottom: 6px"
                                >
                                    {{ d.date }}
                                </a-tag>
                            </div>

                            <!-- <div style="margin-top: 12px; text-align: right">
                                <a-button
                                    type="primary"
                                    size="small"
                                    @click="confirmDateSelection"
                                    :disabled="!selectedDates.length"
                                >
                                    ✔ Confirm
                                </a-button>
                            </div> -->
                        </div>
                    </a-form-item>

                    <!-- Display selected dates as tags -->
                    <!-- <div style="margin-bottom: 16px">
                        <a-tag
                            v-for="(d, index) in selectedDates"
                            :key="d.date"
                            closable
                            @close="removeDateRow(d.date)"
                        >
                            {{ d.date }}
                        </a-tag>
                    </div> -->

                    <!-- Display selected dates as tags -->
                    <!-- <div style="margin-bottom: 16px">
                        <a-tag
                            v-for="(d, index) in selectedDates"
                            :key="d.date"
                            closable
                            @close="removeDateRow(d.date)"
                        >
                            {{ d.date }}
                        </a-tag>
                    </div> -->
                </a-col>
            </a-row>
            <a-row :gutter="16">
                <a-col :xs="24" :sm="24" :md="24" :lg="24">
                    <!-- Selected Dates Table -->
                    <a-table
                        :dataSource="selectedDates"
                        :columns="leaveColumns"
                        rowKey="date"
                        bordered
                        size="small"
                        pagination="false"
                    >
                        <template #bodyCell="{ column, record }">
                            <template v-if="column.key === 'leave_type_id'">
                                <a-select
                                    v-model:value="record.leave_type_id"
                                    placeholder="Leave Type"
                                    style="width: 100%"
                                >
                                    <a-select-option
                                        v-for="item in allLeaveTypes"
                                        :key="item.xid"
                                        :value="item.xid"
                                    >
                                        {{ item.name }}
                                    </a-select-option>
                                </a-select>
                            </template>

                            <template v-else-if="column.key === 'is_half_day'">
                                <a-radio-group
                                    v-model:value="record.is_half_day"
                                    size="small"
                                    button-style="solid"
                                    @change="onHalfDayChange(record)"
                                >
                                    <a-radio-button :value="1"
                                        >Yes</a-radio-button
                                    >
                                    <a-radio-button :value="0"
                                        >No</a-radio-button
                                    >
                                </a-radio-group>
                            </template>

                            <template
                                v-else-if="column.key === 'half_day_type'"
                            >
                                <template v-if="record.is_half_day === 1">
                                    <a-radio-group
                                        v-model:value="record.half_day_type"
                                        size="small"
                                        button-style="solid"
                                    >
                                        <a-radio-button value="morning"
                                            >Morning</a-radio-button
                                        >
                                        <a-radio-button value="evening"
                                            >Evening</a-radio-button
                                        >
                                    </a-radio-group>
                                </template>
                            </template>

                            <template v-else-if="column.key === 'reason'">
                                <a-form-item
                                    :validateStatus="
                                        !record.reason || !record.reason.trim()
                                            ? 'error'
                                            : ''
                                    "
                                    :help="
                                        !record.reason || !record.reason.trim()
                                            ? 'Reason is required'
                                            : ''
                                    "
                                    style="margin-bottom: 0"
                                >
                                    <a-textarea
                                        v-model:value="record.reason"
                                        placeholder="Enter reason"
                                        :rows="2"
                                    />
                                </a-form-item>
                            </template>

                            <template v-else-if="column.key === 'actions'">
                                <a-button
                                    type="link"
                                    danger
                                    @click="removeDateRow(record.date)"
                                >
                                    Remove
                                </a-button>
                            </template>
                        </template>
                    </a-table>
                </a-col>
                <a-col :xs="24" :sm="24" :md="24" :lg="24"> </a-col>
            </a-row>
        </a-form>
        <template #footer>
            <a-space>
                <a-button
                    key="submit"
                    type="primary"
                    :loading="loading"
                    @click="onSubmit"
                >
                    <template #icon>
                        <SaveOutlined />
                    </template>
                    {{
                        addEditType == "add"
                            ? $t("common.create")
                            : $t("common.update")
                    }}
                </a-button>
                <a-button key="back" @click="onClose">
                    {{ $t("common.cancel") }}
                </a-button>
            </a-space>
        </template>
    </a-drawer>
</template>
<script>
import { defineComponent, onMounted, ref, watch } from "vue";
import {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
} from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import common from "../../../../common/composable/common";
import StaffMemberAddButton from "../../staff-members/users/StaffAddButton.vue";
import UploadFile from "../../../../common/core/ui/file/UploadFile.vue";
import LeaveTypeAddButton from "../leave-types/AddButton.vue";
import DateRangePicker from "../../../../common/components/common/calendar/DateRangePicker.vue";
import UserListDisplay from "../../../../common/components/user/UserListDisplay.vue";

export default defineComponent({
    props: [
        "formData",
        "data",
        "visible",
        "url",
        "addEditType",
        "pageTitle",
        "successMessage",
    ],
    components: {
        PlusOutlined,
        LoadingOutlined,
        SaveOutlined,
        StaffMemberAddButton,
        LeaveTypeAddButton,
        UploadFile,
        DateRangePicker,
        UserListDisplay,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();

        const { appSetting, disabledDate, permsArray, dayjs } = common();
        const allStaffMembers = ref([]);
        const staffMembersUrl = "users?limit=10000";
        const allLeaveTypes = ref([]);
        const leaveTypeUrl = "leave-types";

        onMounted(() => {
            const staffMemberPromise = axiosAdmin.get(staffMembersUrl);
            const leaveTypePromise = axiosAdmin.get(leaveTypeUrl);
            Promise.all([staffMemberPromise, leaveTypePromise]).then(
                ([staffMemberResponse, leaveTypeResponse]) => {
                    allStaffMembers.value = staffMemberResponse.data;
                    allLeaveTypes.value = leaveTypeResponse.data;
                }
            );
        });

        const onSubmit = async () => {
            // addEditRequestAdmin({
            //     url: props.url,
            //     data: {
            //         ...props.formData,
            //         start_date: props.formData.date
            //             ? props.formData.date[0]
            //             : "",
            //         end_date: props.formData.date ? props.formData.date[1] : "",
            //     },
            //     successMessage: props.successMessage,
            //     success: (res) => {
            //         emit("addEditSuccess", res.xid);
            //     },
            // });
            if (!props.formData.user_id) {
                console.log("Please select a user");
                return;
            }

            if (!selectedDates.value.length) {
                console.log("Please select at least one date");
                return;
            }

            for (const entry of selectedDates.value) {
                const payload = {
                    user_id: props.formData.user_id,
                    leave_type_id: entry.leave_type_id,
                    start_date: entry.date,
                    end_date: entry.date,
                    leave_date: entry.date,
                    is_half_day: entry.is_half_day,
                    half_day_type: entry.is_half_day ? entry.half_day_type : "",
                    date: [entry.date, entry.date],
                    reason: entry.reason || "",
                    status: "pending",
                };

                await addEditRequestAdmin({
                    url: props.url,
                    data: payload,
                    successMessage: props.successMessage,
                    success: (res) => {
                        // Emit only after final entry
                        if (
                            entry ===
                            selectedDates.value[selectedDates.value.length - 1]
                        ) {
                            selectedDates.value = [];
                            tempDate.value = null;
                            emit("addEditSuccess", res.xid || res);
                        }
                    },
                });
            }
        };

        const staffMemberAdded = (xid) => {
            axiosAdmin.get(staffMembersUrl).then((response) => {
                allStaffMembers.value = response.data;
                emit("addListSuccess", { type: "user_id", id: xid });
            });
        };

        const leaveTypeAdded = (xid) => {
            axiosAdmin.get(leaveTypeUrl).then((response) => {
                allLeaveTypes.value = response.data;
                emit("addListSuccess", { type: "leave_type_id", id: xid });
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        watch(
            () => props.visible,
            (newVal, oldVal) => {
                let arr_dates = [];

                props.formData.date = undefined;
                if (props.addEditType == "add") {
                    arr_dates = [];
                } else {
                    var start = dayjs(props.data.start_date).format(
                        "YYYY-MM-DD"
                    );
                    var end = dayjs(props.data.end_date).format("YYYY-MM-DD");
                    arr_dates.push(start);
                    arr_dates.push(end);
                    props.formData.date = arr_dates;
                }
            }
        );

        const tempDate = ref(null);
        const dateDropdownVisible = ref(false);
        const selectedMonth = ref(null);

        const selectedDates = ref([]);
        const leaveColumns = [
            { title: "Date", dataIndex: "date", key: "date" },
            {
                title: "Leave Type",
                dataIndex: "leave_type_id",
                key: "leave_type_id",
            },
            {
                title: "Is Half Day",
                dataIndex: "is_half_day",
                key: "is_half_day",
            },
            {
                title: "Session",
                dataIndex: "half_day_type",
                key: "half_day_type",
            },
            { title: "Reason", dataIndex: "reason", key: "reason" },
            { title: "Actions", key: "actions" },
        ];

        // Add date only if in same month
        const onAddDate = (date) => {
            const formattedDate = dayjs(date).format("YYYY-MM-DD");
            const month = dayjs(date).format("YYYY-MM");

            if (!selectedMonth.value) {
                selectedMonth.value = month;
            }

            if (selectedMonth.value !== month) {
                message.warning("Please select dates within the same month");
                return;
            }

            const exists = selectedDates.value.find(
                (d) => d.date === formattedDate
            );
            if (!exists) {
                selectedDates.value.push({
                    date: formattedDate,
                    start_date: formattedDate,
                    end_date: formattedDate,
                    leave_type_id: null,
                    is_half_day: 0,
                    half_day_type: null,
                    reason: "",
                });
            }
        };
        const disableCrossMonthDates = (current) => {
            if (!selectedMonth.value) return false;
            return dayjs(current).format("YYYY-MM") !== selectedMonth.value;
        };

        const removeDateRow = (date) => {
            selectedDates.value = selectedDates.value.filter(
                (d) => d.date !== date
            );
            if (selectedDates.value.length === 0) {
                selectedMonth.value = null;
            }
        };
        const onHalfDayChange = (record) => {
            if (record.is_half_day === 1) {
                record.half_day_type = record.half_day_type || "morning";
            } else {
                record.half_day_type = null;
            }
        };

        const generateLeavePayload = () => {
            return selectedDates.value.map((entry) => {
                return {
                    user_id: formData.user_id,
                    leave_type_id: entry.leave_type_id,
                    start_date: entry.date,
                    end_date: entry.date,
                    leave_date: entry.date,
                    is_half_day: entry.is_half_day,
                    half_day_type: entry.is_half_day ? entry.half_day_type : "",
                    reason: entry.reason || "",
                    status: "pending",
                };
            });
        };

        return {
            loading,
            rules,
            onClose,
            onSubmit,
            appSetting,
            disabledDate,
            permsArray,
            staffMemberAdded,
            allStaffMembers,
            leaveTypeAdded,
            allLeaveTypes,
            tempDate,
            selectedDates,
            selectedMonth,
            leaveColumns,
            onAddDate,
            disableCrossMonthDates,
            removeDateRow,
            // prepareLeaveRequest,
            drawerWidth: window.innerWidth <= 991 ? "70%" : "65%",
        };
    },
});
</script>

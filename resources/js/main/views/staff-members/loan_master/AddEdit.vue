<template>
    <a-modal :open="visible" :closable="false" :width="700" :centered="true" title="Loan Master" @ok="onSubmit">
        <a-form layout="vertical">
            <a-row :gutter="16">
            
                <a-col :span="12">
                    <a-form-item label="Employee" name="employee_id" :help="rules.employee_id?.message"
                        :validateStatus="rules.employee_id ? 'error' : null" class="required">
                        <a-select v-model:value="localFormData.employee_id" placeholder="Select Employee"
                            :options="users.map(user => ({ label: user.name, value: user.id }))" show-search
                            option-filter-prop="label" />
                    </a-form-item>
                </a-col>

              
                <a-col :span="12">
                    <a-form-item label="Amount" name="amount" :help="rules.amount?.message"
                        :validateStatus="rules.amount ? 'error' : null" class="required">
                        <a-input-number v-model:value="localFormData.amount" :min="0" style="width: 100%"
                            placeholder="Enter amount" />
                    </a-form-item>
                </a-col>

                <a-col :span="12">
                    <a-form-item label="Tenure (months)" name="tenure" class="required">
                        <a-input-number v-model:value="localFormData.tenure" :min="1" :max="12"
                            placeholder="Enter tenure" style="width: 100%" />
                    </a-form-item>

                </a-col>

                <a-col :span="12">
                    <a-form-item label="Monthly Amount">
                        <a-input :value="monthlyAmount" readonly />
                    </a-form-item>
                </a-col>

                <!-- :disabledDate="disablePastMonths" -->
                 
                <a-col :span="12">
                    <a-form-item label="Start From" name="start_month" class="required">
                        <a-date-picker v-model:value="localFormData.start_month"
                            picker="month" format="MMM YYYY" value-format="YYYY-MM" placeholder="Select Month"
                            style="width: 100%" />
                    </a-form-item>

                </a-col>

             <a-col :span="12">
  <a-form-item label="End On" name="end_month">
    <a-date-picker
      v-model:value="localFormData.end_month"
      picker="month"
      format="MMM YYYY"
      value-format="YYYY-MM"
      placeholder="Auto-calculated"
      style="width: 100%"
      disabled
    />
  </a-form-item>
</a-col>

            </a-row>
        </a-form>

        <template #footer>
            <a-button key="submit" type="primary" :loading="loading" @click="onSubmit">
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ addEditType === "add" ? "Create" : "Update" }}
            </a-button>
            <a-button key="back" @click="onClose">Cancel</a-button>
        </template>
    </a-modal>
</template>


<script>
import { defineComponent, ref, onMounted, watch } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
import dayjs from "dayjs";

export default defineComponent({
    emits: ["closed", "addEditSuccess"],
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
        SaveOutlined,
    },

    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const users = ref([]);
        const monthlyAmount = ref(0);
        const localFormData = ref({ ...props.formData });

        watch(
            () => props.formData,
            (newVal) => {
                localFormData.value = { ...newVal };
            },
            { immediate: true, deep: true }
        );

        const disablePastMonths = (current) => {
            return dayjs(current).isBefore(dayjs().startOf('month'), 'month');
        };

        watch(
            () => [localFormData.value.start_month, localFormData.value.tenure],
            ([startMonth, tenure]) => {
                if (startMonth && tenure) {
                    const start = dayjs(startMonth, "YYYY-MM");
                    const end = start.add(Number(tenure), "month").subtract(1, "day");
                   localFormData.value.end_month = end.format("YYYY-MM");
                } else {
                    localFormData.value.end_month = null;
                }
            }
        );

       watch(
            () => [localFormData.value.amount, localFormData.value.tenure],
            ([amount, tenure]) => {
                const amt = Number(amount);
                const tnr = Number(tenure);

                if (amt > 0 && tnr > 0) {
                    const monthly = (amt / tnr).toFixed(2);
                    monthlyAmount.value = monthly;
                    localFormData.value.monthly_amount = monthly;
                } else {
                    monthlyAmount.value = 0;
                    localFormData.value.monthly_amount = null;
                }
            }
        );

        const userUrl = "users?limit=10000&fields=id,name,xid";

        onMounted(() => {
            axiosAdmin.get(userUrl).then((res) => {
                users.value = res.data;
            });
        });

        const onSubmit = () => {
            const successMsg =
                props.addEditType === "add"
                    ? "Loan Added Successfully"
                    : "Loan Updated Successfully";

            addEditRequestAdmin({
                url: props.url,
                data: localFormData.value,
                successMessage: successMsg,
                success: (res) => {
                    emit("addEditSuccess", res.xid);
                },
            });
        };

        const onClose = () => {
            rules.value = {};
            emit("closed");
        };

        return {
            users,
            localFormData,
            monthlyAmount,
            loading,
            rules,
            disablePastMonths,
            onSubmit,
            onClose,
        };
    },
});
</script>

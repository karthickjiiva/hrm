<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        title="Bank Master"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                   <!-- Employee -->
                <a-col :xs="24">
                    <a-form-item
                        label="Employee"
                        name="employee_id"
                        :help="
                            rules.employee_id ? rules.employee_id.message : null
                        "
                        :validateStatus="rules.employee_id ? 'error' : null"
                        class="required"
                    >
                        <a-select
                            v-model:value="formData.employee_id"
                            placeholder="Select Employee"
                            :options="
                                users.map((user) => ({
                                    label: user.name,
                                    value: user.id,
                                }))
                            "
                            show-search
                            option-filter-prop="label"
                        />
                    </a-form-item>
                </a-col>
                <!-- Account Number -->
                <a-col :xs="24">
                    <a-form-item
                        label="Account Number"
                        name="account_number"
                        :help="
                            rules.account_number
                                ? rules.account_number.message
                                : null
                        "
                        :validateStatus="rules.account_number ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.account_number"
                            placeholder="Enter Account Number"
                        />
                    </a-form-item>
                </a-col>

                <!-- Bank Name -->
                <a-col :xs="24">
                    <a-form-item
                        label="Bank Name"
                        name="bank_name"
                        :help="rules.bank_name ? rules.bank_name.message : null"
                        :validateStatus="rules.bank_name ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.bank_name"
                            placeholder="Enter Bank Name"
                        />
                    </a-form-item>
                </a-col>

                <!-- IFSC Code -->
                <a-col :xs="24">
                    <a-form-item
                        label="IFSC Code"
                        name="ifsc"
                        :help="rules.ifsc ? rules.ifsc.message : null"
                        :validateStatus="rules.ifsc ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.ifsc"
                            placeholder="Enter IFSC Code"
                        />
                    </a-form-item>
                </a-col>

                <!-- MICR Code -->
                <a-col :xs="24">
                    <a-form-item
                        label="MICR Code"
                        name="micr"
                        :help="rules.micr ? rules.micr.message : null"
                        :validateStatus="rules.micr ? 'error' : null"
                    >
                        <a-input
                            v-model:value="formData.micr"
                            placeholder="Enter MICR Code"
                        />
                    </a-form-item>
                </a-col>

             
            </a-row>
        </a-form>

        <!-- Footer -->
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="loading"
                @click="onSubmit"
            >
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ addEditType === "add" ? "Create" : "Update" }}
            </a-button>
            <a-button key="back" @click="onClose"> Cancel </a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import {
    PlusOutlined,
    LoadingOutlined,
    SaveOutlined,
} from "@ant-design/icons-vue";
import apiAdmin from "../../../../common/composable/apiAdmin";
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
        SaveOutlined,
    },
    setup(props, { emit }) {
        const { addEditRequestAdmin, loading, rules } = apiAdmin();
        const users = ref([]);
        const userUrl = "users?limit=10000&fields=id,name,xid";

        onMounted(() => {
            axiosAdmin.get(userUrl).then((res) => {
                users.value = res.data;
            });
        });

        const onSubmit = () => {
            addEditRequestAdmin({
                url: props.url,
                data: props.formData,
                successMessage: props.successMessage,
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
            loading,
            rules,
            onClose,
            onSubmit,
            users,
        };
    },
});
</script>

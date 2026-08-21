<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        title="Employee Leave Master"
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

                <!-- Sick Leave (SL) -->
                <a-col :xs="24">
                    <a-form-item
                        label="Sick Leave (SL)"
                        name="sl"
                        :help="rules.sl ? rules.sl.message : null"
                        :validateStatus="rules.sl ? 'error' : null"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="formData.sl"
                            placeholder="Enter Sick Leave"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>

                <!-- Casual Leave (CL) -->
                <a-col :xs="24">
                    <a-form-item
                        label="Casual Leave (CL)"
                        name="cl"
                        :help="rules.cl ? rules.cl.message : null"
                        :validateStatus="rules.cl ? 'error' : null"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="formData.cl"
                            placeholder="Enter Casual Leave"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>

                <!-- Earned Leave (EL) -->
                <a-col :xs="24">
                    <a-form-item
                        label="Earned Leave (EL)"
                        name="el"
                        :help="rules.el ? rules.el.message : null"
                        :validateStatus="rules.el ? 'error' : null"
                        class="required"
                    >
                        <a-input-number
                            v-model:value="formData.el"
                            placeholder="Enter Earned Leave"
                            :min="0"
                            style="width: 100%"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>

        <!-- Footer Buttons -->
        <template #footer>
            <a-button type="primary" :loading="loading" @click="onSubmit">
                <template #icon>
                    <SaveOutlined />
                </template>
                {{ addEditType === "add" ? "Create" : "Update" }}
            </a-button>
            <a-button @click="onClose">Cancel</a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "@/common/composable/apiAdmin";

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

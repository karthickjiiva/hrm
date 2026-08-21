<template>
    <a-modal
        :open="visible"
        :closable="false"
        :centered="true"
        title="Rejonee"
        @ok="onSubmit"
    >
        <a-form layout="vertical">
            <a-row :gutter="16">
                <!-- Title -->
                <a-col :xs="24">
                    <a-form-item
                        label="Title"
                        name="title"
                        :help="rules.title?.message || null"
                        :validateStatus="rules.title ? 'error' : null"
                        class="required"
                    >
                        <a-input
                            v-model:value="formData.title"
                            placeholder="Enter Title"
                        />
                    </a-form-item>
                </a-col>

                <!-- Description -->
                <a-col :xs="24">
                    <a-form-item
                        label="Description"
                        name="description"
                        :help="rules.description?.message || null"
                        :validateStatus="rules.description ? 'error' : null"
                    >
                        <a-textarea
                            v-model:value="formData.description"
                            placeholder="Enter Description"
                            auto-size
                        />
                    </a-form-item>
                </a-col>

                <!-- Resignated Date -->
                <a-col :xs="24" :md="12">
                    <a-form-item
                        label="Resignated Date"
                        name="resignated_date"
                        :help="rules.resignated_date?.message || null"
                        :validateStatus="rules.resignated_date ? 'error' : null"
                        class="required"
                    >
                        <a-date-picker
                            v-model:value="formData.resignated_date"
                            style="width: 100%"
                            format="YYYY-MM-DD"
                            value-format="YYYY-MM-DD"
                            placeholder="Select Resignated Date"
                        />
                    </a-form-item>
                </a-col>

                <!-- Rejoined Date -->
                <a-col :xs="24" :md="12">
                    <a-form-item
                        label="Rejoined Date"
                        name="rejoined_date"
                        :help="rules.rejoined_date?.message || null"
                        :validateStatus="rules.rejoined_date ? 'error' : null"
                        class="required"
                    >
                        <a-date-picker
                            v-model:value="formData.rejoined_date"
                            style="width: 100%"
                            format="YYYY-MM-DD"
                            value-format="YYYY-MM-DD"
                            placeholder="Select Rejoined Date"
                        />
                    </a-form-item>
                </a-col>

                <!-- Employee -->
                <a-col :xs="24">
                    <a-form-item
                        label="Employee"
                        name="user_id"
                        :help="rules.user_id?.message || null"
                        :validateStatus="rules.user_id ? 'error' : null"
                        class="required"
                    >
                        <a-select
                            v-model:value="formData.user_id"
                            placeholder="Select Employee"
                            show-search
                            :options="
                                users.map((user) => ({
                                    label: user.name,
                                    value: user.xid,
                                }))
                            "
                            option-filter-prop="label"
                        />
                    </a-form-item>
                </a-col>
            </a-row>
        </a-form>

        <!-- Modal Footer -->
        <template #footer>
            <a-button
                key="submit"
                type="primary"
                :loading="loading"
                @click="onSubmit"
            >
                <template #icon><SaveOutlined /></template>
                {{ addEditType === "add" ? "Create" : "Update" }}
            </a-button>
            <a-button key="back" @click="onClose">Cancel</a-button>
        </template>
    </a-modal>
</template>

<script>
import { defineComponent, ref, onMounted } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import apiAdmin from "@/common/composable/apiAdmin";
// import { computed } from "vue";

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

        // const modalTitle = computed(() => {
        //     return (
        //         props.pageTitle ||
        //         (props.addEditType === "add" ? "Add Rejoinee" : "Edit Rejoinee")
        //     );
        // });

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

<template>
    <a-form>
        <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <!-- <a-form-item
                    :label="$t('salary_group.salary_group_id')"
                    name="salary_group_id"
                    :help="
                        rules.salary_group_id
                            ? rules.salary_group_id.message
                            : null
                    "
                    :validateStatus="rules.salary_group_id ? 'error' : null"
                >
                    <span style="display: flex">
                        <a-select
                            v-model:value="formData.salary_group_id"
                            style="width: 100%"
                            :placeholder="
                                $t('common.select_default_text', [
                                    $t('salary_group.salary_group_id'),
                                ])
                            "
                            :allowClear="true"
                            @change="
                                fetchSalaryComponentsAndUsers(formData.salary_group_id)
                            "
                        >
                            <a-select-option
                                v-for="salaryGroup in salaryGroups"
                                :key="salaryGroup.xid"
                                :value="salaryGroup.xid"
                            >
                                {{ salaryGroup.name }}
                            </a-select-option>
                        </a-select>
                        <SalaryGroupAddButton
                            @onAddSuccess="salaryGroupAdded"
                        />
                    </span>
                </a-form-item> -->
            </a-col>
            <a-col :xs="24" :sm="24" :md="12" :lg="12">
                <a-form-item label="Employee Type" name="employee_type_id" :help="rules.employee_type_id
                        ? rules.employee_type_id.message
                        : null
                    " :validateStatus="rules.employee_type_id ? 'error' : null" class="required">
                    <span style="display: flex">
                        <a-select v-model:value="formData.employee_type_id"
                            placeholder="Please select the employee type" :allowClear="false" optionFilterProp="title"
                            show-search @change="
                                fetch_employee_type_components(
                                    formData.employee_type_id
                                )
                                ">
                            <a-select-option v-for="type in employeeTypeGroups" :key="type.xid" :value="type.xid"
                                :title="type.type">
                                {{ type.type }}
                            </a-select-option>
                        </a-select>
                        <EmployeeTypeAddButton @onAddSuccess="employeeTypeAdded" />
                    </span>
                </a-form-item>
            </a-col>
        </a-row>
        <a-row :gutter="16" v-if="inputVisible">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <UserInfo :user="user" />
            </a-col>
        </a-row>
        <a-row :gutter="16" :style="{ marginTop: inputVisible ? '0px' : '18px' }">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <!-- Monthly CTC Input -->
                <a-form-item label="Monthly CTC" name="monthly_ctc" :labelCol="{ span: 6 }" :wrapperCol="{ span: 10 }"
                    labelAlign="left">
                    <a-input v-model:value="formData.monthly_ctc" @change="calculateSalary"
                        :addonBefore="appSetting.currency.symbol" />
                </a-form-item>
            </a-col>
        </a-row>
        <a-row :gutter="16" :style="{ marginTop: inputVisible ? '0px' : '18px' }">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <!-- Annual CTC Display (calculated, readonly) -->
                <a-form-item v-show="false" :label="$t('basic_salary.annual_ctc')" name="annual_ctc"
                    :labelCol="{ span: 6 }" :wrapperCol="{ span: 10 }" labelAlign="left">
                    <a-input v-model:value="formData.annual_ctc" :addonBefore="appSetting.currency.symbol" />
                </a-form-item>
            </a-col>

            <!-- Optional explanation -->
            <div :style="{ marginTop: inputVisible ? '0px' : '18px' }">
                {{ $t("basic_salary.cost_to_company_value_for_this_year") }}
            </div>
        </a-row>

        <a-row :gutter="16" style="
                margin-top: 20px;
                margin-bottom: 20px;
                border-bottom: 1px solid #d9d9d9;
                padding-bottom: 8px;
                display: flex;
                align-items: left;
            ">
            <a-col :xs="24" :sm="12" :md="6" :lg="6" :class="['column-text', 'new-class']">
                {{ $t("basic_salary.salary_component") }}
            </a-col>
            <a-col :xs="24" :sm="12" :md="6" :lg="6" :class="['column-text', 'new-class']">
                {{ $t("basic_salary.calculation_type") }}
            </a-col>
            <a-col :xs="24" :sm="12" :md="6" :lg="6" :class="['column-text', 'new-class']">
                {{ $t("basic_salary.monthly_amount") }}
            </a-col>
            <a-col :xs="24" :sm="12" :md="6" :lg="6" :class="['column-text', 'new-class']">
                {{ $t("basic_salary.annual_amount") }}
            </a-col>
        </a-row>

        <a-row :gutter="16" style="
                margin-top: 20px;
                padding-bottom: 8px;
                display: flex;
                align-items: left;
            ">
            <!-- Salary Component -->
            <a-col :xs="24" :sm="12" :md="6" :lg="6" class="column-text">
                {{ $t("basic_salary.basic_salary") }}
            </a-col>

            <!-- Calculation Type -->
            <a-col :xs="24" :sm="12" :md="6" :lg="6" class="column-text">
                <a-form-item name="ctc_value" :help="rules.ctc_value ? rules.ctc_value.message : null"
                    :validateStatus="rules.ctc_value ? 'error' : null" class="required">
                    <a-input-number v-model:value="formData.ctc_value" disabled :placeholder="$t('common.placeholder_default_text', [
                        $t('basic_salary.ctc_value'),
                    ])
                        " min="0" style="width: 100%" @change="calculateSalary">
                        <!-- <template #addonBefore>
                            {{ appSetting.currency.symbol }}
                        </template> -->
                        <!-- <template #addonAfter> -->
                        <div style="display: none">
                            <a-select v-model:value="formData.calculation_type" style="width: 120px"
                                @change="calculateSalary">
                                <a-select-option value="fixed">{{
                                    $t("basic_salary.fixed")
                                    }}</a-select-option>
                                <a-select-option value="%_of_ctc">{{
                                    $t("basic_salary.%_of_ctc")
                                    }}</a-select-option>
                            </a-select>
                        </div>
                        <!-- </template> -->
                    </a-input-number>
                </a-form-item>
            </a-col>

            <!-- Monthly Amount -->
            <a-col :xs="24" :sm="12" :md="6" :lg="6" class="column-text">
                <a-input-number v-model:value="monthlySalary" :placeholder="$t('common.placeholder_default_text', [
                    $t('basic_salary.ctc'),
                ])
                    " min="0" :disabled="true" style="width: 100%">
                    <template #addonBefore>
                        {{ appSetting.currency.symbol }}
                    </template>
                </a-input-number>
            </a-col>

            <!-- Annual Amount -->
            <a-col :xs="24" :sm="12" :md="6" :lg="6" class="column-text">
                <a-input-number v-model:value="annualSalary" :placeholder="$t('common.placeholder_default_text', [
                    $t('basic_salary.ctc'),
                ])
                    " min="0" :disabled="true" style="width: 100%">
                    <template #addonBefore>
                        {{ appSetting.currency.symbol }}
                    </template>
                </a-input-number>
            </a-col>
        </a-row>
        <a-row :gutter="16" class="new-class">
            <a-col :xs="24" :sm="24" :md="4" :lg="4">{{
                $t("basic_salary.earnings")
                }}</a-col>
        </a-row>
        <a-row :gutter="16">
            <a-col :xs="24" :sm="24" :md="24" :lg="24" v-if="salaryGroupComponentProps">
                <div v-for="(component, idx) in salaryGroupComponentProps" :key="idx">
                    <!-- Check if the salary component is of type 'earnings' -->
                    <a-row v-if="component.salary_component.type === 'earnings'" :gutter="16" style="margin-top: 14px">
                        <!-- Salary Component Name -->
                        <a-col :span="6" class="column-text">
                            <span>{{ component.salary_component.name }}</span>
                        </a-col>

                        <!-- Monthly Input for Earnings -->
                        <a-col :span="6">
                            <span>
                                {{
                                    component.salary_component.value_type ===
                                        "fixed"
                                        ? $t("salary_component.fixed")
                                        : component.salary_component
                                            .value_type === "basic_percent"
                                            ? $t("salary_component.basic_percent")
                                            : component.salary_component
                                                .value_type === "ctc_percent"
                                                ? $t("salary_component.ctc_percent")
                                                : $t("salary_component.variable")
                                }}
                            </span>
                        </a-col>
                        <a-col :span="6">
                            <a-input :value="getMonthlyValue(component)" @input="
                                (event) =>
                                    updateMonthlyValue(
                                        event.target.value,
                                        component.salary_component
                                    )
                            " :disabled="component.salary_component.value_type !==
                                    'variable'
                                    " placeholder="Enter Monthly Value" @change="calculateSalary" style="width: 100%">
                                <template #addonBefore>
                                    {{ appSetting.currency.symbol }}
                                </template>
                            </a-input>
                        </a-col>

                        <!-- Annual Input (calculated) -->
                        <a-col :span="6">
                            <a-input :value="calculateAnnualValue(component)" :disabled="component.salary_component.value_type !==
                                'variable'
                                " placeholder="Annual Value" readonly style="width: 100%">
                                <template #addonBefore>
                                    {{ appSetting.currency.symbol }}
                                </template>
                            </a-input>
                        </a-col>
                    </a-row>
                </div>
            </a-col>
        </a-row>

        <!-- <a-row  :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ $t("basic_salary.special_allowances") }}</div>
            </a-col> -->

        <!-- Value Type or Description (Optional) -->
        <!-- <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ $t("basic_salary.special_allowances") }}</div>
            </a-col> -->

        <!-- Special Allowance Monthly Input -->
        <!-- <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ specialAllowance }}</span
                >
            </a-col> -->

        <!-- Special Allowance Annual Input -->
        <!-- <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ (specialAllowance * 12).toFixed(2) }}</span
                >
            </a-col>
        </a-row> -->

        <a-row v-if="
            formData?.hra_percent_monthly &&
            formData?.hra_percent_monthly > 0
        " :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>HRA</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ formData?.hra_percent_monthly }}%</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_hra_percent_monthly }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_hra_percent_monthly }}
                </span>
            </a-col>
        </a-row>

        <a-row v-if="
            formData?.allowance_percent && formData?.allowance_percent > 0
        " :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>Allowance</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ formData?.allowance_percent }}%</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_allowance_percent }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_allowance_percent }}
                </span>
            </a-col>
        </a-row>

        <a-row v-if="
            formData?.food_allowance_percent &&
            formData?.food_allowance_percent > 0
        " :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>Food Allowance</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ formData?.food_allowance_percent }}%</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_food_allowance_percent }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_food_allowance_percent }}
                </span>
            </a-col>
        </a-row>

        <a-row :gutter="[16, 24]" style="margin-top: 10px" class="styled-row">
            <!-- Cost to Company Label -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ $t("basic_salary.cost_to_company") }}</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6"> </a-col>

            <!-- Monthly Cost to Company Value -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
                <b style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}{{ monthlyCostToCompany }}
                </b>
            </a-col>

            <!-- Annual Cost to Company Value -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
                <b style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_ctc.toFixed(2) }}
                </b>
            </a-col>
        </a-row>
        <a-row :gutter="16" style="margin-top: 20px" class="new-class">
            <a-col :xs="24" :sm="24" :md="4" :lg="4">{{
                $t("basic_salary.deductions")
                }}</a-col>
        </a-row>
        <a-row v-if="formData?.pf_enabled" :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>PF</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>
                    <span v-if="formData?.pf_fix_amount">
                        Fixed: {{ formData?.pf_percentage }}
                    </span>
                    <span v-else> {{ formData?.pf_percentage }}% </span>
                </div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_pf }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_pf }}
                </span>
            </a-col>
        </a-row>
        <a-row v-if="formData?.esi_enabled" :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>ESI</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>
                    <span v-if="formData?.esi_fix_amount">
                        Fixed: {{ formData?.esi_percentage }}
                    </span>
                    <span v-else> {{ formData?.esi_percentage }}% </span>
                </div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_esi }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_esi }}
                </span>
            </a-col>
        </a-row>
        <a-row v-if="formData?.prof_tax_enabled" :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>Proffesional Tax</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>
                    <span v-if="formData?.prof_tax_fix_amount">
                        Fixed: {{ formData?.prof_tax_percentage }}
                    </span>
                    <span v-else> {{ formData?.prof_tax_percentage }}% </span>
                </div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_prof_tax }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_prof_tax }}
                </span>
            </a-col>
        </a-row>
        <a-row v-if="formData?.tds_enabled" :gutter="16" style="margin-top: 10px">
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>TDS</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>
                    <span v-if="formData?.tds_fix_amount">
                        Fixed: {{ formData?.tds_percentage }}
                    </span>
                    <span v-else> {{ formData?.tds_percentage }}% </span>
                </div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.monthly_tds }}
                </span>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <span style="display: inline-block; width: 100%">
                    {{ appSetting.currency.symbol }}
                    {{ formData.annual_tds }}
                </span>
            </a-col>
        </a-row>
        <a-row :gutter="[16, 24]">
            <a-col :xs="24" :sm="24" :md="24" :lg="24">
                <div v-for="(component, idx) in salaryGroupComponentProps" :key="idx">
                    <a-row v-if="component.salary_component.type === 'deductions'" :gutter="16"
                        style="margin-top: 10px">
                        <a-col :span="6" class="column-text">
                            <span>{{ component.salary_component.name }}</span>
                        </a-col>

                        <a-col :span="6">
                            <span>
                                {{
                                    component.salary_component.value_type ===
                                        "fixed"
                                        ? $t("salary_component.fixed")
                                        : component.salary_component
                                            .value_type === "basic_percent"
                                            ? $t("salary_component.basic_percent")
                                            : component.salary_component
                                                .value_type === "ctc_percent"
                                                ? $t("salary_component.ctc_percent")
                                                : $t("salary_component.variable")
                                }}
                            </span>
                        </a-col>

                        <a-col :span="6" style="border: 2px solid red !important">
                            <a-input :value="getMonthlyValue(component)" @input="
                                (event) =>
                                    updateMonthlyValue(
                                        event.target.value,
                                        component.salary_component
                                    )
                            " :disabled="component.salary_component.value_type !==
                                    'variable'
                                    " placeholder="Enter Monthly Value" @change="calculateSalary" style="width: 100%">
                                <template #addonBefore>
                                    {{ appSetting.currency.symbol }}
                                </template>
                            </a-input>
                        </a-col>

                        <a-col :span="6" style="border: 2px solid red !important">
                            <a-input :value="calculateAnnualValue(component)" :disabled="component.salary_component.value_type !==
                                'variable'
                                " placeholder="Annual Value" readonly style="
                                    width: 100%;
                                    border: none;
                                    background: transparent;
                                    color: inherit;
                                    padding: 0;
                                ">
                                <template #addonBefore>
                                    pppp {{ appSetting.currency.symbol }}
                                </template>
                            </a-input>
                        </a-col>
                    </a-row>
                </div>
            </a-col>
        </a-row>
        <a-row :gutter="[16, 24]" style="margin-top: 10px" class="styled-row">
            <!-- Cost to Company Label -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6" class="column-text">
                <div>{{ $t("basic_salary.total_deductions") }}</div>
            </a-col>
            <a-col :xs="24" :sm="24" :md="6" :lg="6"> </a-col>

            <!-- Monthly Cost to Company Value -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
                <div>{{ appSetting.currency.symbol }}{{ deductions }}</div>
            </a-col>

            <!-- Annual Cost to Company Value -->
            <a-col :xs="24" :sm="24" :md="6" :lg="6">
                <div>
                    {{ appSetting.currency.symbol }}
                    {{ deductions * 12 }}
                </div>
            </a-col>
        </a-row>
    </a-form>
</template>
<script>
import { defineComponent, ref, computed, watch, onMounted } from "vue";
import { SaveOutlined } from "@ant-design/icons-vue";
import common from "../../../../common/composable/common";
import UserInfo from "../../../../common/components/user/UserInfo.vue";
import { forEach, find } from "lodash-es";
import apiAdmin from "../../../../common/composable/apiAdmin";
import SalaryGroupAddButton from "../../settings/payroll-settings/salary-groups/AddButton.vue";
import { toRaw } from "vue";

export default defineComponent({
    props: {
        visible: {
            type: Boolean,
            default: false,
        },
        user: {
            type: Object,
            default: {},
        },
        inputVisible: {
            type: Boolean,
            default: false,
        },
    },
    components: {
        SaveOutlined,
        UserInfo,
        SalaryGroupAddButton,
    },
    setup(props, { emit }) {
        const { loading, rules } = apiAdmin();
        const { appSetting } = common();
        const monthlyCTC = ref(null);
        const monthlyCTCError = ref(null);
        const selectUsers = ref("");
        const componentIds = ref([]);
        const formData = ref({
            basic_salary: 0,
            monthly_amount: 0,
            annual_amount: 0,
            annual_ctc: 0,
            monthly_ctc: 0,
            calculation_type: "%_of_ctc",
            ctc_value: 50,
        });
        const monthlySalary = ref(0);
        const annualSalary = ref(0);
        const earnings = ref(0);
        const deductions = ref(0);
        const monthlyCostToCompany = ref(0);
        const salaryComponents = ref([]);
        const salaryGroups = ref([]);
        const employeeTypeGroups = ref([]);
        const salaryGroupUrl =
            "employee_types?fields=id,xid,type,basic_percent,hra_percent,allowance_percent,food_allowance_percent,pf_enabled,pf_percentage,pf_limit,esi_enabled,esi_percentage,esi_limit,prof_tax_enabled,prof_tax_percentage,prof_tax_limit,tds_enabled,tds_percentage,tds_limit,status,pf_fix_amount,esi_fix_amount,tds_fix_amount,prof_tax_fix_amount&limit=1000";

        const employeeTypeGroupUrl =
            "employee_types?fields=id,xid,type,basic_percent,hra_percent,allowance_percent,food_allowance_percent,pf_enabled,pf_percentage,pf_limit,esi_enabled,esi_percentage,esi_limit,prof_tax_enabled,prof_tax_percentage,prof_tax_limit,tds_enabled,tds_percentage,tds_limit,status,pf_fix_amount,esi_fix_amount,tds_fix_amount,prof_tax_fix_amount&limit=1000";

        const salaryGroupComponentProps = ref([]);
        const employeeTypeComponentProps = ref([]);
        onMounted(() => {
            fetchSalaryGroups();
            fetchEmployeeTypeGroupUrl();
        });

        const fetchSalaryGroups = () => {
            const salaryGroupPromise = axiosAdmin.get(salaryGroupUrl);

            Promise.all([salaryGroupPromise]).then(([salaryGroupResponse]) => {
                salaryGroups.value = salaryGroupResponse.data;
            });
        };

        const fetchEmployeeTypeGroupUrl = () => {
            const employeeTypeGroupsPromise =
                axiosAdmin.get(employeeTypeGroupUrl);

            Promise.all([employeeTypeGroupsPromise]).then(
                ([employeeTypeGroupResponse]) => {
                    employeeTypeGroups.value = employeeTypeGroupResponse.data;
                }
            );
        };

        const salaryGroupAdded = () => {
            axiosAdmin.get(salaryGroupUrl).then((response) => {
                salaryGroups.value = response.data;
            });
        };
        const fetch_employee_type_components = (employeTypeGroupId) => {
            if (!employeTypeGroupId) {
                employeeTypeComponentProps.value = [];
                calculateSalary();
                return;
            }
            // const url = `${employeeTypeGroupUrl}${employeTypeGroupId}`;
            // axiosAdmin.get(url).then((response) => {

            setTimeout(() => {
                const allSalaryGroups = employeeTypeGroups.value;
                console.log("Checking Group:", allSalaryGroups);
                const selectedGroup = allSalaryGroups.find((group) => {
                    console.log("Checking Group:", group);
                    if (group.xid === employeTypeGroupId) {
                        formData.value.pf_enabled = group.pf_enabled;
                        formData.value.pf_percentage = group.pf_percentage;
                        formData.value.hra_percent_monthly = group.hra_percent;
                        formData.value.allowance_percent =
                            group.allowance_percent;
                        formData.value.food_allowance_percent =
                            group.food_allowance_percent;
                        formData.value.esi_enabled = group.esi_enabled;
                        formData.value.esi_percentage = group.esi_percentage;
                        formData.value.prof_tax_enabled =
                            group.prof_tax_enabled;
                        formData.value.prof_tax_percentage =
                            group.prof_tax_percentage;
                        formData.value.tds_enabled = group.tds_enabled;
                        formData.value.tds_percentage = group.tds_percentage;

                        formData.value.pf_fix_amount = group.pf_fix_amount;
                        formData.value.esi_fix_amount = group.esi_fix_amount;
                        formData.value.tds_fix_amount = group.tds_fix_amount;
                        formData.value.prof_tax_fix_amount =
                            group.prof_tax_fix_amount;

                        formData.value.ctc_value = group.basic_percent;
                        return true;
                    }
                    return false;
                });
                const rawGroup = toRaw(selectedGroup); // Removes proxy
                console.log(rawGroup); // Full object access
                console.log(JSON.parse(JSON.stringify(selectedGroup)));

                if (selectedGroup) {
                    employeeTypeComponentProps.value =
                        selectedGroup.salary_group_components;
                } else {
                    employeeTypeComponentProps.value = [];
                }
                console.log(
                    employeeTypeComponentProps.employeeTypeComponentProps
                );

                calculateSalary();
            }, 0);
            // });
        };
        const fetchSalaryComponentsAndUsers = (salaryGroupId) => {
            if (!salaryGroupId) {
                salaryGroupComponentProps.value = [];
                calculateSalary();
                return;
            }

            axiosAdmin.get(salaryGroupUrl).then((response) => {
                const allSalaryGroups = response.data;

                const selectedGroup = allSalaryGroups.find(
                    (group) => group.xid === salaryGroupId
                );

                if (selectedGroup) {
                    salaryGroupComponentProps.value =
                        selectedGroup.salary_group_components;
                } else {
                    salaryGroupComponentProps.value = [];
                }

                calculateSalary();
            });
        };

        // Computed properties
        const pfAmount = computed(() => {
            if (!formData.value?.pf_enabled) return 0;
            return formData.value.pf_percentage;
        });

        // Computed property for annual PF amount
        const annualPfAmount = computed(() => (pfAmount.value * 12).toFixed(2));

        const specialAllowance = computed(() =>
            (
                Number(monthlyCostToCompany.value) -
                Number(monthlySalary.value) -
                Number(earnings.value)
            ).toFixed(2)
        );

        const basicSalary = computed(() =>
            (
                Number(monthlySalary.value) +
                Number(specialAllowance.value) +
                Number(earnings.value) -
                Number(deductions.value)
            ).toFixed(2)
        );

        const netSalary = computed(() => {
            return (
                Number(formData.value.basic_salary) +
                Number(specialAllowance.value) +
                Number(earnings.value) -
                Number(deductions.value)
            ).toFixed(2);
        });

        const calculateEarningsAndDeductions = () => {
            earnings.value = 0;
            deductions.value = 0;
            componentIds.value = [];
            salaryComponents.value = [];

            salaryGroupComponentProps.value.forEach(
                ({ salary_component, xid }) => {
                    let amount = 0;

                    switch (salary_component.value_type) {
                        case "fixed":
                        case "variable":
                            amount = Number(salary_component.monthly) || 0;
                            break;

                        case "basic_percent":
                            amount =
                                (monthlySalary.value *
                                    Number(salary_component.monthly)) /
                                100 || 0;
                            break;

                        case "ctc_percent":
                            amount =
                                (monthlySalary.value *
                                    Number(salary_component.monthly)) /
                                formData.value.ctc_value || 0;
                            break;

                        default:
                            amount = 0;
                            break;
                    }

                    if (salary_component.type === "earnings") {
                        earnings.value += amount;
                    } else if (salary_component.type === "deductions") {
                        deductions.value += amount;
                    }

                    salaryComponents.value.push({
                        id: salary_component.xid,
                        type: salary_component.type,
                        value_type: salary_component.value_type,
                        monthly_value: amount,
                    });

                    componentIds.value.push(xid);
                }
            );
        };

        const calculateSalary = () => {
            calculateEarningsAndDeductions();

            const { calculation_type, ctc_value, monthly_ctc } = formData.value;
         
            if (formData.value.pf_enabled) {
                if (formData.value.pf_fix_amount) {
                    formData.value.monthly_pf = formData.value.pf_amount.toFixed(2);
                    formData.value.annual_pf = (12 * formData.value.pf_amount).toFixed(2);
                } else {
                    const pfAmount = (monthlySalary.value * formData.value.pf_percentage) / 100;
                    formData.value.monthly_pf = pfAmount.toFixed(2);
                    formData.value.annual_pf = (12 * pfAmount).toFixed(2);
                    // const pfAmount = (monthly_ctc * formData.value.pf_percentage) / 100;
                }
            }

            if (formData.value.hra_percent_monthly) {
                const hraAmount = (monthly_ctc * formData.value.hra_percent_monthly) / 100;
                formData.value.monthly_hra_percent_monthly = hraAmount.toFixed(2);
                formData.value.annual_hra_percent_monthly = (12 * hraAmount).toFixed(2);
            }

            if (formData.value.allowance_percent) {
                const allowanceAmount = (monthly_ctc * formData.value.allowance_percent) / 100;
                formData.value.monthly_allowance_percent = allowanceAmount.toFixed(2);
                formData.value.annual_allowance_percent = (12 * allowanceAmount).toFixed(2);
            }

            if (formData.value.food_allowance_percent) {
                const foodAllowanceAmount = (monthly_ctc * formData.value.food_allowance_percent) / 100;
                formData.value.monthly_food_allowance_percent = foodAllowanceAmount.toFixed(2);
                formData.value.annual_food_allowance_percent = (12 * foodAllowanceAmount).toFixed(2);
            }

            if (formData.value.esi_enabled) {
                if (formData.value.esi_fix_amount) {
                    formData.value.monthly_esi = formData.value.esi_percentage.toFixed(2);
                    formData.value.annual_esi = (12 * formData.value.esi_percentage).toFixed(2);
                } else {
                    const esiAmount = (monthlySalary.value * formData.value.esi_percentage) / 100;
                    formData.value.monthly_esi = esiAmount.toFixed(2);
                    formData.value.annual_esi = (12 * esiAmount).toFixed(2);
                }
            }

            if (formData.value.prof_tax_enabled) {
                if (formData.value.prof_tax_fix_amount) {
                    formData.value.monthly_prof_tax = formData.value.prof_tax_percentage;
                    formData.value.annual_prof_tax = (12 * formData.value.prof_tax_percentage).toFixed(2);
                } else {
                    const profTaxAmount = (monthlySalary.value * formData.value.prof_tax_percentage) / 100;
                    formData.value.monthly_prof_tax = profTaxAmount.toFixed(2);
                    formData.value.annual_prof_tax = (12 * profTaxAmount).toFixed(2);
                }
            }


            if (formData.value.tds_enabled) {
                if (formData.value.tds_fix_amount) {
                    formData.value.monthly_tds =
                        formData.value.tds_percentage.toFixed(2);
                    formData.value.annual_tds = (
                        12 * formData.value.tds_percentage
                    ).toFixed(2);
                } else {
                    formData.value.monthly_tds = (
                        (monthly_ctc * formData.value.tds_percentage) /
                        100
                    ).toFixed(2);

                    formData.value.annual_tds =
                        12 *
                        (
                            (monthly_ctc * formData.value.tds_percentage) /
                            100
                        ).toFixed(2);
                }
            }
            let ded =
                parseFloat(formData.value.monthly_prof_tax) +
                parseFloat(formData.value.monthly_pf) +
                parseFloat(formData.value.monthly_esi) +
                parseFloat(formData.value.monthly_tds);
            deductions.value = ded;

            let annual_ctc = monthly_ctc * 12;
            console.log(annual_ctc, monthly_ctc);
            formData.value.annual_ctc = annual_ctc;

            if (calculation_type === "fixed") {
                monthlySalary.value = ctc_value;
                annualSalary.value = ctc_value * 12;
            } else if (calculation_type === "%_of_ctc") {
                const percentage = Number(ctc_value);
                monthlySalary.value = (
                    (annual_ctc * percentage) /
                    100 /
                    12
                ).toFixed(2);
                annualSalary.value = ((annual_ctc * percentage) / 100).toFixed(
                    2
                );
            }

            monthlyCostToCompany.value = (annual_ctc / 12).toFixed(2);

            emit("updateSalaryData", {
                ...formData.value,
                xid: props.user.xid,
                basic_salary: monthlySalary.value,
                annual_amount: annualSalary.value,
                monthly_amount: basicSalary.value,
                salary_component_ids: componentIds.value,
                special_allowances: specialAllowance.value,
                salary_components: salaryComponents.value,
                net_salary: netSalary.value,
            });
        };

        const getMonthlyValue = (component) => {
            if (!component) return;

            const { value_type, monthly } = component.salary_component;
            const { ctc_value } = formData.value;

            switch (value_type) {
                case "fixed":
                    return Number(monthly) || 0;
                case "variable":
                    return Number(monthly) || 0;
                case "basic_percent":
                    return (monthlySalary.value * Number(monthly)) / 100 || 0;
                case "ctc_percent":
                    return (
                        (monthlySalary.value * Number(monthly)) / ctc_value || 0
                    );
                default:
                    return 0;
            }
        };

        const updateMonthlyValue = (value, component) => {
            if (!component) return;

            if (component.value_type === "variable") {
                component.monthly = parseFloat(value) || 0;

                const targetComponent = salaryComponents.value.find(
                    (item) => item.id === component.xid
                );

                if (targetComponent) {
                    targetComponent.monthly_value = component.monthly;
                } else {
                    salaryComponents.value.push({
                        id: component.xid,
                        type: component.type,
                        value_type: component.value_type,
                        monthly_value: component.monthly,
                    });
                }

                calculateSalary();
            }
        };

        const calculateAnnualValue = (component) => {
            return (getMonthlyValue(component) * 12).toFixed(2);
        };

        watch(
            () => props.visible,
            (newVal, oldVal) => {
                fetchSalaryGroups();
                if (newVal) {
                    formData.value = {
                        basic_salary: props.user.basic_salary || 0,
                        ctc_value: props.user.ctc_value || 50,
                        calculation_type:
                            props.user.calculation_type || "%_of_ctc",
                        annual_ctc: props.user.annual_ctc || 0,
                        monthly_ctc: props.user.monthly_ctc || 0,
                        monthly_amount: props.user.monthly_amount || 0,
                        annual_amount: props.user.annual_amount || 0,
                        salary_group_id: props.user.salary_group?.xid,
                        employee_type_id: props.user.employee_type_id?.xid,
                        pf_enabled: false,
                        pf_percentage: 0,
                        monthly_pf: 0,
                        annual_pf: 0,
                        esi_enabled: false,
                        esi_percentage: 0,
                        monthly_esi: 0,
                        annual_esi: 0,
                        prof_tax_enabled: false,
                        prof_tax_percentage: 0,
                        monthly_prof_tax: 0,
                        annual_prof_tax: 0,
                        tds_enabled: false,
                        tds_percentage: 0,
                        monthly_tds: 0,
                        annual_tds: 0,
                        hra_percent_monthly: 0,
                        monthly_hra_percent_monthly: 0,
                        annual_hra_percent_monthly: 0,
                        allowance_percent: 0,
                        monthly_allowance_percent: 0,
                        annual_allowance_percent: 0,
                        food_allowance_percent: 0,
                        monthly_food_allowance_percent: 0,
                        annual_food_allowance_percent: 0,
                        pf_fix_amount: 0,
                        esi_fix_amount: 0,
                        tds_fix_amount: 0,
                        prof_tax_fix_amount: 0,
                    };

                    if (
                        (props.user.annual_ctc != 0 &&
                            props.user.annual_ctc != null) ||
                        (props.user.monthly_ctc != 0 &&
                            props.user.monthly_ctc != null)
                    ) {
                        var allValues = [];

                        forEach(
                            props.user.salary_group?.salary_group_components,
                            (salComponent) => {
                                var findValueObject = find(
                                    props.user.basic_salary_details,
                                    {
                                        x_salary_component_id:
                                            salComponent.x_salary_component_id,
                                    }
                                );

                                if (findValueObject) {
                                    allValues.push({
                                        ...salComponent,
                                        salary_component: {
                                            ...salComponent.salary_component,
                                            monthly:
                                                findValueObject.value_type ===
                                                    "variable"
                                                    ? findValueObject.monthly
                                                    : salComponent
                                                        .salary_component
                                                        .monthly,
                                        },
                                    });
                                } else {
                                    allValues.push(salComponent);
                                }
                            }
                        );

                        salaryGroupComponentProps.value = allValues;
                    } else {
                        salaryGroupComponentProps.value =
                            props.user?.salary_group?.salary_group_components ||
                            [];
                    }

                    monthlySalary.value = props.user.monthly_amount || 0;
                    annualSalary.value = props.user.annual_amount || 0;
                    formData.value.salary_group_id =
                        props.user.salary_group?.xid;
                    if (props.user.salary_group) {
                        fetchSalaryComponentsAndUsers(
                            props.user.salary_group.xid
                        );
                    }

                    formData.value.employee_type_id =
                        props.user.employee_type_id?.xid;
                    if (props.user.employee_type_id) {
                        fetch_employee_type_components(
                            props.user.employee_type_id.xid
                        );
                    }
                    basicSalary.value = 0;
                    calculateSalary();
                }
            }
        );

        watch(
            [
                () => formData.value.annual_ctc,
                () => formData.value.monthly_ctc,
                () => formData.value.ctc_value,
                () => earnings.value,
                () => deductions.value,
                () => formData.value.monthly_ctc,
            ],
            () => {
                calculateSalary();
            }
        );

        return {
            loading,
            getMonthlyValue,
            updateMonthlyValue,
            calculateAnnualValue,
            rules,
            formData,
            appSetting,
            selectUsers,
            monthlySalary,
            annualSalary,
            earnings,
            deductions,
            specialAllowance,
            basicSalary,
            calculateSalary,
            monthlyCostToCompany,
            salaryComponents,
            salaryGroupComponentProps,
            salaryGroups,
            employeeTypeGroups,
            fetchSalaryComponentsAndUsers,
            fetch_employee_type_components,
            salaryGroupAdded,

            drawerWidth: window.innerWidth <= 991 ? "90%" : "60%",
        };
    },
});
</script>

<style scoped>
.column-text {
    text-align: left;
}

.styled-row {
    background-color: #f8f9fa;
    height: 55px;
    line-height: 55px;
    border-radius: 4px;
    margin-bottom: 10px;
    text-align: left;
}

.new-class {
    font-weight: bold;
}
</style>

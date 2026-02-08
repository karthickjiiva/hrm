<template>
    <a-spin :spinning="loading">
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
                    {{ (deductions * 12).toFixed(2) }}
                </div>
            </a-col>
        </a-row>
    </a-form>
    </a-spin>
</template>
<script>
import { nextTick, defineComponent, ref, computed, watch, onMounted } from "vue";
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
    const salaryGroupComponentProps = ref([]);
    const employeeTypeComponentProps = ref([]);

    const salaryGroupUrl =
        "employee_types?fields=id,xid,type,basic_percent,hra_percent,allowance_percent,food_allowance_percent,pf_enabled,pf_percentage,pf_limit,esi_enabled,esi_percentage,esi_limit,prof_tax_enabled,prof_tax_percentage,prof_tax_limit,tds_enabled,tds_percentage,tds_limit,status,pf_fix_amount,esi_fix_amount,tds_fix_amount,prof_tax_fix_amount&limit=1000";

    const employeeTypeGroupUrl = salaryGroupUrl;

  onMounted(async () => {
  loading.value = true;
  try {
    await Promise.all([fetchSalaryGroups(), fetchEmployeeTypeGroupUrl()]);
  } finally {
    loading.value = false;
  }
});

const fetchSalaryGroups = () => {
  return axiosAdmin.get(salaryGroupUrl).then((response) => {
    salaryGroups.value = response.data;
  });
};

const fetchEmployeeTypeGroupUrl = () => {
  return axiosAdmin.get(employeeTypeGroupUrl).then((response) => {
    employeeTypeGroups.value = response.data;
  });
};

    const salaryGroupAdded = () => {
        fetchSalaryGroups();
    };

    const fetch_employee_type_components = (employeeTypeId) => {
        if (!employeeTypeId) {
            employeeTypeComponentProps.value = [];
            calculateSalary();
            return;
        }

        setTimeout(() => {
            const allGroups = employeeTypeGroups.value;
            const selectedGroup = allGroups.find(
                (group) => group.xid === employeeTypeId
            );

            if (selectedGroup) {
                formData.value.pf_enabled = selectedGroup.pf_enabled;
                formData.value.pf_percentage = selectedGroup.pf_percentage;
                formData.value.hra_percent_monthly = selectedGroup.hra_percent;
                formData.value.allowance_percent = selectedGroup.allowance_percent;
                formData.value.food_allowance_percent =
                    selectedGroup.food_allowance_percent;
                formData.value.esi_enabled = selectedGroup.esi_enabled;
                formData.value.esi_percentage = selectedGroup.esi_percentage;
                formData.value.prof_tax_enabled = selectedGroup.prof_tax_enabled;
                formData.value.prof_tax_percentage =
                    selectedGroup.prof_tax_percentage;
                formData.value.tds_enabled = selectedGroup.tds_enabled;
                formData.value.tds_percentage = selectedGroup.tds_percentage;

                formData.value.pf_fix_amount = selectedGroup.pf_fix_amount;
                formData.value.esi_fix_amount = selectedGroup.esi_fix_amount;
                formData.value.tds_fix_amount = selectedGroup.tds_fix_amount;
                formData.value.prof_tax_fix_amount =
                    selectedGroup.prof_tax_fix_amount;

                formData.value.ctc_value = selectedGroup.basic_percent;

                employeeTypeComponentProps.value =
                    selectedGroup.salary_group_components || [];
            } else {
                employeeTypeComponentProps.value = [];
            }

            calculateSalary();
        }, 0);
    };

    const fetchSalaryComponentsAndUsers = (salaryGroupId) => {
        if (!salaryGroupId) {
            salaryGroupComponentProps.value = [];
            calculateSalary();
            return;
        }

        axiosAdmin.get(salaryGroupUrl).then((response) => {
            const selectedGroup = response.data.find(
                (group) => group.xid === salaryGroupId
            );

            salaryGroupComponentProps.value =
                selectedGroup?.salary_group_components || [];

            calculateSalary();
        });
    };

    // Computed
    const pfAmount = computed(() =>
        formData.value?.pf_enabled ? formData.value.pf_percentage : 0
    );

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

    const netSalary = computed(() =>
        (
            Number(formData.value.basic_salary) +
            Number(specialAllowance.value) +
            Number(earnings.value) -
            Number(deductions.value)
        ).toFixed(2)
    );

    // Methods
    const calculateEarningsAndDeductions = () => {
        earnings.value = 0;
        deductions.value = 0;
        componentIds.value = [];
        salaryComponents.value = [];

        salaryGroupComponentProps.value.forEach(({ salary_component, xid }) => {
            let amount = 0;
            switch (salary_component.value_type) {
                case "fixed":
                case "variable":
                    amount = Number(salary_component.monthly) || 0;
                    break;
                case "basic_percent":
                    amount = (monthlySalary.value * Number(salary_component.monthly)) / 100;
                    break;
                case "ctc_percent":
                    amount =
                        (monthlySalary.value * Number(salary_component.monthly)) /
                        formData.value.ctc_value;
                    break;
            }

            if (salary_component.type === "earnings") earnings.value += amount;
            else if (salary_component.type === "deductions") deductions.value += amount;

            salaryComponents.value.push({
                id: salary_component.xid,
                type: salary_component.type,
                value_type: salary_component.value_type,
                monthly_value: amount,
            });

            componentIds.value.push(xid);
        });
    };

    const calculateSalary = () => {
        calculateEarningsAndDeductions();
        const { calculation_type, ctc_value, monthly_ctc } = formData.value;

        formData.value.monthly_hra_percent_monthly = 0;
        formData.value.annual_hra_percent_monthly = 0;
        formData.value.monthly_allowance_percent = 0;
        formData.value.annual_allowance_percent = 0;
        formData.value.monthly_food_allowance_percent = 0;
        formData.value.annual_food_allowance_percent = 0;
        formData.value.monthly_pf = 0;
        formData.value.annual_pf = 0;
        formData.value.monthly_esi = 0;
        formData.value.annual_esi = 0;
        formData.value.monthly_prof_tax = 0;
        formData.value.annual_prof_tax = 0;
        formData.value.monthly_tds = 0;
        formData.value.annual_tds = 0;

        // PF
        if (formData.value.pf_enabled) {
            const pfAmountVal = formData.value.pf_fix_amount
                ? formData.value.pf_percentage
                : (monthlySalary.value * formData.value.pf_percentage) / 100;
            formData.value.monthly_pf = pfAmountVal.toFixed(2);
            formData.value.annual_pf = (12 * pfAmountVal).toFixed(2);
        }

        // HRA / allowances
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
            const foodAmount = (monthly_ctc * formData.value.food_allowance_percent) / 100;
            formData.value.monthly_food_allowance_percent = foodAmount.toFixed(2);
            formData.value.annual_food_allowance_percent = (12 * foodAmount).toFixed(2);
        }


        if (formData.value.esi_enabled) {
            const hraAmount = Number((monthly_ctc * formData.value.hra_percent_monthly) / 100);
            const baseSalary = Number(monthlySalary.value);
            const totalEarnings = baseSalary + hraAmount;
            const esiAmount = formData.value.esi_fix_amount
                ? formData.value.esi_percentage
                : (totalEarnings * formData.value.esi_percentage) / 100;
            formData.value.monthly_esi = esiAmount.toFixed(2);
            formData.value.annual_esi = (12 * esiAmount).toFixed(2);
        }

        // Prof tax
        if (formData.value.prof_tax_enabled) {
            const profTaxAmount = formData.value.prof_tax_fix_amount
                ? formData.value.prof_tax_percentage
                : (monthlySalary.value * formData.value.prof_tax_percentage) / 100;
            formData.value.monthly_prof_tax = profTaxAmount.toFixed(2);
            formData.value.annual_prof_tax = (12 * profTaxAmount).toFixed(2);
        }

        // TDS
        if (formData.value.tds_enabled) {
            const tdsAmount = formData.value.tds_fix_amount
                ? formData.value.tds_percentage
                : (monthly_ctc * formData.value.tds_percentage) / 100;
            formData.value.monthly_tds = tdsAmount.toFixed(2);
            formData.value.annual_tds = (12 * tdsAmount).toFixed(2);
        }

        deductions.value =
            parseFloat(formData.value.monthly_pf || 0) +
            parseFloat(formData.value.monthly_esi || 0) +
            parseFloat(formData.value.monthly_prof_tax || 0) +
            parseFloat(formData.value.monthly_tds || 0);

        const annual_ctc = monthly_ctc * 12;
        formData.value.annual_ctc = annual_ctc;

        if (calculation_type === "fixed") {
            monthlySalary.value = ctc_value;
            annualSalary.value = ctc_value * 12;
        } else if (calculation_type === "%_of_ctc") {
            const percentage = Number(ctc_value);
            monthlySalary.value = ((annual_ctc * percentage) / 100 / 12).toFixed(2);
            annualSalary.value = ((annual_ctc * percentage) / 100).toFixed(2);
        }

        monthlyCostToCompany.value = (annual_ctc / 12).toFixed(2);

        emit("updateSalaryData", {
            ...formData.value,
            xid: props.user.xid,
            basic_salary: monthlySalary.value,
            annual_amount: annualSalary.value,
            //monthly_amount: basicSalary.value,
            monthly_amount:monthly_ctc,
            salary_component_ids: componentIds.value,
            special_allowances: specialAllowance.value,
            salary_components: salaryComponents.value,
            net_salary: netSalary.value,
        });
    };

    const getMonthlyValue = (component) => {
        if (!component) return 0;
        const { value_type, monthly } = component.salary_component;
        const { ctc_value } = formData.value;

        switch (value_type) {
            case "fixed":
            case "variable":
                return Number(monthly) || 0;
            case "basic_percent":
                return (monthlySalary.value * Number(monthly)) / 100 || 0;
            case "ctc_percent":
                return (monthlySalary.value * Number(monthly)) / ctc_value || 0;
            default:
                return 0;
        }
    };

    const updateMonthlyValue = (value, component) => {
        if (!component) return;
        if (component.value_type === "variable") {
            component.monthly = parseFloat(value) || 0;

            const target = salaryComponents.value.find(
                (item) => item.id === component.xid
            );
            if (target) target.monthly_value = component.monthly;
            else
                salaryComponents.value.push({
                    id: component.xid,
                    type: component.type,
                    value_type: component.value_type,
                    monthly_value: component.monthly,
                });

            calculateSalary();
        }
    };

    const calculateAnnualValue = (component) =>
        (getMonthlyValue(component) * 12).toFixed(2);

 watch(
  [() => props.user, () => employeeTypeGroups.value],
  ([user, groups]) => {
    if (!user || !user.x_employee_type_id || !groups.length) return;
 
    formData.value.employee_type_id = user.x_employee_type_id;
    formData.value.monthly_ctc =
      user.monthly_amount !== undefined && user.monthly_amount !== null
        ? user.monthly_amount
        : 1200;

    fetch_employee_type_components(user.x_employee_type_id);
  },
  { immediate: true }
);

    watch(
        [
            () => formData.value.annual_ctc,
            () => formData.value.monthly_ctc,
            () => formData.value.ctc_value,
            () => earnings.value,
            () => deductions.value,
        ],
        () => calculateSalary()
    );

    return {
        loading,
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
        netSalary,
        monthlyCostToCompany,
        salaryComponents,
        salaryGroupComponentProps,
        salaryGroups,
        employeeTypeGroups,
        fetchSalaryComponentsAndUsers,
        fetch_employee_type_components,
        salaryGroupAdded,
        getMonthlyValue,
        updateMonthlyValue,
        calculateAnnualValue,
        calculateSalary,
        drawerWidth: window.innerWidth <= 991 ? "90%" : "60%",
    };
}

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

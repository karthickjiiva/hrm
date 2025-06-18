import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();

    const url = "bank_masters?fields=id,xid,account_number,bank_name,ifsc,micr,employee_id";

    const addEditUrl = "bank_masters";

    const hashableColumns = [];

    const initData = {
        account_number: '',
        bank_name: '',
        ifsc: '',
        micr: '',
        employee_id: null
    };

   const columns = [
    {
        title: 'Account Number',
        dataIndex: 'account_number',
        key: 'account_number',
    },
    {
        title: 'Bank Name',
        dataIndex: 'bank_name',
        key: 'bank_name',
    },
    {
        title: 'IFSC',
        dataIndex: 'ifsc',
        key: 'ifsc',
    },
    {
        title: 'MICR',
        dataIndex: 'micr',
        key: 'micr',
    },
    {
        title: 'Employee',
        dataIndex: ['employee', 'name'], // assumes relation `employee` exists and contains `name`
        key: 'employee_name',
    },
    {
        title: t("common.action"),
        dataIndex: "action",
    }
];


const filterableColumns = [
    {
        key: "employee_id",
        value: "Employee",
        type: "select",
        options: [], // Dynamically load employee list as { label: name, value: id } from API
        remote: true, // if using remote dropdown
        multiple: false,
    },
];


    return {
        url,
        initData,
        columns,
        filterableColumns,
        addEditUrl,
        hashableColumns
    };
};





export default fields;

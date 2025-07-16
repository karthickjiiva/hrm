import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();

    const url = "employee_leave_masters?fields=id,xid,sl,cl,el,employee_id";

    const addEditUrl = "employee_leave_masters";

    const hashableColumns = [];

    const initData = {
        employee_id: null,
        sl: 0,
        cl: 0,
        el: 0
    };

    const columns = [
        {
            title: 'Employee',
            dataIndex: ['employee', 'name'], // Assumes relation exists and returns employee.name
            key: 'employee_name',
        },
        {
            title: 'Sick Leave (SL)',
            dataIndex: 'sl',
            key: 'sl',
        },
        {
            title: 'Casual Leave (CL)',
            dataIndex: 'cl',
            key: 'cl',
        },
        {
            title: 'Earned Leave (EL)',
            dataIndex: 'el',
            key: 'el',
        },
        // {
        //     title: t("common.action"),
        //     dataIndex: "action",
        // }
    ];

    const filterableColumns = [
        {
            key: "employee_id",
            value: "Employee",
            type: "select",
            options: [], // Populate dynamically with { label: name, value: xid }
            remote: true,
            multiple: false,
        }
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

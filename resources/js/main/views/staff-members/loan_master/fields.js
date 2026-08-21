import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();

    const url = "employee_loans?fields=id,xid,employee_id,amount,monthly_amount,tenure,start_month,end_month";

    const addEditUrl = "employee_loans";

    const hashableColumns = [];

    const initData = {
        employee_id: null,
        amount: null,
        tenure: null,
        monthly_amount: null,
        start_month: null,
        end_month: null,
    };

    const columns = [
        {
            title: "Employee",
            dataIndex: ["employee", "name"],
            key: "employee_name",
        },
        {
            title: "Loan Amount",
            dataIndex: "amount",
            key: "amount",
            customRender: ({ text }) =>
                `₹ ${Number(text).toLocaleString("en-IN", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
            })}`,
        },       
        {
            title: "Tenure",
            dataIndex: "tenure",
            key: "tenure",
            customRender: ({ text }) => `${text} months`            
        },
        {
            title: "Monthly Amount",
            dataIndex: "monthly_amount",
            key: "monthly_amount",
            customRender: ({ text }) =>
                `₹ ${Number(text).toLocaleString("en-IN", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
            })}`,
        },
        {
            title: "Start From",
            dataIndex: "start_month",
            key: "start_month",
             customRender: ({ text }) => {
                const date = new Date(text);
                return date.toLocaleString('default', { month: 'short', year: 'numeric' });
            }
        },
        {
            title: "End On",
            dataIndex: "end_month",
            key: "end_month",
             customRender: ({ text }) => {
                const date = new Date(text);
                return date.toLocaleString('default', { month: 'short', year: 'numeric' });
            }
        },
        {
            title: t("common.action"),
            dataIndex: "action",
        },
    ];

    const filterableColumns = [
        {
            key: "employee.name",
            value: "Employee",
            type: "select",
            options: [],
            remote: true,
            multiple: false,
        },
    ];

    return {
        url,
        initData,
        columns,
        filterableColumns,
        addEditUrl,
        hashableColumns,
    };
};

export default fields;

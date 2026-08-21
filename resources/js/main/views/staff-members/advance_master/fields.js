import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();

    const url =
        "emp_advance?fields=id,xid,employee_id,amount,deduct_month,advance_type";

    const addEditUrl = "emp_advance";

    const hashableColumns = [];

    const initData = {
        employee_id: null,
        amount: null,
        deduct_month: null,
        advance_type: null,
    };

    const columns = [
        {
            title: "Employee",
            dataIndex: ["employee", "name"],
            key: "employee_name",
        },
        {
            title: "Amount",
            dataIndex: "amount",
            key: "amount",
            customRender: ({ text }) =>
                `₹ ${Number(text).toLocaleString("en-IN", {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2,
                })}`,
        },
        {
            title: "Month",
            dataIndex: "deduct_month",
            key: "deduct_month",
            customRender: ({ text }) => {
                const date = new Date(text);
                return date.toLocaleString("default", {
                    month: "short",
                    year: "numeric",
                });
            },
        },
        {
            title: "Advance Type",
            dataIndex: "advance_type",
            key: "advance_type",
            customRender: ({ text }) => {
                if (text === "salary_advance") return "Salary Advance";
                if (text === "site_advance") return "Site Advance";
                return text || "-";
            },
        },
        {
            title: t("common.action"),
            dataIndex: "action",
        },
    ];

    const filterableColumns = [
        {
            key: "advance_type",
            value: "Advance Type",
            type: "select",
            options: [
                { label: "Salary Advance", value: "salary_advance" },
                { label: "Site Advance", value: "site_advance" },
            ],
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

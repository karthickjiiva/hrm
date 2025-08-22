import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { h } from "vue";

const fields = () => {
    // API endpoint for bank statements
    const url = "bank_statements?fields=id,xid,employee_id,x_employee_id,employee.name,month,year,remark,amount";
    const addEditUrl = "bank_statements";
    const { t } = useI18n();

    const initData = {
        employee_id: null,
        month: null,
        year: null,
        remark: "",
        amount: 0,
    };

    const columns = ref([
       { title: t("bank.month"), dataIndex: "month" },
  { title: t("bank.year"), dataIndex: "year" },
        {
            title: t("common.action"),
            dataIndex: "action",
            width: 100,
            customRender: ({ record }) => {
                return h(
                    "a",
                    {
                        href: `/download/bank-statement/${record.xid}`,
                        target: "_blank",
                        class: "text-primary hover:underline",
                        onClick: (e) => {
                            e.preventDefault();
                            downloadStatement(record.xid);
                        }
                    },
                    t("common.download")
                );
            }
        }
    ]);

    const filterableColumns = [
        {
            key: "employee_id",
            value: t("bank.employee"),
        },
        {
            key: "month",
            value: t("Month"),
        },
        {
            key: "year",
            value: t("Year"),
        }
    ];

    const downloadStatement = (statementId) => {    
        console.log(`Downloading statement with ID: ${statementId}`);
        // here you can call an API to download PDF/Excel
    };

    return {
        url,
        addEditUrl,
        initData,
        columns,
        filterableColumns,
        downloadStatement
    };
};

export default fields;

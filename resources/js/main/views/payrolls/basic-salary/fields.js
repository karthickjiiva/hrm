import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { h } from "vue";

const fields = () => {
    const url = "payroll_new?fields=id,xid,employee_id,x_employee_id,employee.name,month,year,net_salary,net_salary_in_words";
    const addEditUrl = "payroll_new";
    const { t } = useI18n();

    const initData = {
        employee_id: null,
        month: null,
        year: null,
        actual_payable_days: 0,
        total_working_days: 0,
        loss_of_pay_days: 0,
        days_payable: 0,
        basic: 0,
        hra: 0,
        allowance: 0,
        food_allowance: 0,
        total_earnings: 0,
        pf_employee: 0,
        esi_employee: 0,
        total_contributions: 0,
        professional_tax: 0,
        tds: 0,
        total_taxes_deductions: 0,
        net_salary: 0,
        per_day_salary: 0
    };

    const columns = ref([
        {
            title: t("payroll.employee"),
            dataIndex: "employee.name",
        },
        {
            title: t("payroll.month_year"),
            dataIndex: "month_year",
            customRender: ({ record }) => {
                return `${record.month}/${record.year}`;
            }
        },
        {
            title: t("payroll.net_salary"),
            dataIndex: "net_salary",
            customRender: ({ text }) => {
                return text;
            }
        },
        {
            title: t("payroll.net_salary_in_words"),
            dataIndex: "net_salary_in_words",
        },
        {
            title: t("common.action"),
            dataIndex: "action",
            width: 100,
            customRender: ({ record }) => {
                return h(
                    "a",
                    {
                        href: `/download/payroll/${record.xid}`,
                        target: "_blank",
                        class: "text-primary hover:underline",
                        onClick: (e) => {
                            e.preventDefault();
                            downloadPayroll(record.xid);
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
            value: t("payroll.employee"),
        },
        {
            key: "month",
            value: t("payroll.month"),
        },
        {
            key: "year",
            value: t("payroll.year"),
        }
    ];

    const downloadPayroll = (payrollId) => {
        // Implement your download logic here
        // Example using axios:
        // axios.get(`/download/payroll/${payrollId}`, { responseType: 'blob' })
        //     .then(response => {
        //         const url = window.URL.createObjectURL(new Blob([response.data]));
        //         const link = document.createElement('a');
        //         link.href = url;
        //         link.setAttribute('download', `payroll_${payrollId}.pdf`);
        //         document.body.appendChild(link);
        //         link.click();
        //     });
        
        console.log(`Downloading payroll with ID: ${payrollId}`);
        // In a real implementation, this would trigger the file download
    };

    return {
        url,
        addEditUrl,
        initData,
        columns,
        filterableColumns,
        downloadPayroll
    };
};

export default fields;
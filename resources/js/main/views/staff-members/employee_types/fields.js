import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();
    //const url = "employee_types?fields=id,xid,type,basic_percent,hra_percent,allowance_percent,food_allowance_percent,pf_enabled,pf_percentage,pf_limit,esi_enabled,esi_percentage,esi_limit,prof_tax_enabled,prof_tax_percentage,prof_tax_limit,tds_enabled,tds_percentage,tds_limit,status,pf_pension_scheme";
    const url = "employee_types?fields=id,xid,type,basic_percent,hra_percent,allowance_percent,food_allowance_percent,pf_enabled,pf_percentage,pf_limit,pf_fix_amount,pf_pension_scheme,esi_enabled,esi_percentage,esi_limit,esi_fix_amount,prof_tax_enabled,prof_tax_percentage,prof_tax_limit,prof_tax_fix_amount,tds_enabled,tds_percentage,tds_limit,tds_fix_amount,status";
    const addEditUrl = "employee_types";
    const hashableColumns = [
    ];

    const initData = {
            type: '',
            basic_percent: 0,
            hra_percent: 0,
            allowance_percent: 0,
            food_allowance_percent: 0,
            pf_enabled: false,
            pf_percentage: 0,
            pf_fix_amount: false,
            esi_fix_amount: false,
            tds_fix_amount: false,
            prof_tax_fix_amount: false,
            pf_limit: 0,
            pf_pension_scheme: 0,
            esi_enabled: false,
            esi_percentage: 0,
            esi_limit: 0,
            prof_tax_enabled: false,
            prof_tax_percentage: 0,
            prof_tax_limit: 0,
            tds_enabled: false,
            tds_percentage: 0,
            tds_limit: 0,
            status:true
};

    const columns = [
            {
                title: 'Type',
                dataIndex: 'type',
                key: 'type',
            },
            {
                title: 'Basic %',
                dataIndex: 'basic_percent',
                key: 'basic_percent',
            },
            {
                title: 'HRA %',
                dataIndex: 'hra_percent',
                key: 'hra_percent',
            },
            {
                title: 'Allowance %',
                dataIndex: 'allowance_percent',
                key: 'allowance_percent',
            },
            {
                title: 'Food Allowance %',
                dataIndex: 'food_allowance_percent',
                key: 'food_allowance_percent',
            },

            // PF
            // {
            //     title: 'PF',
            //     dataIndex: 'pf_enabled',
            //     key: 'pf_enabled',
            //     customRender: ({ text }) => (text ? 'Yes' : 'No'),
            // },
            // {
            //     title: 'PF %',
            //     dataIndex: 'pf_percentage',
            //     key: 'pf_percentage',
            // },
            {
                title: 'PF',
                key: 'pf_percentage',
                customRender: ({ record }) => {
                    const value = record.pf_percentage ?? 0;
                    return record.pf_fix_amount ? `${value}` : `${value}%`;
                },
            },

            {
                title: 'PF Limit',
                dataIndex: 'pf_limit',
                key: 'pf_limit',
            },

            // ESI
            // {
            //     title: 'ESI',
            //     dataIndex: 'esi_enabled',
            //     key: 'esi_enabled',
            //     customRender: ({ text }) => (text ? 'Yes' : 'No'),
            // },
            // {
            //     title: 'ESI %',
            //     dataIndex: 'esi_percentage',
            //     key: 'esi_percentage',
            // },
             {
                title: 'ESI',
                key: 'esi_percentage',
                customRender: ({ record }) => {
                    const value = record.esi_percentage ?? 0;
                    return record.esi_fix_amount ? `${value}` : `${value}%`;
                },
            },
            {
                title: 'ESI Limit',
                dataIndex: 'esi_limit',
                key: 'esi_limit',
            },

            // Prof Tax
            // {
            //     title: 'Prof Tax',
            //     dataIndex: 'prof_tax_enabled',
            //     key: 'prof_tax_enabled',
            //     customRender: ({ text }) => (text ? 'Yes' : 'No'),
            // },
            // {
            //     title: 'Prof Tax %',
            //     dataIndex: 'prof_tax_percentage',
            //     key: 'prof_tax_percentage',
            // },
             {
                title: 'Prof Tax',
                key: 'prof_tax_percentage',
                customRender: ({ record }) => {
                    const value = record.prof_tax_percentage ?? 0;
                    return record.prof_tax_fix_amount ? `${value}` : `${value}%`;
                },
            },
            {
                title: 'Prof Tax Limit',
                dataIndex: 'prof_tax_limit',
                key: 'prof_tax_limit',
            },

            // TDS
            // {
            //     title: 'TDS',
            //     dataIndex: 'tds_enabled',
            //     key: 'tds_enabled',
            //     customRender: ({ text }) => (text ? 'Yes' : 'No'),
            // },
            // {
            //     title: 'TDS %',
            //     dataIndex: 'tds_percentage',
            //     key: 'tds_percentage',
            // },
             {
                title: 'TDS',
                key: 'tds_percentage',
                customRender: ({ record }) => {
                    const value = record.tds_percentage ?? 0;
                    return record.tds_fix_amount ? `${value}` : `${value}%`;
                },
            },
            {
                title: 'TDS Limit',
                dataIndex: 'tds_limit',
                key: 'tds_limit',
            },

            // Actions (Edit/Delete, optional)
           {
                title: t("common.action"),
                dataIndex: "action",
            },
            ];

 const filterableColumns = [
    {
        key: "type",
        value: "Type",
    },
    {
        key: "status",
        value: "Status",
        options: [
            { label: "Active", value: "active" },
            { label: "Inactive", value: "inactive" }
        ]
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

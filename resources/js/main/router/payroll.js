export default [
    {
        path: "/admin/payrolls/",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "pre-payments",
                component: () =>
                    import("../views/payrolls/pre-payments/index.vue"),
                name: "admin.pre_payments.index",
                meta: {
                    requireAuth: true,
                    menuParent: "payrolls",
                    menuKey: (route) => "pre_payments",
                    permission: "pre_payments_view",
                },
            },
            {
                path: "increments-promotions",
                component: () =>
                    import("../views/payrolls/increments-promotions/index.vue"),
                name: "admin.increments_promotions.index",
                meta: {
                    requireAuth: true,
                    menuParent: "payrolls",
                    menuKey: (route) => "increments_promotions",
                    permission: "increments_promotions_view",
                },
            },
            {
                path: "payrolls",
                component: () => import("../views/payrolls/payroll/index.vue"),
                name: "admin.payrolls.index",
                meta: {
                    requireAuth: true,
                    menuParent: "payrolls",
                    menuKey: (route) => "payrolls",
                    permission: "payrolls_view",
                },
            },
            // {
            //     path: "basic-salaries",
            //     component: () =>
            //         import("../views/payrolls/basic-salary/index.vue"),
            //     name: "admin.basic_salaries.index",
            //     meta: {
            //         requireAuth: true,
            //         menuParent: "payrolls",
            //         menuKey: (route) => "basic_salaries",
            //         permission: "salary_settings",
            //     },
            // },
            {
                path: "basic-salaries",
                component: () =>
                    import("../views/payrolls/basic-salary/generate_payroll.vue"),
                name: "admin.basic_salaries.generate_payroll",
                meta: {
                    requireAuth: true,
                    menuParent: "payrolls",
                    menuKey: (route) => "basic_salaries",
                    permission: "salary_settings",
                },
            },
            {
                path: "payroll_reportexcel",
                component: () =>
                    import("../views/payrolls/basic-salary/payroll_reportexcel.vue"),
                name: "admin.basic_salaries.payroll_reportexcel",
                meta: {
                    requireAuth: true,
                    menuParent: "allreports",
                    menuKey: (route) => "payroll_reportexcel",
                    permission: "salary_settings",
                },
            },           
            {
                path: "payroll_report",
                component: () =>
                    import("../views/payrolls/basic-salary/payroll_report.vue"),
                name: "admin.basic_salaries.payroll_report",
                meta: {
                    requireAuth: true,
                    menuParent: "payrolls",
                    menuKey: (route) => "payroll_report",
                    permission: "salary_settings",
                },
            },
            {
                path: "bank_reportexcel",
                component: () =>
                    import("../views/banks/bank-reports/bank_reportexcel.vue"),
                name: "admin.bank_reports.bank_reportexcel",
                meta: {
                    requireAuth: true,
                    menuParent: "allreports",
                    menuKey: (route) => "bank_reportexcel",
                    permission: "salary_settings",
                },
            },
             {
                path: "leave_salaryreport",
                component: () =>
                    import("../views/employee/leaves/leave_salaryreport.vue"),
                name: "admin.leave.leave_salaryreport",
                meta: {
                    requireAuth: true,
                    menuParent: "allreports",
                    menuKey: (route) => "leave_salaryreport",
                    permission: "salary_settings",
                },
            },
        ],
    },
];

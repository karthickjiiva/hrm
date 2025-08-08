export default [
    {
        path: "/admin/users",
        component: () => import("../../common/layouts/Admin.vue"),
        children: [
            {
                path: "/admin/staffs",
                component: () =>
                    import("../views/staff-members/users/index.vue"),
                name: "admin.staffs.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: "staff",
                    permission: "users_view",
                },
            },
            {
                path: "departments",
                component: () =>
                    import("../views/staff-members/departments/index.vue"),
                name: "admin.departments.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: (route) => "departments",
                    permission: "departments_view",
                },
            },
            {
                path: "designations",
                component: () =>
                    import("../views/staff-members/designations/index.vue"),
                name: "admin.designations.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: (route) => "designations",
                    permission: "designations_view",
                },
            },
            {
                path: "shifts",
                component: () =>
                    import("../views/staff-members/shifts/index.vue"),
                name: "admin.shifts.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: (route) => "shifts",
                    permission: "shifts_view",
                },
            },
            {
                path: "employees/:id",
                component: () =>
                    import("../views/staff-members/users/index.vue"),
                name: "admin.employees.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: (route) => "employees",
                    permission: "employees_view",
                },
            },
            {
                path: "/admin/employee_types",
                component: () =>
                    import("../views/staff-members/employee_types/index.vue"),
                name: "admin.employee_types.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: "employee_type",
                    permission: "users_view",
                },
            },
            {
                path: "/admin/employee_leave_master",
                component: () =>
                    import("../views/staff-members/employee_leave_master/index.vue"),
                name: "admin.employee_leave_master.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: "employee_leave_master",
                    permission: "users_view",
                },
            },
            {
                path: "/admin/bank_master",
                component: () =>
                    import("../views/staff-members/bank_master/index.vue"),
                name: "admin.bank_master.index",
                meta: {
                    requireAuth: true,
                    menuParent: "staff",
                    menuKey: "bank_master",
                    permission: "users_view",
                },
            },
            {
                path: "/admin/loan_master",
                component: () =>
                    import("../views/staff-members/loan_master/index.vue"),
                name: "admin.loan_master.index",
                meta: {
                    requireAuth: true,
                    menuParent: "myloans",
                    menuKey: "loan_master",
                    permission: "users_view",
                },
            },
              {
                path: "/admin/advance_master",
                component: () =>
                    import("../views/staff-members/advance_master/index.vue"),
                name: "admin.advance_master.index",
                meta: {
                    requireAuth: true,
                    menuParent: "myloans",
                    menuKey: "advance_master",
                    permission: "users_view",
                },
            },
        ],
    },
];

import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const { user } = common();
    const { t } = useI18n();
    const url = "users?fields=id,xid,user_type,name,email,employee_number,gender,allow_login,dob,joining_date,is_married,marriage_date,spouse_name,education,blood_group,personal_email,personal_phone,report_to,x_report_to,is_manager,visibility,reporter{id,xid,name},location_id,x_location_id,location{id,xid,name},employee_type_id,x_employee_type_id,employeeType{id,xid,type},designation_id,x_designation_id,designation{id,xid,name},profile_image,profile_image_url,phone,address,status,created_at,role_id,x_role_id,role{id,xid,name,display_name},basic_salary,probation_start_date,probation_end_date,notice_start_date,notice_end_date,duration,shift_id,x_shift_id,shift{id,xid,name},department_id,x_department_id,department{id,xid,name},end_date,annual_ctc,salary_group_id,x_salary_group_id,salaryGroup{id,xid},employee_status_id,x_employee_status_id,employeeWorkStatus{id,xid,name},uan_number,pf_number,esi_number,pan_number,aadhar_number,monthly_amount,emergency_contact_name,emergency_contact_number,alternate_phone,has_resigned,resignation_date,resignation_reason,has_rejoined,rejoining_date,rejoining_reason,";
    const addEditUrl = "users";
    const hashableColumns = [
        "location_id",
        "report_to",
        "department_id",
        "designation_id",
        "salary_group_id",
        "shift_id",
        "employee_status_id",
        "employee_type_id",
    ];

    const initData = {
        name: "",
        email: "",
        employee_number: "",
        gender: "female",
        allow_login: 1,
        is_manager: 0,
        dob: undefined,
        joining_date: undefined,
        is_married: undefined,
        marriage_date: undefined,
        spouse_name: "",               // ✅ ADD THIS
    education: "",                 // ✅ ADD THIS
    blood_group: "",               // ✅ ADD THIS
        personal_email: undefined,
        personal_phone: "",
        report_to: user.value.xid,
        password: "",
        profile_image: undefined,
        profile_image_url: undefined,
        phone: "",
        address: "",
        status: "active",
        user_type: "staff_members",
        location_id: user.value.x_location_id,
        employee_type_id: undefined,
        shift_id: undefined,
        probation_start_date: undefined,
        probation_end_date: undefined,
        notice_start_date: undefined,
        notice_end_date: undefined,
        department_id: user.value.x_department_id,
        designation_id: undefined,
        salary_group_id: undefined,
        employee_status_id: undefined,
        uan_number: "",
        pf_number: "",
        esi_number: "",
        pan_number: "",
        aadhar_number: "",
        monthly_amount: "",
        emergency_contact_name:"",
        emergency_contact_number:"",
        alternate_phone:"",
        has_resigned: false,
  resignation_date: null,
  resignation_reason: '',
  has_rejoined: false,
  rejoining_date: null,
  rejoining_reason: ''
    };

    const columns = [
        {
            title: t("user.name"),
            dataIndex: "name",
            key: "name",
        },
        {
            title: t("user.report_to"),
            dataIndex: "report_to",
            key: "report_to",
        },
        {
            title: t("user.working_email"),
            dataIndex: "email",
        },
        {
            title: t("user.department"),
            dataIndex: "department",
            key: "department",
        },
        {
            title: t("user.location_id"),
            dataIndex: "location_id",
            key: "location_id",
        },
        {
            title: "Employee Type",
            key: "employee_type",
            customRender: ({ record }) => record.employee_type?.type || "-",
            },
        // {
        //     title: t("user.duration"),
        //     dataIndex: "duration",
        // },
        {
            title: t("user.status"),
            dataIndex: "status",
            key: "status",
        },
        {
            title: t("common.action"),
            dataIndex: "action",
        },
    ];

    const filterableColumns = [
        {
            key: "name",
            value: t("user.name"),
        },
        {
            key: "email",
            value: t("user.working_email"),
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

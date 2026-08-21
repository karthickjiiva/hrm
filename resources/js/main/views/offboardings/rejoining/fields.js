import { useI18n } from "vue-i18n";
import common from "@/common/composable/common";

const fields = () => {
    const url = "rejoining?fields=id,xid,title,description,resignated_date,rejoined_date,user_id,x_user_id,user{id,xid,name,profile_image,profile_image_url},user:designation{id,xid,name},user:location{id,xid,name}";

    const addEditUrl = "rejoining";


    const hashableColumns = ["user_id"];

    const { t } = useI18n();
    const { dayjs } = common();

    const multiDimensalObjectColumns = {};

    const initData = {
        title: "",
        description: "",
        resignated_date: dayjs().utc().format("YYYY-MM-DDTHH:mm:ssZ"),
        rejoined_date: dayjs().utc().format("YYYY-MM-DDTHH:mm:ssZ"),
        user_id: undefined,
    };

   const columns = [
    // {
    //     title: "Title",
    //     dataIndex: "title",
    // },
    {
        title: "User",
        dataIndex: "user_id",
    },
    {
        title: "Description",
        dataIndex: "description",
    },
    {
        title: "Resignated Date",
        dataIndex: "resignated_date",
    },
    {
        title: "Rejoined Date",
        dataIndex: "rejoined_date",
    },
    {
        title: "Action",
        dataIndex: "action",
    },
];

const filterableColumns = [
    {
        key: "user_name", // ✅ flat key expected by API
        value: "User Name",
    },
];


    return {
        url,
        addEditUrl,
        initData,
        columns,
        filterableColumns,
        hashableColumns,
        multiDimensalObjectColumns,
    };
};

export default fields;

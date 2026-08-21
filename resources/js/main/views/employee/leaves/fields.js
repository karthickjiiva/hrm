import { ref } from "vue";
import { useI18n } from "vue-i18n";
import { h } from "vue";

const fields = () => {
  const url = "salary_leave?fields=id,month,year"; 
  const addEditUrl = "salary_leave";
  const { t } = useI18n();

  const initData = {
    month: null,
    year: null,
  };

  const columns = ref([
    { title: t("Month"), dataIndex: "month" },
    { title: t("Year"), dataIndex: "year" },
    {
      title: t("common.action"),
      dataIndex: "action",
      width: 100,
      customRender: ({ record }) =>
        h(
          "a",
          {
            href: `/download/salary-leave/${record.xid}`,
            target: "_blank",
            class: "text-primary hover:underline",
            onClick: (e) => {
              e.preventDefault();
              downloadLeaveSummary(record.xid);
            },
          },
          t("common.download")
        ),
    },
  ]);

  const filterableColumns = [
    { key: "month", value: t("Month") },
    { key: "year", value: t("Year") },
  ];

  return { url, addEditUrl, initData, columns, filterableColumns };
};

export default fields;

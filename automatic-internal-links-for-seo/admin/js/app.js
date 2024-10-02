import {
  addItem,
  findItem,
  updateItem,
  updateStatus,
  deleteItem,
} from "./modules/crud.js?232432";
import {
  bulkAdd,
  bulkStop,
  bulkDelete,
  bulkFetch,
} from "./modules/bulk.js?32133";
import {
  useCustom,
  truncate,
  selectAll,
  onPageChange,
  countDown,
} from "./modules/utils.js";

Vue.component("v-select", VueSelect.VueSelect);
Vue.use(VuePaginate);

let links = document.getElementById("app_autolink_links");

if (links) {
  const app = new Vue({
    el: "#app_autolink_links",
    data: {
      items: [],
      logs: [],
      all_pages: data.all_pages,
      total_items: 0,
      total_pages: 0,
      syncRequired: [],
      sync: false,
      ids: [],
      item: "",
      search: "",
      selected: "",
      form: {
        use_custom: 0,
        priority: 0,
        max_links: 1,
      },
      settings: {},
      showEdit: false,
      paginate: ["items"],
      perPage: 30,
      disabled: false,
      delayed: false,
      errors: [],
      count: 5,
      selectAllCheckbox: false,
      itemsProcessed: 0,
      progress: 0,
      storingProgress: 0,
      stopFlag: false,
      stopFetchBtn: false,
      stopStoreBtn: false,
    },
    mounted() {
      if (
        typeof data.total_pages_and_items === "object" &&
        data.total_pages_and_items !== null
      ) {
        this.total_items = data.total_pages_and_items.items;
        this.total_pages = data.total_pages_and_items.pages;
      }

      if (data.items && data.items.length) {
        let props = [
          "id",
          "post_id",
          "new_tab",
          "nofollow",
          "partial_match",
          "bold",
          "case_sensitive",
          "priority",
          "max_links",
          "use_custom",
          "status",
        ];

        data.items.forEach(item => {
          props.forEach(prop => {
            item[prop] = Number(item[prop]);
          });
          // item['created_at'] = new Date(item['created_at'].replace(' ', 'T')).getTime();
          // item['created_at'] = item['created_at']+ data.timezone;
          this.items.push(item);
        });
      }

      // console.log(data);
    },
    methods: {
      addItem,
      updateItem,
      updateStatus,
      deleteItem,
      findItem,
      useCustom,
      bulkAdd,
      bulkStop,
      bulkDelete,
      bulkFetch,
      truncate,
      selectAll,
      onPageChange,
      countDown,
      compareDate(val) {
        return new Date(val);
      },
    },
    computed: {
      filteredItems() {
        if (this.search) {
          return this.items.filter(item => {
            return (
              item.title.toLowerCase().match(this.search.toLowerCase()) ||
              item.keyword.toLowerCase().match(this.search.toLowerCase())
            );
          });
        } else {
          return this.items;
        }
      },
    },
  });
}

jQuery(document).ready(function () {
  jQuery("#fs_connect button[type=submit]").on("click", function (e) {
    window.open(
      "https://better-robots.com/subscribe.php?plugin=autolinks",
      "autolinks",
      "resizable,height=400,width=700"
    );
  });

  jQuery("#enable_override").on("click", function () {
    jQuery("#override_options").fadeToggle();
  });
});

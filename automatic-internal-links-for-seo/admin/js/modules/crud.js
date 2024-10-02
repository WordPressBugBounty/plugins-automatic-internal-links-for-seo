export function addItem(e) {
  this.disabled = true;
  this.errors = [];

  // if (!this.form.title) this.errors.push("Title is required.");

  let postData = Object.assign(
    {},
    {
      action: "ails_create_link",
      nonce: data.nonce,
      post_id: this.selected,
      title: this.form.title,
      keyword: this.form.keyword,
      url: this.form.url,
      new_tab: this.form.new_tab ? 1 : 0,
      nofollow: this.form.nofollow ? 1 : 0,
      partial_match: this.form.partial_match ? 1 : 0,
      bold: this.form.bold ? 1 : 0,
      case_sensitive: this.form.case_sensitive ? 1 : 0,
    },
    this.form
  );
  console.log(postData);

  axios
    .post(ajaxurl, Qs.stringify(postData))
    .then(response => {
      console.log(response.data);
      if (response.data.success == true) {
        Swal.fire({
          title: "Success!",
          text: response.data.data.message,
          icon: "success",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        }).then(() => {
          this.items.unshift(response.data.data.link);

          this.form = {};

          let use_custom = postData.use_custom === 0 ? 0 : 1;

          this.form = {
            use_custom: use_custom,
            status: 1,
            priority: 0,
            max_links: 3,
          };

          this.form.status = 1;

          this.selected = "";

          this.disabled = false;
        });
      } else {
        // display error message
        Swal.fire({
          title: "Error! 70001",
          text: "Something went wrong",
          icon: "error",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });

        this.disabled = false;
      }
    })
    .catch(error => {
      // console.log(error.response)
      this.errors =
        error &&
        error.response &&
        error.response.data &&
        error.response.data.data
          ? error.response.data.data.errors
          : "Something went wrong";

      Swal.fire({
        toast: true,
        position: "bottom-end",
        title: "Error!",
        text: "Please correct the error(s)",
        icon: "error",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
      }).then(() => {
        this.disabled = false;
      });
      // console.log(this.errors)
    });

  e.preventDefault();
}

export function findItem(id) {
  this.errors = [];
  let item = this.items.find(x => x.id === id);
  if (typeof item === "object" && item !== null) {
    let props = [
      "id",
      "post_id",
      "new_tab",
      "nofollow",
      "partial_match",
      "case_sensitive",
      "priority",
      "max_links",
      "use_custom",
      "status",
    ];

    props.forEach(prop => {
      item[prop] = Number(item[prop]);
    });

    this.item = item;
    // console.log(this.item)
    this.item.post_id = Number(this.item.post_id);
    this.showEdit = true;
  } else {
    Swal.fire({
      toast: true,
      position: "bottom-end",
      title: "Error! 70002",
      text: "The item with id: " + id + " doesn't exist",
      icon: "error",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    });
  }
}

export function updateItem(id) {
  this.disabled = true;
  this.errors = [];

  let postData = {
    action: "ails_update_link",
    nonce: data.nonce,
    id: id,
    post_id: this.item.post_id,
    title: this.item.title,
    keyword: this.item.keyword,
    url: this.item.url,
    new_tab: this.item.new_tab ? "1" : "0",
    nofollow: this.item.nofollow ? "1" : "0",
    partial_match: this.item.partial_match ? "1" : "0",
    bold: this.item.bold ? "1" : "0",
    case_sensitive: this.item.case_sensitive ? "1" : "0",
    priority: this.item.priority,
    max_links: this.item.max_links,
  };
  console.log(postData);

  axios
    .post(ajaxurl, Qs.stringify(postData))
    .then(response => {
      console.log(response.data);
      if (response.data.success == true) {
        // display success message
        Swal.fire({
          title: "Success!",
          text: response.data.data.message,
          icon: "success",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });

        this.disabled = false;

        this.showEdit = false;
      } else {
        // display error message
        Swal.fire({
          title: "Error! 70003",
          text: "Something went wrong",
          icon: "error",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        }).then(() => {
          this.disabled = false;
        });
        console.log(response);
      }
    })
    .catch(error => {
      console.log(error.response);
      this.errors = error.response.data.data.errors;

      Swal.fire({
        toast: true,
        position: "bottom-end",
        title: "Error!",
        text: "Please correct the error(s)",
        icon: "error",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
      }).then(() => {
        this.disabled = false;
      });
    });
}

export function updateStatus(id) {
  let item = this.items.find(x => x.id === id);
  if (typeof item === "object" && item !== null) {
    this.item = item;
  } else {
    Swal.fire({
      toast: true,
      position: "bottom-end",
      title: "Error! 70004",
      text: "The item with id: " + id + " doesn't exist",
      icon: "error",
      showConfirmButton: false,
      timer: 2000,
      timerProgressBar: true,
    });
  }

  let postData = {
    action: "ails_update_status",
    nonce: data.nonce,
    id: id,
    status: this.item.status,
  };

  axios
    .post(ajaxurl, Qs.stringify(postData))
    .then(response => {
      // console.log( response.data )
      if (response.data.success == true) {
        // display success message
        Swal.fire({
          toast: true,
          position: "bottom-end",
          title: "Success!",
          text: response.data.data.message,
          icon: "success",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });
      } else {
        // display error message
        Swal.fire({
          title: "Error! 70005",
          text: "Something went wrong",
          icon: "error",
          showConfirmButton: false,
          timer: 2000,
          timerProgressBar: true,
        });
        // console.log(response)
      }
    })
    .catch(error => {
      console.log(error.response.data);
      Swal.fire({
        title: "Error!",
        text: error,
        icon: "error",
        showConfirmButton: false,
        timer: 2000,
        timerProgressBar: true,
      });
    });
}

export function deleteItem(id, table) {
  this.showEdit = false;

  Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#3085d6",
    cancelButtonColor: "#d33",
    confirmButtonText: "Yes, delete it!",
  }).then(result => {
    if (result.isConfirmed) {
      let postData = {
        action: "ails_delete_item",
        nonce: data.nonce,
        id: id,
        table: table ? table : "",
      };

      axios
        .post(ajaxurl, Qs.stringify(postData))
        .then(response => {
          // console.log( response.data )

          if (response.data.success == true) {
            // Find Index
            let objIndex = this.items.findIndex(obj => obj.id == id);

            // Delete item from Current Array
            this.items.splice(objIndex, 1);
          } else {
            // display error message
            Swal.fire("Error", "Something went wrong.", "error");
          }
        })
        .catch(error => console.log(error));
    }
  });
}

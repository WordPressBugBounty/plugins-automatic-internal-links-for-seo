export async function bulkFetch() {
  this.syncRequired = [];
  this.stopFetchBtn = true;
  const fetchData = async page => {
    if (this.stopFlag) {
      return;
    }

    this.errors = [];
    const batchSize = Number(data.batch_size);
    let offset = (page - 1) * batchSize;

    let postData = {
      action: "ails_bulk_fetch",
      nonce: data.nonce,
      batchSize: batchSize,
      page: page,
      offset: offset,
      totalPages: this.total_pages,
    };

    try {
      const response = await axios.post(ajaxurl, Qs.stringify(postData));
      // console.log(response.data.data);
      response.data.data.items.forEach(item => {
        this.syncRequired.push(item);
      });
      this.progress = Math.round(response.data.data.progress);

      if (page < this.total_pages) {
        // Wait for half second before fetching the next batch of data
        await new Promise(resolve => setTimeout(resolve, 500));
        // Call the function recursively to fetch the next batch of data
        await fetchData(page + 1);
      } else {
        this.sync = true;
        this.disabled = false;
        this.stopFetchBtn = false;
        // console.log(this.syncRequired);
      }
    } catch (error) {
      console.log(error.response);
      this.errors = error.response.data.data.errors;
      this.disabled = false;
      this.stopFetchBtn = false;
      this.sync = false;
    }
  };

  if (this.total_pages > 0) {
    await fetchData(1); // Call the function to fetch the first batch of data
  }
}

export async function bulkAdd() {
  this.disabled = true;
  this.stopStoreBtn = true;
  let count = 0;
  let totalItems = this.syncRequired.length;

  const storeItem = async itemIndex => {
    if (this.stopFlag) {
      return;
    }

    // Set post variable to passed item
    let post = this.syncRequired[itemIndex];

    // Assign post data for Axios request
    let postData = Object.assign(
      {},
      {
        action: "ails_bulk_add",
        nonce: data.nonce,
        post_id: post.id,
        title: post.title,
        keyword: post.keyword,
        url: post.url,
        new_tab: this.form.new_tab ? 1 : 0,
        nofollow: this.form.nofollow ? 1 : 0,
        partial_match: this.form.partial_match ? 1 : 0,
        bold: this.form.bold ? 1 : 0,
        case_sensitive: this.form.case_sensitive ? 1 : 0,
      },
      this.form
    );

    try {
      const response = await axios.post(ajaxurl, Qs.stringify(postData));

      if (response && response.data && response.data.success === true) {
        // SET THE STATUS TO 200 IF ITEM IS ADDED SUCCESSFULLY
        response.data.data.link.status = 200;
        //PUSH LINK (ON TOP) TO CONSOLE LOG ARRAY
        this.logs.unshift(response.data.data.link);

        // Calculate progress bar
        count++;
        this.storingProgress = Math.round((count / totalItems) * 100);
      }

      if (itemIndex < this.syncRequired.length - 1) {
        // Wait for half second before fetching the next batch of data
        await new Promise(resolve => setTimeout(resolve, 500));

        // Call the function recursively to fetch the next batch of data
        await storeItem(itemIndex + 1);
      } else {
        // Set syncRequired items to empty array
        this.syncRequired = [];
        // Set disable to false
        // this.disabled = false;
        // Hide Stop Button for Sync Now
        this.stopStoreBtn = false;
        // SET LAST SYNC DATE in WORDPRESS OPTIONS
        let postData = {
          action: "ails_sync_date",
          nonce: data.nonce,
          alldone: true,
        };

        axios.post(ajaxurl, Qs.stringify(postData));
      }
    } catch (error) {
      console.log(error);
      if (error && error.response) {
        console.log(error.response);
        console.log(error.response.data);
      }
      this.errors =
        error.response && error.response.data && error.response.data
          ? error.response.data.data.errors
          : "An error occurred";
      this.disabled = false;
      this.stopStoreBtn = false;
    }
  };

  if (this.syncRequired.length > 0) {
    await storeItem(0); // Call the function to fetch the first batch of data
  }
}

export function bulkStop() {
  this.disabled = false;
  this.stopStoreBtn = false;
  // SET LAST SYNC DATE in WORDPRESS OPTIONS
  let postData = {
    action: "ails_sync_date",
    nonce: data.nonce,
    alldone: true,
  };

  axios.post(ajaxurl, Qs.stringify(postData));
}

export function bulkDelete(ids, table) {
  Swal.fire({
    title: "Are you sure?",
    text: "You won't be able to revert this!",
    icon: "warning",
    showCancelButton: true,
    confirmButtonColor: "#d33",
    cancelButtonColor: "#3085d6",
    confirmButtonText: "Yes, delete all selected items!",
  }).then(result => {
    if (result.isConfirmed) {
      ids.forEach((id, index, array) => {
        let postData = {
          action: "ails_delete_item",
          nonce: data.nonce,
          id: id,
          table: table ? table : "",
        };

        axios
          .post(ajaxurl, Qs.stringify(postData))
          .then(response => {
            console.log(response.data);

            if (response.data.success == true) {
              // FIND ITEM INDEX IN ALL ITEMS ARRAY
              let objIndex = this.items.findIndex(obj => obj.id == id);

              // DELETE ITEM FROM CURRENT ARRAY
              this.items.splice(objIndex, 1);
            } else {
              // DISPLAY ERROR MESSAGE IF SOMETHING WENT WRONG
              Swal.fire("Error", "Something went wrong.", "error");
            }
          })
          .catch(error => console.log(error))
          .then(() => {
            // SET ID'S ARRAY TO EMPTY STATE ONCE ALL ITEMS ARE DELETED
            this.itemsProcessed++;
            if (this.itemsProcessed === array.length) {
              this.ids = [];
            }
          });
      });
    }
  });
}

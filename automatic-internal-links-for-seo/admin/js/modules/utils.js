export function truncate(input, len) {
    if (input != null && input.length > len) {
       return input.substring(0, len) + '...';
    }
    return input;
}

export function useCustom(val) {
    if (val === "true"){
        this.selected = ""
    }

    if (val === "false"){
        this.form.url = ""
    }

}

export function timeSince(date) {

    var seconds = Math.floor(((new Date().getTime()/1000) - date)),
    interval = Math.floor(seconds / 31536000);

    if (interval > 1) return interval + " years ago";

    interval = Math.floor(seconds / 2592000);
    if (interval > 1) return interval + " months ago";

    interval = Math.floor(seconds / 86400);
    if (interval >= 1) return interval + " days ago";

    interval = Math.floor(seconds / 3600);
    if (interval >= 1) return interval + " hours ago";

    interval = Math.floor(seconds / 60);
    if (interval > 1) return interval + " minutes ago";

    return Math.floor(seconds) + " seconds ago";
}

export function selectAll(e) {
    if (e.target.checked === true) {
        this.paginate.items.list.forEach( item => {
            this.ids.push(item.id);
        })
        // console.log(this.paginate.items.list)
        console.log(this.ids)
    } else {
        this.ids.splice(0, this.perPage);
    }
}

export function onPageChange(){
    if (this.ids.length) {
        this.selectAllCheckbox = false;
        this.ids.splice(0, this.perPage);
    }
}

export function countDown() {
    if(this.count > 0) {
        setTimeout(() => {
            this.count -= 1;
            this.countDown();
        }, 1000)
    }
}
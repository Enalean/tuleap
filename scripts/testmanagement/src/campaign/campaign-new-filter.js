export default CurrentPageFilter;

function CurrentPageFilter() {
    return function (list, page, items_per_page) {
        page = page - 1;
        return list.slice(page * items_per_page, page * items_per_page + items_per_page);
    };
}

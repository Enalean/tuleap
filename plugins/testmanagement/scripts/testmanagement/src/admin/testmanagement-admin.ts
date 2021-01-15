import { createListPicker } from "@tuleap/list-picker";

document.addEventListener("DOMContentLoaded", () => {
    const list_pickers = document.getElementsByClassName("list-picker-sb");

    for (const select of list_pickers) {
        if (select !== null && select instanceof HTMLSelectElement) {
            createListPicker(select, {
                is_filterable: true,
            });
        }
    }
});

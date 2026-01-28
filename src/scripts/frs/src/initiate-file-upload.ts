/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

export function initiateFileUpload(): void {
    const tbody = document.getElementById("frs-release-files-table-tbody");
    if (!(tbody instanceof HTMLTableSectionElement)) {
        return;
    }
    const processors: ReadonlyArray<Option> = JSON.parse(tbody.dataset.processors || "[]");
    const filetypes: ReadonlyArray<Option> = JSON.parse(tbody.dataset.filetypes || "[]");
    const choose_label = tbody.dataset.chooseLabel || "Choose...";
    const delete_label = tbody.dataset.deleteLabel || "Delete";

    initiateLocalUpload(tbody, processors, filetypes, choose_label, delete_label);
    initiateStagingArea(tbody, processors, filetypes, choose_label, delete_label);
}

function initiateLocalUpload(
    tbody: HTMLTableSectionElement,
    processors: ReadonlyArray<Option>,
    filetypes: ReadonlyArray<Option>,
    choose_label: string,
    delete_label: string,
): void {
    const input = document.getElementById("frs-release-upload-file-input");
    if (!(input instanceof HTMLInputElement)) {
        return;
    }

    const container = input.closest(".frs-release-upload-file-input-container");
    if (!(input instanceof HTMLElement)) {
        return;
    }

    const handle = (event: Event): void => {
        const input = event.target;
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        input.removeEventListener("input", handle);
        const sibling = document.createElement("input");
        sibling.type = "file";
        sibling.name = input.name;
        sibling.addEventListener("input", handle);
        container?.appendChild(sibling);

        insertFileRow(
            tbody,
            processors,
            filetypes,
            choose_label,
            delete_label,
            "file_processor[]",
            "file_type[]",
            "reference_md5[]",
            "comment[]",
            (file_column: HTMLTableCellElement) => {
                file_column.appendChild(input);
            },
            () => {},
        );
    };

    input.addEventListener("input", handle);
}

interface Option {
    id: number;
    name: string;
}

function initiateStagingArea(
    tbody: HTMLTableSectionElement,
    processors: ReadonlyArray<Option>,
    filetypes: ReadonlyArray<Option>,
    choose_label: string,
    delete_label: string,
): void {
    const select = document.getElementById("frs-release-staging-area-select");
    if (!(select instanceof HTMLSelectElement)) {
        return;
    }

    select.addEventListener("change", () => {
        const filename = select.value;

        insertFileRow(
            tbody,
            processors,
            filetypes,
            choose_label,
            delete_label,
            "ftp_file_processor[]",
            "ftp_file_type[]",
            "ftp_reference_md5[]",
            "ftp_comment[]",
            (file_column: HTMLTableCellElement) => {
                file_column.textContent = filename;
                const hidden = document.createElement("input");
                hidden.type = "hidden";
                hidden.name = "ftp_file[]";
                hidden.value = filename;
                file_column.appendChild(hidden);
            },
            () => {
                const option = document.createElement("option");
                option.value = filename;
                option.textContent = filename;
                select.add(option);
                sortSelectbox(select);
                select.options[0].selected = true;
            },
        );

        select.options[select.selectedIndex].remove();
        select.options[0].selected = true;
    });
}

function insertFileRow(
    tbody: HTMLTableSectionElement,
    processors: ReadonlyArray<Option>,
    filetypes: ReadonlyArray<Option>,
    choose_label: string,
    delete_label: string,
    processor_name: string,
    filetype_name: string,
    md5_name: string,
    comment_name: string,
    insert_content_in_filename_column_callback: (file_column: HTMLTableCellElement) => void,
    after_delete_callback: () => void,
): void {
    const row = tbody.insertRow();
    row.classList.add("frs-release-file-row");

    const delete_column = row.insertCell();
    const delete_button = document.createElement("button");
    delete_button.type = "button";
    delete_button.title = delete_label;
    delete_button.classList.add(
        "tlp-button-primary",
        "tlp-button-outline",
        "tlp-button-small",
        "frs-release-file-delete-button",
    );
    delete_button.addEventListener("click", () => {
        row.remove();
        after_delete_callback();
    });
    const icon = document.createElement("i");
    icon.role = "img";
    icon.classList.add("fa-solid", "fa-trash");
    icon.ariaHidden = "true";
    delete_button.appendChild(icon);
    delete_column.appendChild(delete_button);

    const file_column = row.insertCell();
    file_column.classList.add("frs-release-file-name-column");
    insert_content_in_filename_column_callback(file_column);

    const processor_column = row.insertCell();
    insertSelectbox(processor_column, processors, processor_name, choose_label);

    const filetype_column = row.insertCell();
    insertSelectbox(filetype_column, filetypes, filetype_name, choose_label);

    const md5_column = row.insertCell();
    const md5 = document.createElement("input");
    md5.type = "text";
    md5.name = md5_name;
    md5.size = 32;
    md5.setAttribute("data-test", "add-md5-file-input");
    md5.classList.add("tlp-input", "tlp-input-small");
    md5_column.appendChild(md5);

    const comment_column = row.insertCell();
    const textarea = document.createElement("textarea");
    textarea.name = comment_name;
    textarea.rows = 1;
    textarea.cols = 20;
    textarea.classList.add("tlp-textarea", "tlp-textarea-small");
    comment_column.appendChild(textarea);

    if (tbody.closest(".frs-release-files-table-with-extra-columns") !== null) {
        const empty_columns = ["owner", "release", "release-date"];
        empty_columns.forEach(() => row.insertCell());
    }
}

function insertSelectbox(
    container: HTMLElement,
    options: ReadonlyArray<Option>,
    name: string,
    choose_label: string,
): void {
    const filetypes_select = document.createElement("select");
    filetypes_select.name = name;
    filetypes_select.classList.add("tlp-select", "tlp-select-small", "tlp-select-adjusted");

    const option_element = document.createElement("option");
    option_element.value = "";
    option_element.disabled = true;
    option_element.selected = true;
    option_element.textContent = choose_label;
    filetypes_select.appendChild(option_element);

    options.forEach((option) => {
        const option_element = document.createElement("option");
        option_element.value = String(option.id);
        option_element.textContent = option.name;
        filetypes_select.appendChild(option_element);
    });

    container.appendChild(filetypes_select);
}

function sortSelectbox(select: HTMLSelectElement): void {
    const options = [...select.options].sort((a, b) => a.value.localeCompare(b.value));
    while (select.options.length > 0) {
        select.remove(0);
    }
    options.forEach((option) => {
        select.add(option);
    });
}

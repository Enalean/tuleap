/*
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import { post } from "@tuleap/tlp-fetch";
import mustache from "mustache";
import { getPOFileFromLocale, initGettext } from "@tuleap/gettext";
import import_preview_template from "./project-admin-member-import-preview.mustache";
import import_spinner from "./members-import-spinner.mustache";
import type { GetText } from "@tuleap/gettext";

let gettext_provider: GetText | null;

interface ResultImport {
    warning_multiple_users: string[];
    warning_invalid_users: string[];
    users: string[];
}

export async function initImportMembersPreview(): Promise<void> {
    await initGettextProvider();
    initUploadButton();
}

async function initGettextProvider(): Promise<void> {
    const import_button = document.getElementById(
        "project-admin-members-modal-import-users-button",
    );
    if (!import_button) {
        return;
    }
    const locale = import_button.dataset.targetUserLocale;

    if (!locale) {
        throw new Error("No user locale");
    }
    gettext_provider = await initGettext(
        locale,
        "project-admin",
        (locale) =>
            import(
                /* webpackChunkName: "project-admin-po-" */ "../../po/" +
                    getPOFileFromLocale(locale)
            ),
    );
}

function initUploadButton(): void {
    const upload_button = document.getElementById("project-admin-members-import-users-file");

    if (!upload_button) {
        return;
    }

    upload_button.addEventListener("change", async () => {
        await uploadFile();
    });
}

function toggleImportButton(): void {
    const import_button = document.getElementById("project-admin-import-members-load-validation");
    if (!import_button) {
        throw new Error("No button to import members");
    }

    if (!isFileValid()) {
        disableImportButton();

        return;
    }

    import_button.removeAttribute("disabled");
    import_button.removeAttribute("title");
}

function disableImportButton(): void {
    const import_button = document.getElementById("project-admin-import-members-load-validation");
    if (!import_button) {
        throw new Error("No button to import members");
    }
    import_button.setAttribute("disabled", "");
    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }
    import_button.setAttribute("title", gettext_provider.gettext("Please select a file to upload"));
}

function showFileFormatError(): void {
    const file_input_label = document.getElementById(
        "project-admin-members-upload-file-form-element",
    );
    if (!file_input_label) {
        throw new Error("No file input label");
    }
    const error_display = document.getElementById("file-input-bad-type");
    if (!error_display) {
        throw new Error("No error displayer");
    }
    file_input_label.classList.add("tlp-form-element-error");
    file_input_label.classList.add("file-input-error");
    error_display.removeAttribute("hidden");
}

function hideFileFormatError(): void {
    const file_input_label = document.getElementById(
        "project-admin-members-upload-file-form-element",
    );
    if (!file_input_label) {
        throw new Error("No file input label");
    }
    const error_display = document.getElementById("file-input-bad-type");
    if (!error_display) {
        throw new Error("No error displayer");
    }

    file_input_label.classList.remove("tlp-form-element-error");
    file_input_label.classList.remove("file-input-error");
    error_display.setAttribute("hidden", "");
}

function isFileValid(): boolean {
    const file_element = document.getElementById("project-admin-members-import-users-file");
    if (!(file_element instanceof HTMLInputElement)) {
        throw new Error("No users file");
    }
    if (!file_element.files || !file_element.files[0]) {
        throw new Error("No users file");
    }
    const file = file_element.files[0];
    return file && file.type === "text/plain";
}

async function uploadFile(): Promise<void> {
    const preview_section = document.getElementById("modal-import-users-preview");
    if (!preview_section) {
        throw new Error("No modal preview to import user ");
    }

    removeAllChildren(preview_section);

    if (!isFileValid()) {
        showFileFormatError();
        disableImportButton();

        return;
    }

    startSpinner(preview_section);

    hideFileFormatError();

    const form = document.getElementById("project-admin-user-import-form");
    if (!(form instanceof HTMLFormElement)) {
        throw new Error("No form to import user");
    }
    const form_data = new FormData(form);
    const response = await post("/project/admin/userimport.php", {
        body: form_data,
    });

    const json = await response.json();

    renderImportPreview(json);
}

function renderImportPreview(import_result: ResultImport): void {
    const preview_section = document.getElementById("modal-import-users-preview");
    if (!preview_section) {
        throw new Error("No modal preview to import user ");
    }
    const import_warnings = [
        ...import_result.warning_multiple_users,
        ...import_result.warning_invalid_users,
    ];

    removeAllChildren(preview_section);

    if (import_result.users.length > 0) {
        toggleImportButton();
    } else {
        disableImportButton();
    }

    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }

    preview_section.insertAdjacentHTML(
        "beforeend",
        mustache.render(import_preview_template, {
            import_warnings,
            parsed_users: import_result.users,
            empty_preview: gettext_provider.gettext("There isn't any new user to import"),
            table_header_name: gettext_provider.gettext("Name"),
            table_header_email: gettext_provider.gettext("Email"),
        }),
    );
}

function removeAllChildren(element: HTMLElement): void {
    [...element.children].forEach((child) => child.remove());
}

function startSpinner(element: HTMLElement): void {
    if (!gettext_provider) {
        throw new Error("No gettext provider");
    }
    element.insertAdjacentHTML(
        "afterbegin",
        mustache.render(import_spinner, {
            loading: gettext_provider.gettext("Preview loading..."),
        }),
    );
}

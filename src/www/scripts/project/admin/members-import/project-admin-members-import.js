/*
 * Copyright Enalean (c) 2017. All rights reserved.
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

import { post } from "tlp";
import { render } from "mustache";
import Gettext from "node-gettext";
import french_translations from "../../po/fr.po";
import import_preview_template from "./project-admin-member-import-preview.mustache";
import import_spinner from "./members-import-spinner.mustache";

const gettext_provider = new Gettext();

export function initImportMembersPreview() {
    initGettext();
    initUploadButton();
}

function initGettext() {
    const import_button = document.getElementById(
        "project-admin-members-modal-import-users-button"
    );
    if (!import_button) {
        return;
    }
    const locale = import_button.dataset.targetUserLocale;

    gettext_provider.addTranslations("fr_FR", "project-admin", french_translations);
    gettext_provider.setTextDomain("project-admin");
    gettext_provider.setLocale(locale);
}

function initUploadButton() {
    const upload_button = document.getElementById("project-admin-members-import-users-file");

    if (!upload_button) {
        return;
    }

    upload_button.addEventListener("change", () => {
        uploadFile();
    });
}

function toggleImportButton() {
    const import_button = document.getElementById("project-admin-import-members-load-validation");

    if (!isFileValid()) {
        disableImportButton();

        return;
    }

    import_button.removeAttribute("disabled");
    import_button.removeAttribute("title");
}

function disableImportButton() {
    const import_button = document.getElementById("project-admin-import-members-load-validation");

    import_button.setAttribute("disabled", "");
    import_button.setAttribute("title", gettext_provider.gettext("Please select a file to upload"));
}

function showFileFormatError() {
    const file_input_label = document.getElementById(
        "project-admin-members-upload-file-form-element"
    );
    const error_display = document.getElementById("file-input-bad-type");

    file_input_label.classList.add("tlp-form-element-error");
    file_input_label.classList.add("file-input-error");
    error_display.removeAttribute("hidden");
}

function hideFileFormatError() {
    const file_input_label = document.getElementById(
        "project-admin-members-upload-file-form-element"
    );
    const error_display = document.getElementById("file-input-bad-type");

    file_input_label.classList.remove("tlp-form-element-error");
    file_input_label.classList.remove("file-input-error");
    error_display.setAttribute("hidden", "");
}

function isFileValid() {
    const file = document.getElementById("project-admin-members-import-users-file").files[0];

    return file && file.type === "text/plain";
}

async function uploadFile() {
    const preview_section = document.getElementById("modal-import-users-preview");

    removeAllChildren(preview_section);

    if (!isFileValid()) {
        showFileFormatError();
        disableImportButton();

        return;
    }

    startSpinner(preview_section);

    hideFileFormatError();

    const form = document.getElementById("project-admin-user-import-form");
    const form_data = new FormData(form);
    const response = await post("/project/admin/userimport.php", {
        body: form_data,
    });

    const json = await response.json();

    renderImportPreview(json);
}

function renderImportPreview(import_result) {
    const preview_section = document.getElementById("modal-import-users-preview");
    const import_warnings = [
        ...import_result.warning_multiple_users,
        ...import_result.warning_inavlid_users,
    ];

    removeAllChildren(preview_section);

    if (import_result.users.length > 0) {
        toggleImportButton();
    } else {
        disableImportButton();
    }

    preview_section.insertAdjacentHTML(
        "beforeEnd",
        render(import_preview_template, {
            import_warnings,
            parsed_users: import_result.users,
            empty_preview: gettext_provider.gettext("There isn't any new user to import"),
            table_header_name: gettext_provider.gettext("Name"),
            table_header_email: gettext_provider.gettext("Email"),
        })
    );
}

function removeAllChildren(element) {
    [...element.children].forEach((child) => child.remove());
}

function startSpinner(element) {
    element.insertAdjacentHTML(
        "afterbegin",
        render(import_spinner, {
            loading: gettext_provider.gettext("Preview loading..."),
        })
    );
}

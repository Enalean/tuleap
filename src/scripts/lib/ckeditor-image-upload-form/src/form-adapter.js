/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import "css.escape"; // Polyfill for css.escape
import {
    increaseCurrentlyUploadingFilesNumber,
    decreaseCurrentlyUploadingFilesNumber,
    isThereAFileCurrentlyUploading,
} from "./forms-being-uploaded-state.js";

export function findAllHiddenInputByNames(form, field_names) {
    const selector = field_names
        .map((field_name) => "input[type=hidden][name=" + CSS.escape(field_name) + "]")
        .join(",");

    return form.querySelectorAll(selector);
}

function preventFormSubmissionListener(event) {
    event.preventDefault();
    event.stopPropagation();
}

function findAllSubmitButtons(form) {
    return form.querySelectorAll(".hidden-artifact-submit-button button");
}

export function disableFormSubmit(form) {
    increaseCurrentlyUploadingFilesNumber();
    const submit_buttons = findAllSubmitButtons(form);
    for (const button of submit_buttons) {
        button.disabled = true;
    }
    form.addEventListener("submit", preventFormSubmissionListener);
}

export function enableFormSubmit(form) {
    decreaseCurrentlyUploadingFilesNumber();
    if (isThereAFileCurrentlyUploading()) {
        return;
    }
    const submit_buttons = findAllSubmitButtons(form);
    for (const button of submit_buttons) {
        button.disabled = false;
    }
    form.removeEventListener("submit", preventFormSubmissionListener);
}

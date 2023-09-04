/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { RichTextEditorFactory } from "@tuleap/plugin-tracker-rich-text-editor";
import { RichTextEditorsCreator } from "./rich-text-editor-creator/RichTextEditorsCreator";
import { reopenFieldsetsWithInvalidInput } from "./edition/reopen-fieldsets-with-invalid-input";

document.addEventListener("DOMContentLoaded", () => {
    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        return;
    }
    const creator = new RichTextEditorsCreator(
        document,
        new UploadImageFormFactory(document, locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale),
    );
    creator.createTextFieldEditors();

    const submit_buttons = document.querySelectorAll(
        `.artifact-form input[name="submit_and_continue"],
        .artifact-form input[name="submit_and_stay"],
        .artifact-form button[type=submit][data-test="artifact-submit-button"]`,
    );
    for (const button of submit_buttons) {
        if (!(button instanceof HTMLButtonElement) && !(button instanceof HTMLInputElement)) {
            continue;
        }

        const form = button.form;
        if (!form) {
            continue;
        }

        button.addEventListener("click", () => {
            reopenFieldsetsWithInvalidInput(form);
        });
    }
});

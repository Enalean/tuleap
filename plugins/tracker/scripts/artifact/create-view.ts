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
import { escaper } from "@tuleap/html-escaper";
import { RichTextEditorsCreator } from "./rich-text-editor-creator/RichTextEditorsCreator";

document.addEventListener("DOMContentLoaded", () => {
    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        return;
    }
    const creator = new RichTextEditorsCreator(
        document,
        new UploadImageFormFactory(document, locale),
        RichTextEditorFactory.forFlamingParrotWithFormatSelector(document, locale)
    );
    creator.createTextFieldEditors({
        onEditorInit: (editor, textarea) => {
            /*
                CKEDITOR filters HTML tags
                So, if your default text is like <blabla>, this will not be displayed.
                To "fix" this, we escape the textarea content.
                However, we don't need to escape this for non default values.
            */
            if (textarea.dataset.fieldDefaultValue) {
                editor.once("instanceReady", () => {
                    const escaped_value = escaper.html(textarea.value);
                    editor.setData(escaped_value);
                });
            }
        },
    });
});

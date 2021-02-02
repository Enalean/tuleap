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

import { TextFieldFormat } from "../constants/fields-constants";
import { getUploadImageOptions, UploadImageFormFactory } from "@tuleap/ckeditor-image-upload-form";
import {
    RichTextEditorFactory,
    RichTextEditorOptions,
} from "@tuleap/plugin-tracker-rich-text-editor";

const NEW_FOLLOWUP_TEXTAREA_ID = "tracker_followup_comment_new";
const NEW_FOLLOWUP_ID_PREFIX = "new";

const locale = getUserLocale();
const image_upload_factory = new UploadImageFormFactory(document, locale);
const editor_factory = new RichTextEditorFactory(document, locale);
const new_followup_textarea = document.getElementById(NEW_FOLLOWUP_TEXTAREA_ID);
if (!(new_followup_textarea instanceof HTMLTextAreaElement)) {
    throw new Error(`Could not find textarea by id #${NEW_FOLLOWUP_TEXTAREA_ID}`);
}

const help_block = image_upload_factory.createHelpBlock(new_followup_textarea);
const options: RichTextEditorOptions = {
    format_selectbox_id: NEW_FOLLOWUP_ID_PREFIX,
    getAdditionalOptions: (textarea: HTMLTextAreaElement) => getUploadImageOptions(textarea),
    onFormatChange: (new_format: TextFieldFormat) => {
        help_block.onFormatChange(new_format);
    },
    onEditorInit: (ckeditor: CKEDITOR.editor, textarea: HTMLTextAreaElement) =>
        image_upload_factory.initiateImageUpload(ckeditor, textarea),
};
editor_factory.createRichTextEditor(new_followup_textarea, options);

// Edition-switcher must execute after CKEditors are initialized, otherwise the
// "submission bar" won't work properly.
import "./TrackerArtifactEditionSwitcher.js";
import "./text-follow-up";
import "./tracker-email-copy-paste-fp";

function getUserLocale(): string {
    const locale = document.body.dataset.userLocale;
    if (locale === undefined) {
        throw new Error("Could not determine user locale from document body");
    }
    return locale;
}

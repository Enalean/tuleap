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

import type { UploadImageFormFactory } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import { getUploadImageOptions } from "@tuleap/plugin-tracker-artifact-ckeditor-image-upload";
import type {
    RichTextEditorFactory,
    RichTextEditorOptions,
    TextEditorInterface,
} from "@tuleap/plugin-tracker-rich-text-editor";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import { isValidTextFormat, TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";
import { initMentionsOnEditorDataReady } from "./init-mentions";
import { initMentions } from "@tuleap/mention";

const NEW_COMMENT_TEXTAREA_ID = "tracker_followup_comment_new";
const NEW_COMMENT_ID_SUFFIX = "new";
const TEXT_FIELDS_SELECTOR = ".tracker_artifact_field textarea";

export interface RichTextEditorsCreator {
    createNewCommentEditor(): void;
    createEditCommentEditor(
        textarea: HTMLTextAreaElement,
        changeset_id: string,
        format: TextFieldFormat,
    ): TextEditorInterface;
    /**
     * @throws
     */
    createTextFieldEditors(): void;
}

export const RichTextEditorsCreator = (
    doc: Document,
    image_upload_factory: UploadImageFormFactory,
    editor_factory: RichTextEditorFactory,
): RichTextEditorsCreator => {
    function createNewCommentEditor(): void {
        const new_comment_textarea = doc.getElementById(NEW_COMMENT_TEXTAREA_ID);
        if (!(new_comment_textarea instanceof HTMLTextAreaElement)) {
            // When copying artifacts or browsing as anonymous, there is no "new follow-up" textarea
            return;
        }
        initMentions(new_comment_textarea);
        const help_block = image_upload_factory.createHelpBlock(new_comment_textarea);
        const options: RichTextEditorOptions = {
            format_selectbox_id: NEW_COMMENT_ID_SUFFIX,
            getAdditionalOptions: getUploadImageOptions,
            onFormatChange: (new_format) => help_block?.onFormatChange(new_format),
            onEditorInit: (ckeditor, textarea) =>
                image_upload_factory.initiateImageUpload(ckeditor, textarea),
            onEditorDataReady: initMentionsOnEditorDataReady,
        };
        editor_factory.createRichTextEditor(new_comment_textarea, options);
    }

    function createEditCommentEditor(
        textarea: HTMLTextAreaElement,
        changeset_id: string,
        format: TextFieldFormat,
    ): TextEditorInterface {
        initMentions(textarea);
        const options: RichTextEditorOptions = {
            format_selectbox_id: changeset_id,
            format_selectbox_value: format,
            onEditorInit: (ckeditor) => image_upload_factory.forbidImageUpload(ckeditor),
            onEditorDataReady: initMentionsOnEditorDataReady,
        };
        return editor_factory.createRichTextEditor(textarea, options);
    }

    function createTextFieldEditors(): void {
        const text_field_textareas = doc.querySelectorAll(TEXT_FIELDS_SELECTOR);
        const observer = new IntersectionObserver(
            (entries: IntersectionObserverEntry[], observer: IntersectionObserver) => {
                for (const entry of entries) {
                    if (!entry.isIntersecting) {
                        return;
                    }
                    observer.unobserve(entry.target);
                    createTextFieldEditor(entry.target);
                }
            },
        );
        for (const text_field_textarea of text_field_textareas) {
            observer.observe(text_field_textarea);
        }
    }

    function createTextFieldEditor(text_field_textarea: Element): void {
        if (!(text_field_textarea instanceof HTMLTextAreaElement)) {
            return;
        }

        const match = text_field_textarea.id.match(/_(\d+)$/);
        if (!match) {
            throw new Error(
                `Text field textarea's id must finish by an underscore and the field ID. Got ${text_field_textarea.id} instead`,
            );
        }
        const field_id = match[1];
        const format_name = `artifact[${field_id}][format]`;
        const format_value = getTextFieldFormatOrDefault(field_id);

        const help_block = image_upload_factory.createHelpBlock(text_field_textarea);
        const options: RichTextEditorOptions = {
            format_selectbox_id: text_field_textarea.id,
            format_selectbox_name: format_name,
            format_selectbox_value: format_value,
            getAdditionalOptions: getUploadImageOptions,
            onFormatChange: (new_format) => help_block?.onFormatChange(new_format),
            onEditorInit: (ckeditor, textarea) => {
                image_upload_factory.initiateImageUpload(ckeditor, textarea);
            },
        };
        editor_factory.createRichTextEditor(text_field_textarea, options);
    }

    function getTextFieldFormatOrDefault(field_id: string): TextFieldFormat {
        const format_hidden_input = doc.getElementById(`artifact[${field_id}]_body_format`);
        if (format_hidden_input instanceof HTMLInputElement) {
            const format = format_hidden_input.value;
            if (isValidTextFormat(format)) {
                return format;
            }
        }
        return TEXT_FORMAT_COMMONMARK;
    }

    return {
        createNewCommentEditor,
        createEditCommentEditor,
        createTextFieldEditors,
    };
};

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

import { TEXT_FORMAT_HTML, TEXT_FORMAT_COMMONMARK } from "@tuleap/plugin-tracker-constants";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";
import type { TextFieldValueModel } from "../text-field/text-field-value-formatter";
import type { FileFieldValueModel } from "../../../../domain/fields/file-field/FileFieldValueModel";
import type { UploadedImage } from "../../../../domain/fields/file-field/UploadedImage";

export interface FileValueModel {
    readonly field_id: number;
    readonly value: ReadonlyArray<number>;
    readonly images_added_by_text_fields: ReadonlyArray<UploadedImage>;
}

export interface FollowupValueModel {
    readonly body: string;
    readonly format: TextFieldFormat;
}

type ValidatedFileFieldValue = Pick<FileFieldValueModel, "field_id" | "value">;

export function validateFileField(
    file_value_model: FileValueModel | undefined,
    text_field_value_models: ReadonlyArray<TextFieldValueModel>,
    followup_value_model: FollowupValueModel,
): ValidatedFileFieldValue | null {
    if (file_value_model === undefined) {
        return null;
    }

    const { field_id, value } = file_value_model;
    if (value.length === 0) {
        return { field_id, value: [] };
    }

    const filtered_value = value.filter((file_id) => {
        const file_added_by_text_field = findFileThatWasAddedByATextField(
            file_value_model.images_added_by_text_fields,
            file_id,
        );

        if (file_added_by_text_field === null) {
            return true;
        }

        const { body, format } = followup_value_model;
        return (
            isFileReferencedByAnyTextField(file_added_by_text_field, text_field_value_models) ||
            isFileReferencedByAnEditor(file_added_by_text_field, body, format)
        );
    });

    return { field_id, value: filtered_value };
}

function findFileThatWasAddedByATextField(
    images_added_by_text_fields: ReadonlyArray<UploadedImage>,
    file_id: number,
): UploadedImage | null {
    return images_added_by_text_fields.find(({ id }) => id === file_id) ?? null;
}

function isFileReferencedByAnyTextField(
    file: UploadedImage,
    text_field_value_models: ReadonlyArray<TextFieldValueModel>,
): boolean {
    return text_field_value_models.some(({ value }) =>
        isFileReferencedByAnEditor(file, value.content, value.format),
    );
}

function isFileReferencedByAnEditor(
    file: UploadedImage,
    text_content: string,
    text_format: TextFieldFormat,
): boolean {
    if (!text_content) {
        return false;
    }

    return (
        isTextFieldInHTMLOrMarkdownFormat(text_format) && text_content.includes(file.download_href)
    );
}

function isTextFieldInHTMLOrMarkdownFormat(text_format: TextFieldFormat): boolean {
    return text_format === TEXT_FORMAT_HTML || text_format === TEXT_FORMAT_COMMONMARK;
}

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

import { findImageUrls } from "../../../../../../../src/www/scripts/tuleap/ckeditor/image-urls-finder.js";
import { TEXT_FORMAT_HTML } from "../../../../constants/fields-constants.js";

export function validateFileField(file_value_model, text_field_value_models, followup_value_model) {
    if (typeof file_value_model === "undefined") {
        return null;
    }

    const { field_id, value } = file_value_model;
    if (value.length === 0) {
        return { field_id, value: [] };
    }

    const filtered_value = value.filter((file_id) => {
        const file_added_by_text_field = findFileThatWasAddedByATextField(
            file_value_model.images_added_by_text_fields,
            file_id
        );

        if (typeof file_added_by_text_field === "undefined") {
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

function findFileThatWasAddedByATextField(images_added_by_text_fields, file_id) {
    return images_added_by_text_fields.find(({ id }) => id === file_id);
}

function isFileReferencedByAnyTextField(file, text_field_value_models) {
    return text_field_value_models.some(({ value }) =>
        isFileReferencedByAnEditor(file, value.content, value.format)
    );
}

function isFileReferencedByAnEditor(file, text_content, text_format) {
    return (
        isTextFieldInHTMLFormat(text_format) &&
        findImageUrls(text_content).includes(file.download_href)
    );
}

function isTextFieldInHTMLFormat(text_format) {
    return text_format === TEXT_FORMAT_HTML;
}

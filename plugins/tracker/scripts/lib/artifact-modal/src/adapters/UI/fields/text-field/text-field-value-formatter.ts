/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
 *
 */

import {
    TEXT_FORMAT_COMMONMARK,
    TEXT_FORMAT_HTML,
    TEXT_FORMAT_TEXT,
} from "@tuleap/plugin-tracker-constants";
import type { TextFieldFormat } from "@tuleap/plugin-tracker-constants";

interface TextFieldArtifactBasicValue {
    readonly value: string;
    readonly format: TextFieldFormat;
}

interface TextFieldArtifactValueWithCommonMark extends TextFieldArtifactBasicValue {
    readonly commonmark: string;
}

export type TextFieldArtifactValue = TextFieldArtifactBasicValue &
    TextFieldArtifactValueWithCommonMark;

export interface TextFieldValue {
    readonly content: string;
    readonly format: TextFieldFormat;
}

export interface TextFieldValueModel {
    readonly field_id: number;
    readonly value: TextFieldValue;
}

export function formatExistingValue(artifact_value: TextFieldArtifactValue): TextFieldValue {
    switch (artifact_value.format) {
        case TEXT_FORMAT_TEXT:
            return {
                content: artifact_value.value,
                format: artifact_value.format,
            };
        case TEXT_FORMAT_HTML:
            if (artifact_value.commonmark === undefined) {
                return {
                    content: artifact_value.value,
                    format: artifact_value.format,
                };
            }
            return {
                content: artifact_value.commonmark,
                format: TEXT_FORMAT_COMMONMARK,
            };
        default:
            throw new Error(
                `Unknown text field format was given: ${artifact_value.format}, supported values are "text", "html"`,
            );
    }
}

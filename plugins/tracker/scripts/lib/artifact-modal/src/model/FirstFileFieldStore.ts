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

import { getFirstFileField as detectFirstFileField } from "../fields/file-field/file-field-detector";
import type { FileFieldValueModel } from "../domain/fields/file-field/FileFieldValueModel";
import type { Field } from "../domain/fields/Field";

export type FirstFileField = Pick<
    FileFieldValueModel,
    "field_id" | "file_creation_uri" | "max_size_upload"
>;

let first_file_field: FirstFileField | null = null;

export const setTrackerFields = (fields: readonly Field[]): void => {
    first_file_field = detectFirstFileField(fields);
};

export const getFirstFileField = (): FirstFileField | null => first_file_field;

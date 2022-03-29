/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { FILE_FIELD } from "@tuleap/plugin-tracker-constants";
import { isDisabled } from "../disabled-field-detector";
import type { Field, FileField } from "../../types";

const isFileField = (field: Field): field is FileField => field.type === FILE_FIELD;
const isEnabledFileField = (field: Field): field is FileField =>
    isFileField(field) && !isDisabled(field);

export const getAllFileFields = (tracker_fields: Field[]): FileField[] =>
    tracker_fields.filter(isEnabledFileField);

export const isThereAtLeastOneFileField = (tracker_fields: Field[]): boolean =>
    tracker_fields.some(isEnabledFileField);

export function getFirstFileField(tracker_fields: Field[]): FileField | null {
    const result = tracker_fields.find(isEnabledFileField);
    return result !== undefined ? result : null;
}

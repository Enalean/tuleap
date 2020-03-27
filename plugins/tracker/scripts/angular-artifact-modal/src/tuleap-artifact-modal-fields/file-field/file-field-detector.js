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

import { FILE_FIELD } from "../../../../constants/fields-constants.js";
import { isDisabled } from "../disabled-field-detector.js";

const isFileField = (field) => field.type === FILE_FIELD;
const isEnabledFileField = (field) => isFileField(field) && !isDisabled(field);

export function getAllFileFields(tracker_fields) {
    return tracker_fields.filter(isEnabledFileField);
}

export function isThereAtLeastOneFileField(tracker_fields) {
    return tracker_fields.some(isEnabledFileField);
}

export function getFirstFileField(tracker_fields) {
    const result = tracker_fields.find(isEnabledFileField);
    return result !== undefined ? result : null;
}

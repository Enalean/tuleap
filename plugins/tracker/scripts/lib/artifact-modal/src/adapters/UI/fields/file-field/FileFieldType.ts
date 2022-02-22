/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import type { FileFieldIdentifier } from "../../../../../../../constants/fields-constants";
import type { AttachedFileDescription } from "./AttachedFileDescription";

/**
 * I hold a combination of the File field's Tracker representation (field_id, label, required, type)
 * and its value's representation in the last changeset of the current Artifact.
 * Both come from Tuleap API and are combined by the modal into one object.
 */
export interface FileFieldType {
    readonly field_id: number;
    readonly type: FileFieldIdentifier;
    readonly label: string;
    readonly required: boolean;
    readonly file_descriptions: AttachedFileDescription[] | undefined;
}

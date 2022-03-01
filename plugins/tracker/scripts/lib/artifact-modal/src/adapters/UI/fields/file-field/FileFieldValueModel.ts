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

import type { NewFileToAttach } from "./NewFileToAttach";
import type { FileFieldType } from "./FileFieldType";

/**
 * I hold an extension of the File field's representation, where the modal adds
 * new files to attach and maintains the new value ("value" property) of the
 * field.
 */
export interface FileFieldValueModel extends Omit<FileFieldType, "required"> {
    temporary_files: ReadonlyArray<NewFileToAttach>;
    value: ReadonlyArray<number>;
}

/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import type { StrictInjectionKey } from "@tuleap/vue-strict-inject";
import type { StructureFields } from "@tuleap/plugin-tracker-rest-api-types";

export const PROJECT_ID: StrictInjectionKey<number> = Symbol("PROJECT_ID");

export type Child = Field | Fieldset | ColumnWrapper;

export interface ElementWithChildren {
    readonly children: Array<Child>;
}

export type ContainerId = number | "root";
export const ROOT_CONTAINER_ID: ContainerId = "root";

export interface ColumnWrapper {
    readonly identifier: string;
    readonly columns: [Column, ...Column[]];
}

export interface Field {
    readonly field: StructureFields;
}

export interface Fieldset extends ElementWithChildren {
    readonly field: StructureFields;
}

export interface Column extends ElementWithChildren {
    readonly field: StructureFields;
}

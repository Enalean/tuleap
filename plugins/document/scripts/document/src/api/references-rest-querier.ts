/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
import type { ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";

export interface TitleBadge {
    readonly label: string;
    readonly color: string;
    readonly icon: string;
}

export interface CrossReference {
    readonly id: number;
    readonly title: string;
    readonly url: string;
    readonly type: string;
    readonly target_value: string;
    readonly title_badge: TitleBadge | null;
}

export interface CrossReferenceSection {
    readonly label: string;
    readonly cross_references: ReadonlyArray<CrossReference>;
}

export interface CrossReferenceNature {
    readonly label: string;
    readonly icon: string;
    readonly sections: ReadonlyArray<CrossReferenceSection>;
}

export interface CrossReferenceByDirection {
    readonly sources_by_nature: ReadonlyArray<CrossReferenceNature>;
    readonly targets_by_nature: ReadonlyArray<CrossReferenceNature>;
    readonly has_source: boolean;
    readonly has_target: boolean;
}

export function getItemReferences(
    item_id: number,
    project_id: number,
): ResultAsync<CrossReferenceByDirection, Fault> {
    return getJSON<CrossReferenceByDirection>(
        uri`/project/${project_id}/cross-references/${item_id}?type=document`,
    );
}

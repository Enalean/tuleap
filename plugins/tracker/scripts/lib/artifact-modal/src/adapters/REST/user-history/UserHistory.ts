/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { ArtifactProject } from "../../../domain/ArtifactProject";
import type { TrackerColorName } from "@tuleap/plugin-tracker-constants";

export type ItemBadge = {
    readonly color: string | null;
    readonly label: string;
};

type EntryType = "artifact" | "kanban";

export const HISTORY_ENTRY_ARTIFACT = "artifact";

export type UserHistoryEntry = {
    readonly xref: string | null;
    readonly html_url: string;
    readonly title: string | null;
    readonly color_name: TrackerColorName;
    readonly project: ArtifactProject;
    readonly badges: ReadonlyArray<ItemBadge>;
    readonly per_type_id: number;
    readonly type: EntryType;
};

export type UserHistory = {
    readonly entries: UserHistoryEntry[];
};

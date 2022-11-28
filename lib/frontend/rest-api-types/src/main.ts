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

export type ProjectReference = {
    readonly id: number;
    readonly label: string;
    readonly icon: string;
};

export type QuickLink = {
    readonly name: string;
    readonly html_url: string;
    readonly icon_name: string;
};

export type Badge = {
    readonly color: string | null;
    readonly label: string;
};

type EntryType = "artifact" | "kanban";

export const ARTIFACT_TYPE = "artifact";

export type UserHistoryEntry = {
    readonly xref: string | null;
    readonly html_url: string;
    readonly title: string;
    readonly color_name: string;
    readonly type: EntryType;
    readonly per_type_id: number;
    readonly icon_name: string;
    readonly project: ProjectReference;
    readonly quick_links: ReadonlyArray<QuickLink>;
    readonly badges: ReadonlyArray<Badge>;
};

export type SearchResultEntry = UserHistoryEntry & {
    readonly cropped_content: string | null;
};

export type UserHistoryResponse = {
    readonly entries: ReadonlyArray<UserHistoryEntry>;
};

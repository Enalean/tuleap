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

import type { ColorName, UserHistoryEntryType } from "@tuleap/core-constants";

export type ProjectReference = {
    readonly id: number;
    readonly label: string;
    readonly icon: string;
    readonly uri: string;
};

/**
 * ⚠️ The label already contains the icon of the project as a prefix:
 * `label = icon + " " + label`
 */
export type ProjectResponse = {
    readonly id: number;
    readonly label: string;
    readonly label_without_icon: string;
    readonly shortname: string;
    readonly uri: string;
};

export type ProjectArchiveReference = {
    readonly id: number;
    readonly upload_href: string;
    readonly uri: string;
};

export type QuickLink = {
    readonly name: string;
    readonly html_url: string;
    readonly icon_name: string;
};

export type Badge = {
    readonly color: ColorName | null;
    readonly label: string;
};

export interface ProjectLabel {
    readonly id: number;
    readonly label: string;
    readonly is_outline: boolean;
    readonly color: ColorName;
}

export interface ProjectLabelsCollection {
    readonly labels: ReadonlyArray<ProjectLabel>;
}

export type UserHistoryEntry = {
    readonly xref: string | null;
    readonly html_url: string;
    readonly title: string;
    readonly color_name: ColorName;
    readonly type: UserHistoryEntryType;
    readonly per_type_id: number;
    readonly icon_name: string;
    readonly project: ProjectResponse;
    readonly quick_links: ReadonlyArray<QuickLink>;
    readonly badges: ReadonlyArray<Badge>;
};

export type User = {
    readonly id: number;
    readonly avatar_url: string;
    readonly user_url: string;
    readonly display_name: string;
};

export type SearchResultEntry = UserHistoryEntry & {
    readonly cropped_content: string | null;
};

export type UserHistoryResponse = {
    readonly entries: ReadonlyArray<UserHistoryEntry>;
};

export type FeatureFlagResponse = {
    readonly value: string | number;
};

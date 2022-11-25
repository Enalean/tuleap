/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { SearchResultEntry, QuickLink } from "@tuleap/core-rest-api-types";

export type { QuickLink, Badge as ItemBadge } from "@tuleap/core-rest-api-types";

export interface ProjectBaseDefinition {
    readonly project_uri: string;
    readonly project_name: string;
    readonly project_config_uri: string;
    readonly is_private: boolean;
    readonly is_public: boolean;
    readonly is_public_incl_restricted: boolean;
    readonly is_private_incl_restricted: boolean;
    readonly is_current_user_admin: boolean;
    readonly icon: string;
}

export type Project = ProjectBaseDefinition & {
    readonly quick_links: QuickLink[];
};

export interface HiddenField {
    readonly name: string;
    readonly value: string;
}

export interface SearchForm {
    readonly type_of_search: string;
    readonly hidden_fields: HiddenField[];
}

export type ItemDefinition = Pick<
    SearchResultEntry,
    "xref" | "html_url" | "title" | "color_name" | "icon_name" | "quick_links" | "badges"
> & {
    readonly project: { readonly label: string };
    readonly cropped_content?: string | null;
};

export type UserHistory = {
    readonly entries: ItemDefinition[];
};

/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

import type { Fault } from "@tuleap/fault";
import { getJSON, uri } from "@tuleap/fetch-result";
import type { ResultAsync } from "neverthrow";

export interface NewsVisibilityRepresentation {
    readonly news_name: string;
    readonly admin_quicklink: string;
    readonly is_public: boolean;
}

export function getNewsPermissions(
    project_id: number,
    selected_ugroup_id: string,
): ResultAsync<NewsVisibilityRepresentation[], Fault> {
    return getJSON<NewsVisibilityRepresentation[]>(
        uri`/news/permissions-per-group?group_id=${project_id}&selected_ugroup_id=${selected_ugroup_id}`,
    );
}

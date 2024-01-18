/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { LazyboxItem } from "@tuleap/lazybox";
import type { Fault } from "@tuleap/fault";
import { fetchPullRequestsAuthors } from "../../../api/tuleap-rest-querier";
import type { SelectorsDropdownLoadItemsCallback } from "@tuleap/plugin-pullrequest-selectors-dropdown";

export const AuthorsLoader =
    (
        on_error_callback: (fault: Fault) => void,
        repository_id: number,
    ): SelectorsDropdownLoadItemsCallback =>
    () =>
        fetchPullRequestsAuthors(repository_id).match(
            (authors): LazyboxItem[] =>
                authors.map((author) => ({
                    value: author,
                    is_disabled: false,
                })),
            (fault): LazyboxItem[] => {
                on_error_callback(fault);
                return [];
            },
        );

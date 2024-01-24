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

import type { SelectorEntry } from "@tuleap/plugin-pullrequest-selectors-dropdown";
import type { Fault } from "@tuleap/fault";
import { AuthorTemplatingCallback } from "./AuthorTemplatingCallback";
import { AuthorsLoader } from "./AuthorsLoader";

export const AuthorSelectorEntry = (
    $gettext: (string: string) => string,
    on_error_callback: (fault: Fault) => void,
    repository_id: number,
): SelectorEntry => ({
    entry_name: $gettext("Author"),
    config: {
        placeholder: $gettext("Name"),
        group: {
            label: $gettext("Matching users"),
            empty_message: $gettext("No matching user"),
            is_loading: false,
            footer_message: "",
            items: [],
        },
        templating_callback: AuthorTemplatingCallback,
        loadItems: AuthorsLoader(on_error_callback, repository_id),
    },
});

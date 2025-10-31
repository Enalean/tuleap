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

import type { User } from "@tuleap/core-rest-api-types";
import type { LocaleString } from "@tuleap/locale";
import { createLazybox } from "@tuleap/lazybox";
import { initUsersAutocompleter } from "@tuleap/lazybox-users-autocomplete";

export function instantiateUserAutocompleter(locale: LocaleString): void {
    const container = document.getElementById("frs-monitor-user-add");
    if (container === null) {
        return;
    }

    const lazybox = createLazybox(document);
    lazybox.id = "listeners_to_add";
    container.appendChild(lazybox);

    const hidden = document.createElement("input");
    hidden.type = "hidden";
    hidden.name = "listeners_to_add";
    container.appendChild(hidden);

    initUsersAutocompleter(
        lazybox,
        [],
        (selected_users: ReadonlyArray<User>): void => {
            hidden.value = selected_users.map((user: User): number => user.id).join(";");
        },
        locale,
    );
}

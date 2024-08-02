/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { GroupOfItems, HTMLTemplateResult, Lazybox } from "@tuleap/lazybox";
import { createLazybox } from "@tuleap/lazybox";
import type { LazyboxProps } from "./lazybox.stories";

type User = {
    readonly id: number;
    readonly display_name: string;
};

function isUser(item: unknown): item is User {
    return typeof item === "object" && item !== null && "id" in item;
}

const users = [
    {
        value: { id: 102, display_name: "Johnny Cash (jocash)" },
        is_disabled: false,
    },
    {
        value: { id: 102, display_name: "Joe l'Asticot (jolasti)" },
        is_disabled: false,
    },
    {
        value: { id: 103, display_name: "John doe (jdoe)" },
        is_disabled: false,
    },
    {
        value: { id: 104, display_name: "Joe the hobo (johobo)" },
        is_disabled: true,
    },
];

const users_group: GroupOfItems = {
    label: "Matching users",
    empty_message: "No user found",
    is_loading: false,
    items: [],
    footer_message: "",
};

const recent_users = [
    { value: { id: 105, display_name: "Jon Snow (jsnow)" }, is_disabled: false },
    { value: { id: 106, display_name: "Joe Dalton (jdalton)" }, is_disabled: false },
];

const recent_group = {
    label: "Recent users",
    empty_message: "No user found",
    is_loading: false,
    items: [],
    footer_message: "",
};

export function buildLazyboxMultiple(args: LazyboxProps): Lazybox & HTMLElement {
    const lazybox = createLazybox(document);
    lazybox.id = `lazybox-${args.story}-link-selector`;
    lazybox.options = {
        is_multiple: true,
        placeholder: args.placeholder,
        templating_callback: (html, item): HTMLTemplateResult => {
            if (!isUser(item.value)) {
                return html``;
            }
            return html`<span class="doc-multiple-lazybox-user-with-avatar">
                <div class="tlp-avatar-mini"></div>
                ${item.value.display_name}
            </span>`;
        },
        selection_callback: (): void => {
            // Do nothing
        },
        search_input_callback: (query): void => {
            if (query === "") {
                lazybox.replaceDropdownContent([users_group]);
                return;
            }
            const lowercase_query = query.toLowerCase();
            const matching_users = users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query),
            );
            const matching_recent = recent_users.filter((user) =>
                user.value.display_name.toLowerCase().includes(lowercase_query),
            );
            const matching_users_group = { ...users_group, items: matching_users };
            const matching_recent_group = { ...recent_group, items: matching_recent };
            lazybox.replaceDropdownContent([matching_users_group, matching_recent_group]);
        },
    };
    lazybox.replaceDropdownContent([users_group]);
    lazybox.replaceSelection([users[0]]);
    return lazybox;
}

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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { LinkSelector, LinkSelectorSearchFieldCallback } from "../type";

const TRIGGER_CALLBACK_DELAY_IN_MS = 250;

export interface SearchFieldEventCallbackHandlerType {
    init(): void;
}

export const SearchFieldEventCallbackHandler = (
    link_selector: LinkSelector,
    search_field_element: HTMLInputElement,
    callback: LinkSelectorSearchFieldCallback
): SearchFieldEventCallbackHandlerType => ({
    init: (): void => {
        let timeout_id: number | undefined;
        search_field_element.addEventListener("input", () => {
            // setTimeout + clearTimeout is a trick to "debounce":
            // we call the callback only once after the delay and _not_ for each input
            clearTimeout(timeout_id);

            const query = search_field_element.value;
            if (query === "") {
                // The query has been cleared, no need to wait
                callback(link_selector, query);
            }

            timeout_id = window.setTimeout(() => {
                callback(link_selector, query);
            }, TRIGGER_CALLBACK_DELAY_IN_MS);
        });
    },
});

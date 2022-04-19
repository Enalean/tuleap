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

import { html as HTMLTemplateStringProcessor } from "lit/html.js";
import type { DropdownContentRefresher } from "../dropdown/DropdownContentRefresher";
import type { GroupCollection } from "../items/GroupCollection";

const TRIGGER_CALLBACK_DELAY_IN_MS = 250;

export type LinkSelectorSearchFieldCallback = (
    query: string,
    html: typeof HTMLTemplateStringProcessor
) => Promise<GroupCollection>;

export interface SearchFieldEventCallbackHandlerType {
    init(search_field_element: HTMLInputElement, callback: LinkSelectorSearchFieldCallback): void;
}

export const SearchFieldEventCallbackHandler = (
    refresher: DropdownContentRefresher
): SearchFieldEventCallbackHandlerType => ({
    init: (
        search_field_element: HTMLInputElement,
        callback: LinkSelectorSearchFieldCallback
    ): void => {
        let timeout_id: number | undefined;
        search_field_element.addEventListener("input", () => {
            // setTimeout + clearTimeout is a trick to "debounce":
            // we call the callback only once after the delay and _not_ for each input
            clearTimeout(timeout_id);

            timeout_id = setTimeout(async () => {
                const groups = await callback(
                    search_field_element.value,
                    HTMLTemplateStringProcessor
                );
                refresher.refresh(groups);
            }, TRIGGER_CALLBACK_DELAY_IN_MS);
        });
    },
});

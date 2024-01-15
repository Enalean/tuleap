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

import { define } from "hybrids";
import { selectOrThrow } from "@tuleap/dom";
import type { ControlSelectorsDropdown } from "./SelectorsDropdownController";
import { SelectorsDropdownController } from "./SelectorsDropdownController";
import {
    DROPDOWN_BUTTON_CLASSNAME,
    DROPDOWN_CONTENT_CLASSNAME,
    renderContent,
} from "./SelectorsDropdownTemplate";

export const TAG = "tuleap-selectors-dropdown";

export type SelectorEntry = {
    readonly entry_name: string;
};

export type SelectorsDropdown = {
    button_text: string;
    selectors_entries: ReadonlyArray<SelectorEntry>;
};

export type InternalSelectorsDropdown = Readonly<SelectorsDropdown> & {
    dropdown_button_element: Element;
    dropdown_content_element: Element;
    controller: ControlSelectorsDropdown;
};

export type HostElement = InternalSelectorsDropdown & HTMLElement;

export const SelectorsDropdown = define<InternalSelectorsDropdown>({
    tag: TAG,
    button_text: "",
    selectors_entries: undefined,
    dropdown_button_element: {
        get: (host) => selectOrThrow(host, `.${DROPDOWN_BUTTON_CLASSNAME}`),
    },
    dropdown_content_element: {
        get: (host) => selectOrThrow(host, `.${DROPDOWN_CONTENT_CLASSNAME}`),
    },
    controller: {
        get: (host: InternalSelectorsDropdown, controller: ControlSelectorsDropdown | undefined) =>
            controller ?? SelectorsDropdownController(),
        connect: (host) => {
            setTimeout(() => host.controller.initDropdown(host));
        },
    },
    content: renderContent,
});

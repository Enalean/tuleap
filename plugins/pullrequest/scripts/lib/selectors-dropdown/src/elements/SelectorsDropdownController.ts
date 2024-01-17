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

import { createDropdown } from "@tuleap/tlp-dropdown";
import { Option } from "@tuleap/option";
import type { InternalSelectorsDropdown, SelectorEntry } from "./SelectorsDropdown";

export type ControlSelectorsDropdown = {
    initDropdown(host: InternalSelectorsDropdown): void;
    onDropdownShown(host: InternalSelectorsDropdown): void;
    onDropdownHidden(host: InternalSelectorsDropdown): void;
    openSidePanel(host: InternalSelectorsDropdown, selector: SelectorEntry): void;
};

export const SelectorsDropdownController = (): ControlSelectorsDropdown => ({
    initDropdown: (host): void => {
        createDropdown(host.dropdown_button_element, {
            dropdown_menu: host.dropdown_content_element,
        });
    },
    onDropdownShown: (host): void => {
        host.is_dropdown_shown = true;
    },
    onDropdownHidden: (host): void => {
        host.is_dropdown_shown = false;
        host.active_selector = Option.nothing();
    },
    openSidePanel: (host, selector): void => {
        host.active_selector = Option.fromValue(selector);
    },
});

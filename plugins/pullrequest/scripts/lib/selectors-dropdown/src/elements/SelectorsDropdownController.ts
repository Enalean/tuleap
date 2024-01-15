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
import type { InternalSelectorsDropdown } from "./SelectorsDropdown";

export type ControlSelectorsDropdown = {
    initDropdown(host: InternalSelectorsDropdown): void;
};

export const SelectorsDropdownController = (): ControlSelectorsDropdown => ({
    initDropdown: (host): void => {
        createDropdown(host.dropdown_button_element, {
            dropdown_menu: host.dropdown_content_element,
        });
    },
});

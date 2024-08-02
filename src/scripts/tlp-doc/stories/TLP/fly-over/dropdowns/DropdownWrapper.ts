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

import { createDropdown, type Dropdown } from "@tuleap/tlp-dropdown";

class DropdownWrapper extends HTMLElement {
    dropdown_instance: Dropdown | undefined = undefined;
    submenu_1: Dropdown | undefined = undefined;
    submenu_2: Dropdown | undefined = undefined;
    #trigger: "click" | "hover-and-click" = "click";
    #keyboard: boolean = true;

    set trigger(trigger: "click" | "hover-and-click") {
        this.#trigger = trigger;
        this.update();
    }

    set keyboard(keyboard: boolean) {
        this.#keyboard = keyboard;
        this.update();
    }

    update(): void {
        this.dropdown_instance?.destroy();
        const select_element = this.querySelector("#dropdown-example");
        const dropdown_menu = this.querySelector("#dropdown-menu-example");
        if (!select_element || !dropdown_menu) {
            return;
        }
        this.dropdown_instance = createDropdown(select_element, {
            dropdown_menu: dropdown_menu,
            trigger: this.#trigger,
            keyboard: this.#keyboard,
        });
        if (this.hasAttribute("submenu")) {
            const select_element_1 = this.querySelector("#dropdown-submenu-example-1");
            const dropdown_submenu_1 = this.querySelector("#dropdown-menu-example-submenu-1");
            const select_element_2 = this.querySelector("#dropdown-submenu-example-2");
            const dropdown_submenu_2 = this.querySelector("#dropdown-menu-example-submenu-2");
            if (
                !select_element_1 ||
                !dropdown_submenu_1 ||
                !select_element_2 ||
                !dropdown_submenu_2
            ) {
                return;
            }
            this.submenu_1 = createDropdown(select_element_1, {
                dropdown_menu: dropdown_submenu_1,
            });
            this.submenu_2 = createDropdown(select_element_2, {
                dropdown_menu: dropdown_submenu_2,
            });
        }
    }

    connectedCallback(): void {
        this.update();
    }
}

if (!window.customElements.get("tuleap-dropdown-wrapper")) {
    window.customElements.define("tuleap-dropdown-wrapper", DropdownWrapper);
}

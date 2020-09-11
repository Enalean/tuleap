/**
 * Copyright (c) 2020-present, Enalean. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

import { Dropdown, createDropdown } from "../../themes/tlp/src/js/dropdowns";
import { manageUserPreferences } from "../user/user-patch-release-note-preference";
import { patch } from "../../themes/tlp/src/js/fetch-wrapper";

document.addEventListener("DOMContentLoaded", () => {
    // We need CustomEvent to work properly to use the tlp dropdown
    polyfillCustomEventIE11();
    const help_button = document.getElementById("help");
    if (help_button) {
        const help_dropdown: Dropdown = createDropdown(document, help_button);
        help_dropdown.addEventListener("tlp-dropdown-shown", function () {
            manageUserPreferences(help_button, patch);
        });
    }

    const help_shortcuts_trigger = document.getElementById("help-dropdomn-shortcuts");
    if (help_shortcuts_trigger) {
        help_shortcuts_trigger.addEventListener("click", function (event) {
            event.preventDefault();
        });
    }
});

function polyfillCustomEventIE11(): void {
    // IE11 does not have a correct implementation of CustomEvent, window.CustomEvent is defined but it is a function...
    if (typeof window.CustomEvent === "function") {
        return;
    }

    function CustomEvent<T>(event: string, params?: CustomEventInit<T>): CustomEvent<T> {
        const params_with_default = params || {
            bubbles: false,
            cancelable: false,
            detail: undefined,
        };
        const evt = document.createEvent("CustomEvent");
        evt.initCustomEvent(
            event,
            params_with_default.bubbles ?? false,
            params_with_default.cancelable ?? false,
            params_with_default.detail
        );
        return evt;
    }

    CustomEvent.prototype = window.Event.prototype;

    // We need to force the TypeScript compiler to ignore this as IE11 does not come with a proper CustomEvent class
    // we fallback to the closer thing which Event however the type cannot match
    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions,@typescript-eslint/no-explicit-any
    window.CustomEvent = CustomEvent as any;
}

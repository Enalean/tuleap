/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { Shortcut, ShortcutsGroup } from "../setup-shortcuts";
import type { GettextProvider } from "../type";

import { clickOnElement, focusElement } from "../shortcuts-handles/trigger-datashortcut-element";
import { showRemainingTests } from "../shortcuts-handles/show-remaining-tests";

export function createCampaignShortcutsGroup(
    doc: Document,
    gettextCatalog: GettextProvider,
): ShortcutsGroup {
    const select_tests: Shortcut = {
        keyboard_inputs: "e",
        description: gettextCatalog.getString("Select tests"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-select-tests]");
        },
    };

    const focus_search_filter: Shortcut = {
        keyboard_inputs: "f",
        description: gettextCatalog.getString("Set focus in search filter"),
        handle: (): void => {
            focusElement(doc, "[data-shortcut-search-filter]");
        },
    };

    const toggle_automated_tests_filter: Shortcut = {
        keyboard_inputs: "a",
        description: gettextCatalog.getString("Toggle automated tests"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-filter-automated]");
        },
    };

    const show_remaining_tests: Shortcut = {
        keyboard_inputs: "r",
        description: gettextCatalog.getString("Show remaining tests only"),
        handle: (): void => {
            showRemainingTests(doc);
        },
    };

    const focus_test_list: Shortcut = {
        keyboard_inputs: "l",
        description: gettextCatalog.getString("Set focus in tests list"),
        handle: (): void => {
            focusElement(doc, "[data-navigation-test-link][tabindex='0']");
        },
    };

    return {
        title: gettextCatalog.getString("Test campaign"),
        shortcuts: [
            select_tests,
            focus_test_list,
            focus_search_filter,
            show_remaining_tests,
            toggle_automated_tests_filter,
        ],
    };
}

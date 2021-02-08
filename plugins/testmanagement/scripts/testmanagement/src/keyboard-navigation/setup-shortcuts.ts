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

import {
    Shortcut,
    ShortcutsGroup,
    addShortcutsGroup,
} from "@tuleap/core/scripts/keyboard-navigation/add-shortcuts-group";

import { Direction, GettextProvider } from "./type";

import { moveFocus } from "./move-focus";
import { clickOnElement, focusElement } from "./trigger-datashortcut-element";

export function setupTestManagementShortcuts(gettextCatalog: GettextProvider): void {
    const move_to_previous_test: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettextCatalog.getString("Select previous test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.previous);
        },
    };

    const move_to_next_test: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettextCatalog.getString("Select next test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.next);
        },
    };

    const move_to_first_test: Shortcut = {
        keyboard_inputs: "home",
        displayed_inputs: "Home,↖",
        description: gettextCatalog.getString("Select first test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.top);
        },
    };

    const move_to_last_test: Shortcut = {
        keyboard_inputs: "end",
        displayed_inputs: "End",
        description: gettextCatalog.getString("Select last test tab from test list"),
        handle: (): void => {
            moveFocus(document, Direction.bottom);
        },
    };

    const edit_campaign: Shortcut = {
        keyboard_inputs: "e",
        description: gettextCatalog.getString("Edit campaign"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-edit-campaign]");
        },
    };

    const focus_search_filter: Shortcut = {
        keyboard_inputs: "f",
        description: gettextCatalog.getString("Set focus in search filter"),
        handle: (): void => {
            focusElement(document, "[data-shortcut-search-filter]");
        },
    };

    const toggle_automated_tests_filter: Shortcut = {
        keyboard_inputs: "a",
        description: gettextCatalog.getString("Toggle automated tests"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-filter-automated]");
        },
    };

    const focus_test_list: Shortcut = {
        keyboard_inputs: "l",
        description: gettextCatalog.getString("Set focus in tests list"),
        handle: (): void => {
            focusElement(document, "[data-navigation-test-link][tabindex='0']");
        },
    };

    const show_scores: Shortcut = {
        keyboard_inputs: "g",
        description: gettextCatalog.getString("Show test campaign scores"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-campaign-scores]");
        },
    };

    const mark_as_success: Shortcut = {
        keyboard_inputs: "m + p",
        description: gettextCatalog.getString("Mark test as passed"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-passed]");
        },
    };

    const mark_as_blocked: Shortcut = {
        keyboard_inputs: "m + b",
        description: gettextCatalog.getString("Mark test as blocked"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-blocked]");
        },
    };

    const mark_as_not_run: Shortcut = {
        keyboard_inputs: "m + n",
        description: gettextCatalog.getString("Mark test as not run"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-not-run]");
        },
    };

    const focus_comment: Shortcut = {
        keyboard_inputs: "t + c",
        description: gettextCatalog.getString("Set focus in comment field"),
        handle: (): void => {
            focusElement(document, "[data-shortcut-current-test-comment]");
        },
    };

    const edit_test: Shortcut = {
        keyboard_inputs: "t + e",
        description: gettextCatalog.getString("Edit test"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-edit-test]");
        },
    };

    const new_bug: Shortcut = {
        keyboard_inputs: "t + b",
        description: gettextCatalog.getString("Create a new bug"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-new-bug]");
        },
    };

    const link_bug: Shortcut = {
        keyboard_inputs: "t + l",
        description: gettextCatalog.getString("Link to an existing bug"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-link-bug]");
        },
    };

    const show_dependency_graph: Shortcut = {
        keyboard_inputs: "t + d",
        description: gettextCatalog.getString("Show dependencies graph"),
        handle: (): void => {
            clickOnElement(document, "[data-shortcut-dependency-graph]");
        },
    };

    const test_management_navigation_shortcuts_group: ShortcutsGroup = {
        title: gettextCatalog.getString("Navigation in Test Management"),
        details: gettextCatalog.getString(
            "Navigation shortcuts are available when a test is selected in tests list"
        ),
        shortcuts: [
            move_to_first_test,
            move_to_last_test,
            move_to_previous_test,
            move_to_next_test,
        ],
    };

    const test_management_campaign_shortcuts_group: ShortcutsGroup = {
        title: gettextCatalog.getString("Campaign page shortcuts in Test Management"),
        shortcuts: [
            edit_campaign,
            focus_search_filter,
            toggle_automated_tests_filter,
            focus_test_list,
            show_scores,
        ],
    };

    const test_management_actions_shortcuts_group: ShortcutsGroup = {
        title: gettextCatalog.getString("Test shortcuts in Test Management"),
        details: gettextCatalog.getString(
            "Test shortcuts are available on the test currently open."
        ),
        shortcuts: [
            mark_as_success,
            mark_as_blocked,
            mark_as_not_run,
            focus_comment,
            edit_test,
            new_bug,
            link_bug,
            show_dependency_graph,
        ],
    };

    addShortcutsGroup(document, test_management_navigation_shortcuts_group);
    addShortcutsGroup(document, test_management_campaign_shortcuts_group);
    addShortcutsGroup(document, test_management_actions_shortcuts_group);
}

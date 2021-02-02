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

import { GettextProvider } from "../type";

import { clickOnElement, focusElement } from "../shortcuts-handles/trigger-datashortcut-element";

export function setupTestActionsShortcuts(gettextCatalog: GettextProvider): void {
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
    addShortcutsGroup(document, test_management_actions_shortcuts_group);
}

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
import { markTestAndJumpToNext } from "../shortcuts-handles/mark-test";
import { BLOCKED, NOTRUN, PASSED } from "../type";

export function createTestExecutionShortcutsGroup(
    doc: Document,
    gettextCatalog: GettextProvider,
): ShortcutsGroup {
    const mark_as_success: Shortcut = {
        keyboard_inputs: "m + p",
        description: gettextCatalog.getString("Mark test as passed and open next test"),
        handle: (): void => {
            markTestAndJumpToNext(doc, PASSED);
        },
    };

    const mark_as_blocked: Shortcut = {
        keyboard_inputs: "m + b",
        description: gettextCatalog.getString("Mark test as blocked and open next test"),
        handle: (): void => {
            markTestAndJumpToNext(doc, BLOCKED);
        },
    };

    const mark_as_not_run: Shortcut = {
        keyboard_inputs: "m + n",
        description: gettextCatalog.getString("Mark test as not run and open next test"),
        handle: (): void => {
            markTestAndJumpToNext(doc, NOTRUN);
        },
    };

    const focus_comment: Shortcut = {
        keyboard_inputs: "t + c",
        description: gettextCatalog.getString("Set focus in comment field"),
        handle: (): void => {
            focusElement(doc, "[data-shortcut-current-test-comment]");
        },
    };

    const edit_test: Shortcut = {
        keyboard_inputs: "t + e",
        description: gettextCatalog.getString("Edit test"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-edit-test]");
        },
    };

    const new_bug: Shortcut = {
        keyboard_inputs: "t + b",
        description: gettextCatalog.getString("Create a new bug"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-new-bug]");
        },
    };

    const link_bug: Shortcut = {
        keyboard_inputs: "t + l",
        description: gettextCatalog.getString("Link to an existing bug"),
        handle: (): void => {
            clickOnElement(doc, "[data-shortcut-link-bug]");
        },
    };

    return {
        title: gettextCatalog.getString("Test execution"),
        details: gettextCatalog.getString("Shortcuts available on the test currently open."),
        shortcuts: [
            mark_as_success,
            mark_as_blocked,
            mark_as_not_run,
            focus_comment,
            edit_test,
            new_bug,
            link_bug,
        ],
    };
}

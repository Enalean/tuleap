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

import type { Shortcut, ShortcutsGroup } from "@tuleap/keyboard-shortcuts";
import { addShortcutsGroup } from "@tuleap/keyboard-shortcuts";

import type { ArrowKey, GettextProvider } from "../type";
import { DOWN, UP, RIGHT, LEFT } from "../type";
import { moveFocus } from "./move-focus";
import {
    editCard,
    editRemainingEffort,
    handleFocusFirstSwimlane,
    focusSwimlaneFirstCard,
    returnToParent,
    toggleClosedItems,
} from "./quick-access-shortcuts-helpers";

export class KeyboardShortcuts {
    private moving_shortcuts_group: ShortcutsGroup | null = null;
    private quick_access_shortcuts_group: ShortcutsGroup | null = null;
    private readonly gettextCatalog: GettextProvider;
    private readonly doc: Document;

    constructor(doc: Document, gettextCatalog: GettextProvider) {
        this.doc = doc;
        this.gettextCatalog = gettextCatalog;
    }

    setNavigation(moving_cards_handler: (event: KeyboardEvent, direction: ArrowKey) => void): void {
        this.moving_shortcuts_group = createNavigationShortcutsGroup(
            this.doc,
            this.gettextCatalog,
            moving_cards_handler,
        );
        addShortcutsGroup(this.doc, this.moving_shortcuts_group);
    }

    setQuickAccess(): void {
        this.quick_access_shortcuts_group = createQuickAccessShortcutsGroup(
            this.doc,
            this.gettextCatalog,
        );
        addShortcutsGroup(this.doc, this.quick_access_shortcuts_group);
    }
}

function createNavigationShortcutsGroup(
    doc: Document,
    gettext_provider: GettextProvider,
    handler: (event: KeyboardEvent, direction: ArrowKey) => void,
): ShortcutsGroup {
    const next: Shortcut = {
        keyboard_inputs: "k,down",
        displayed_inputs: "k,↓",
        description: gettext_provider.$gettext("Move to next swimlane or card"),
        handle: (): void => {
            moveFocus(doc, DOWN);
        },
    };

    const previous: Shortcut = {
        keyboard_inputs: "j,up",
        displayed_inputs: "j,↑",
        description: gettext_provider.$gettext("Move to previous swimlane or card"),
        handle: (): void => {
            moveFocus(doc, UP);
        },
    };

    const right: Shortcut = {
        keyboard_inputs: "l,right",
        displayed_inputs: "l,→",
        description: gettext_provider.$gettext("Move to right cell"),
        handle: (): void => {
            moveFocus(doc, RIGHT);
        },
    };

    const left: Shortcut = {
        keyboard_inputs: "h,left",
        displayed_inputs: "h,←",
        description: gettext_provider.$gettext("Move to left cell"),
        handle: (): void => {
            moveFocus(doc, LEFT);
        },
    };

    const move_right: Shortcut = {
        keyboard_inputs: "shift+right,shift+l",
        displayed_inputs: "Shift+l,Shift+→",
        description: gettext_provider.$gettext("Move card to the right cell"),
        handle: (event): void => {
            handler(event, RIGHT);
        },
    };

    const move_left: Shortcut = {
        keyboard_inputs: "shift+left,shift+h",
        displayed_inputs: "Shift+h,Shift+←",
        description: gettext_provider.$gettext("Move card to the left cell"),
        handle: (event): void => {
            handler(event, LEFT);
        },
    };

    const move_up: Shortcut = {
        keyboard_inputs: "shift+j,shift+up",
        displayed_inputs: "Shift+j,Shift+↑",
        description: gettext_provider.$gettext("Move card up"),
        handle: (event): void => {
            handler(event, UP);
        },
    };

    const move_down: Shortcut = {
        keyboard_inputs: "shift+k,shift+down",
        displayed_inputs: "Shift+k,Shift+↓",
        description: gettext_provider.$gettext("Move card down"),
        handle: (event): void => {
            handler(event, DOWN);
        },
    };

    return {
        title: gettext_provider.$gettext("Navigation in Taskboard"),
        shortcuts: [next, previous, right, left, move_right, move_left, move_down, move_up],
    };
}

function createQuickAccessShortcutsGroup(
    doc: Document,
    gettext_provider: GettextProvider,
): ShortcutsGroup {
    const edit_card: Shortcut = {
        keyboard_inputs: "e",
        description: gettext_provider.$gettext("Edit card"),
        handle: (event) => editCard(event),
    };

    const edit_remaining_effort: Shortcut = {
        keyboard_inputs: "r",
        description: gettext_provider.$gettext("Edit remaining efforts"),
        handle: (event) => editRemainingEffort(event),
    };

    const toggle_closed_items: Shortcut = {
        keyboard_inputs: "t",
        description: gettext_provider.$gettext("Toggle closed items"),
        handle: () => toggleClosedItems(doc),
    };

    const return_to_parent: Shortcut = {
        keyboard_inputs: "escape",
        description: gettext_provider.$gettext("Return to parent card or swimlane"),
        handle: (event) => returnToParent(event),
    };

    const focus_first_swimlane: Shortcut = {
        keyboard_inputs: "ctrl+j,ctrl+up",
        displayed_inputs: "Ctrl+j,Ctrl+↑",
        description: gettext_provider.$gettext("Place focus on first swimlane"),
        handle: (event) => handleFocusFirstSwimlane(doc, event),
    };

    const focus_first_swimlane_card: Shortcut = {
        keyboard_inputs: "enter",
        description: gettext_provider.$gettext("Place focus on the first swimlane card"),
        handle: (event) => focusSwimlaneFirstCard(event),
    };

    return {
        title: gettext_provider.$gettext("Quick access"),
        shortcuts: [
            focus_first_swimlane,
            edit_card,
            edit_remaining_effort,
            toggle_closed_items,
            focus_first_swimlane_card,
            return_to_parent,
        ],
    };
}

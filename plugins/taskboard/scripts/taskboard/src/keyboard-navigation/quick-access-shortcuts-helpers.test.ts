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
    editRemainingEffort,
    toggleClosedItems,
    editCard,
    returnToParent,
} from "./quick-access-shortcuts-helpers";
import { CARD, SWIMLANE } from "../type";

describe("quick-access-shortcuts-handlers", () => {
    let doc: Document;

    let edit_card_button: HTMLButtonElement;
    let edit_card_click: jest.SpyInstance;

    let card: HTMLElement;
    let card_focus: jest.SpyInstance;

    let remaining_effort_button: HTMLButtonElement;
    let remaining_effort_click: jest.SpyInstance;

    let parent_card: HTMLElement;
    let parent_card_focus: jest.SpyInstance;

    let swimlane: HTMLElement;
    let swimlane_focus: jest.SpyInstance;

    let toggle_closed_items_input: HTMLInputElement;
    let toggle_closed_items_click: jest.SpyInstance;

    let keyboard_event: KeyboardEvent;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        setupDocument(doc);

        edit_card_click = jest.spyOn(edit_card_button, "click");
        card_focus = jest.spyOn(card, "focus");
        remaining_effort_click = jest.spyOn(remaining_effort_button, "click");
        parent_card_focus = jest.spyOn(parent_card, "focus");
        swimlane_focus = jest.spyOn(swimlane, "focus");
        toggle_closed_items_click = jest.spyOn(toggle_closed_items_input, "click");

        keyboard_event = new KeyboardEvent("keyboard-event");
    });

    describe("editRemainingEffort", () => {
        it("does nothing if KeyboardEvent target is not an HTMLElement", () => {
            editRemainingEffort(keyboard_event);

            expect(remaining_effort_click).not.toHaveBeenCalled();
        });

        it("does nothing if Event Target has no SWIMLANE parent", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: remaining_effort_button,
            });
            editRemainingEffort(keyboard_event);

            expect(remaining_effort_click).toHaveBeenCalled();
        });

        it("clicks on SWIMLANE remaining effort button if it was found", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: remaining_effort_button,
            });
            editRemainingEffort(keyboard_event);

            expect(remaining_effort_click).toHaveBeenCalled();
        });
    });

    describe("toggleClosedItems", () => {
        it("throws an error if unchecked toggle button could not be found", () => {
            toggle_closed_items_input.remove();

            expect(() => toggleClosedItems(doc)).toThrow();
            expect(toggle_closed_items_click).not.toHaveBeenCalled();
        });

        it("clicks on the unchecked toggle button", () => {
            toggleClosedItems(doc);

            expect(toggle_closed_items_click).toHaveBeenCalled();
        });
    });

    describe("returnToParent", () => {
        it("does nothing if KeyboardEvent target is not an HTMLElement", () => {
            returnToParent(keyboard_event);

            expect(swimlane_focus).not.toHaveBeenCalled();
            expect(card_focus).not.toHaveBeenCalled();
        });

        it("focuses the SWIMLANE if active element is the swimlane parent card", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: parent_card,
            });
            returnToParent(keyboard_event);

            expect(swimlane_focus).toHaveBeenCalled();
        });

        it("focuses the parent card if active element is a CARD or ADDFORM", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: card,
            });
            returnToParent(keyboard_event);

            expect(parent_card_focus).toHaveBeenCalled();
        });

        it("throws an error if SWIMLANE could not be found", () => {
            swimlane.removeAttribute("data-navigation");
            Object.defineProperty(keyboard_event, "target", {
                value: card,
            });

            expect(() => returnToParent(keyboard_event)).toThrow();
            expect(swimlane_focus).not.toHaveBeenCalled();
            expect(card_focus).not.toHaveBeenCalled();
        });

        it("focuses the card if active element is inside that card", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: edit_card_button,
            });
            returnToParent(keyboard_event);

            expect(card_focus).toHaveBeenCalled();
        });
    });

    describe("editCard", () => {
        it("does nothing if KeyboardEvent target is not an HTMLElement", () => {
            editCard(keyboard_event);

            expect(edit_card_click).not.toHaveBeenCalled();
        });

        it("does nothing if KeyboardEvent target is not a CARD", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: swimlane,
            });
            editCard(keyboard_event);

            expect(edit_card_click).not.toHaveBeenCalled();
        });

        it("clicks on the Edit card button of the card", () => {
            Object.defineProperty(keyboard_event, "target", {
                value: card,
            });
            editCard(keyboard_event);

            expect(edit_card_click).toHaveBeenCalled();
        });
    });

    function setupDocument(doc: Document): void {
        edit_card_button = doc.createElement("button");
        edit_card_button.dataset.shortcut = "edit-card";

        parent_card = doc.createElement("div");
        parent_card.dataset.shortcut = "parent-card";
        parent_card.setAttribute("tabindex", "0");

        card = doc.createElement("div");
        card.dataset.navigation = CARD;
        card.setAttribute("tabindex", "0");
        card.append(edit_card_button);

        remaining_effort_button = doc.createElement("button");
        remaining_effort_button.dataset.shortcut = "edit-remaining-effort";

        swimlane = doc.createElement("div");
        swimlane.dataset.navigation = SWIMLANE;
        swimlane.setAttribute("tabindex", "0");
        swimlane.append(parent_card, card, remaining_effort_button);

        toggle_closed_items_input = doc.createElement("input");
        toggle_closed_items_input.dataset.shortcut = "toggle-closed-items";

        doc.body.append(swimlane, toggle_closed_items_input);
    }
});

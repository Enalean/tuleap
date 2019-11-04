/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
import * as drag_drop from "./drag-drop";
import * as item_finder from "./html-to-item";

function createElement(...css_classes: string[]): Element {
    const local_document = document.implementation.createHTMLDocument();
    const div = local_document.createElement("div");
    div.classList.add(...css_classes);
    return div;
}

function createNonHTMLElement(): Element {
    const local_document = document.implementation.createDocument(
        "http://www.w3.org/2000/svg",
        "svg",
        null
    );
    return local_document.createElement("g");
}

describe(`drag-drop helper`, () => {
    describe(`isContainer()`, () => {
        it(`Given an undefined element, it will return false`, () => {
            expect(drag_drop.isContainer(undefined)).toBe(false);
        });

        it(`Given an unrelated element, it will return false`, () => {
            const element = createElement("unrelated-class");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a swimlane header cell, it will return false`, () => {
            const element = createElement("taskboard-cell", "taskboard-cell-swimlane-header");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a collapsed swimlane cell, it will return false`, () => {
            const element = createElement(
                "taskboard-cell",
                "taskboard-swimlane-collapsed-cell-placeholder"
            );
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a parent card, it will return false`, () => {
            const element = createElement("taskboard-cell", "taskboard-card-parent");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a "regular" taskboard cell, it will return true`, () => {
            const element = createElement("taskboard-cell");
            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an undefined element, it will return false`, () => {
            const element = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, handle)).toBe(false);
        });

        it(`Given an undefined handle, it will return false`, () => {
            const element = createElement();
            const handle = undefined;
            expect(drag_drop.canMove(element, handle)).toBe(false);
        });

        it(`Given an unrelated element, it will return false`, () => {
            const element = createElement("unrelated-class");
            const handle = createElement();
            expect(drag_drop.canMove(element, handle)).toBe(false);
        });

        it(`Given a collapsed card element, it will return false`, () => {
            const element = createElement("taskboard-card", "taskboard-card-collapsed");
            const handle = createElement();
            expect(drag_drop.canMove(element, handle)).toBe(false);
        });

        it(`Given a handle marked as "no-drag", it will return false`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement("taskboard-item-no-drag");
            expect(drag_drop.canMove(element, handle)).toBe(false);
        });

        it(`Given a "regular" taskboard card, it will return true`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement();
            expect(drag_drop.canMove(element, handle)).toBe(true);
        });
    });

    describe(`accepts()`, () => {
        it(`Given an undefined target, it will return false`, () => {
            const element = createElement();
            const target = undefined;
            const source = createElement();
            expect(drag_drop.accepts(element, target, source)).toBe(false);
        });

        it(`Given an undefined source, it will return false`, () => {
            const element = createElement();
            const target = createElement();
            const source = undefined;
            expect(drag_drop.accepts(element, target, source)).toBe(false);
        });

        it(`Given an target that is not a HTMLElement, it will return false`, () => {
            const element = createElement();
            const non_html_target = createNonHTMLElement();
            const source = createElement();
            expect(drag_drop.accepts(element, non_html_target, source)).toBe(false);
        });

        it(`Given a source that is not a HTMLElement, it will return false`, () => {
            const element = createElement();
            const target = createElement();
            const non_html_source = createNonHTMLElement();
            expect(drag_drop.accepts(element, target, non_html_source)).toBe(false);
        });

        it(`When the card has been dropped in the same cell, it will return true`, () => {
            jest.spyOn(item_finder, "hasCardBeenDroppedInTheSameCell").mockReturnValue(true);
            const element = createElement();
            const target = createElement();
            const source = createElement();
            expect(drag_drop.accepts(element, target, source)).toBe(true);
        });
    });
});

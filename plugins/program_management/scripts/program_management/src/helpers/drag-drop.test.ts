/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
import { createElement } from "./jest/create-dom-element";

describe(`drag-drop helper`, () => {
    describe(`isContainer()`, () => {
        it(`Given an element without a data-is-container flag, it will return false`, () => {
            const element = createElement("taskboard-card");

            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a taskboard cell with a data-is-container flag, it will return true`, () => {
            const element = createElement("taskboard-cell");
            element.setAttribute("data-is-container", "true");

            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an element with no draggable attribute, it will return false`, () => {
            const element = createElement("taskboard-cell");

            expect(drag_drop.canMove(element)).toBe(false);
        });

        it(`Given a element with a draggable flag, it will return true`, () => {
            const element = createElement("taskboard-card", "taskboard-card-collapsed");
            element.setAttribute("draggable", "true");

            expect(drag_drop.canMove(element)).toBe(true);
        });
    });

    describe("invalid", () => {
        it(`Given a handle with a not-drag-handle flag, it will return true`, () => {
            const handle = createElement();
            handle.setAttribute("data-not-drag-handle", "true");

            expect(drag_drop.invalid(handle)).toBe(true);
        });

        it(`Given a handle whose a parent has a not-drag-handle flag, it will return true`, () => {
            const handle = createElement();
            const parent = createElement();
            handle.setAttribute("data-not-drag-handle", "true");
            parent.appendChild(handle);

            expect(drag_drop.invalid(handle)).toBe(true);
        });

        it(`Given a regular handle, it will return false`, () => {
            const handle = createElement("taskboard-stuff");
            expect(drag_drop.invalid(handle)).toBe(false);
        });
    });

    describe(`checkAcceptsDrop()`, () => {
        it(`When all elements are DOM valid, then the drop i accepted`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            dropped_card.setAttribute("data-tracker-id", "9");
            target_cell.setAttribute("data-accepted-trackers-ids", "10,11,12");

            expect(drag_drop.checkAcceptsDrop({ dropped_card, target_cell, source_cell })).toBe(
                true
            );
        });
    });
});

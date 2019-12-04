/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
import { createElement, createNonHTMLElement } from "./jest/create-dom-element";
import { SwimlaneState } from "../store/swimlane/type";
import * as item_finder from "./html-to-item";
import { Store } from "vuex";
import { RootState } from "../store/type";

describe(`drag-drop helper`, () => {
    describe(`isContainer()`, () => {
        it(`Given an undefined element, it will return false`, () => {
            expect(drag_drop.isContainer(undefined)).toBe(false);
        });

        it(`Given a taskboard cell with a data-is-container flag, it will return true`, () => {
            const element = createElement("taskboard-cell");
            element.setAttribute("data-is-container", "true");

            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an undefined element, it will return false`, () => {
            const element = undefined;
            expect(drag_drop.canMove(element)).toBe(false);
        });

        it(`Given a element with a is-draggable flag, it will return true`, () => {
            const element = createElement("taskboard-card", "taskboard-card-collapsed");
            element.setAttribute("data-is-draggable", "true");

            expect(drag_drop.canMove(element)).toBe(true);
        });
    });

    describe("invalid", () => {
        it(`Given a handle with a not-drag-handle flag, it will return true`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement();
            handle.setAttribute("data-not-drag-handle", "true");

            expect(drag_drop.invalid(element, handle)).toBe(true);
        });

        it(`Given a handle whose a parent has a not-drag-handle flag, it will return true`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement();

            const parent = createElement();
            handle.setAttribute("data-not-drag-handle", "true");

            parent.appendChild(element);

            expect(drag_drop.invalid(element, handle)).toBe(true);
        });

        it(`Given a regular handle, it will return false`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement("taskboard-stuff");
            expect(drag_drop.invalid(element, handle)).toBe(false);
        });

        it(`Given no handle, it will return true`, () => {
            const element = createElement("taskboard-card");
            const handle = undefined;
            expect(drag_drop.invalid(element, handle)).toBe(true);
        });
    });

    describe(`checkCellAcceptsDrop()`, () => {
        let store: Store<RootState>, swimlane_state: SwimlaneState;

        beforeEach(() => {
            swimlane_state = {
                dropzone_rejecting_drop: undefined
            } as SwimlaneState;

            store = ({
                modules: {
                    swimlane: {
                        state: swimlane_state
                    }
                },
                commit: jest.fn()
            } as unknown) as Store<RootState>;
        });

        it(`Given an undefined target, it will return false`, () => {
            const dropped_card = createElement();
            const target_cell = undefined;
            const source_cell = createElement();
            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);
        });

        it(`Given an undefined source_cell, it will return false`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const source_cell = undefined;
            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);
        });

        it(`Given an undefined dropped_card, it will return false`, () => {
            const dropped_card = undefined;
            const target_cell = createElement();
            const source_cell = createElement();
            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);
        });

        it(`Given a target_cell that is not a HTMLElement, it will return false`, () => {
            const dropped_card = createElement();
            const non_html_target_cell = createNonHTMLElement();
            const source_cell = createElement();
            expect(
                drag_drop.checkCellAcceptsDrop(store, {
                    dropped_card,
                    target_cell: non_html_target_cell,
                    source_cell
                })
            ).toBe(false);
        });

        it(`Given a source_cell that is not a HTMLElement, it will return false`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const non_html_source_cell = createNonHTMLElement();
            expect(
                drag_drop.checkCellAcceptsDrop(store, {
                    dropped_card,
                    target_cell,
                    source_cell: non_html_source_cell
                })
            ).toBe(false);
        });

        it(`Given an dropped_card that is not a HTMLElement, it will return false`, () => {
            const non_html_dropped_card = createNonHTMLElement();
            const target_cell = createElement();
            const source_cell = createElement();
            expect(
                drag_drop.checkCellAcceptsDrop(store, {
                    dropped_card: non_html_dropped_card,
                    target_cell,
                    source_cell
                })
            ).toBe(false);
        });

        it(`When the card has been dropped in another swimlane, then it will return false`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const source_cell = createElement();

            jest.spyOn(item_finder, "isDraggedOverAnotherSwimlane").mockReturnValue(true);

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);
        });

        it(`When the drop is accepted by the column, then it will return true`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const source_cell = createElement();

            dropped_card.setAttribute("data-tracker-id", "10");
            target_cell.setAttribute("data-accepted-trackers-ids", "10,11,12");

            jest.spyOn(item_finder, "isDraggedOverAnotherSwimlane").mockReturnValue(false);

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(true);
        });

        it(`When the drop is not accepted by the column, then it will toggle the error overlay and return false`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const source_cell = createElement();

            jest.spyOn(item_finder, "isDraggedOverAnotherSwimlane").mockReturnValue(false);

            dropped_card.setAttribute("data-tracker-id", "9");
            dropped_card.setAttribute("data-column-id", "1");
            target_cell.setAttribute("data-accepted-trackers-ids", "10,11,12");
            target_cell.setAttribute("data-column-id", "2");

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);

            expect(store.commit).toHaveBeenCalledWith(
                "swimlane/setDropZoneRejectingDrop",
                target_cell
            );
        });

        it(`When the user has not the right to update the mapped field
            And the target column is the source column,
            Then it will return true`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            jest.spyOn(item_finder, "isDraggedOverAnotherSwimlane").mockReturnValue(false);

            dropped_card.setAttribute("data-tracker-id", "9");
            target_cell.setAttribute("data-accepted-trackers-ids", "10,11,12");

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(true);
        });
    });
});

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

        it(`Given an unrelated element, it will return false`, () => {
            const element = createElement("unrelated-class");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a swimlane header cell, it will return false`, () => {
            const element = createElement("taskboard-cell", "taskboard-cell-swimlane-header");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a parent card, it will return false`, () => {
            const element = createElement("taskboard-cell", "taskboard-card-parent");
            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a collapsed column, it will return true`, () => {
            const element = createElement("taskboard-cell", "taskboard-cell-collapsed");
            expect(drag_drop.isContainer(element)).toBe(true);
        });

        it(`Given a "regular" taskboard cell, it will return true`, () => {
            const element = createElement("taskboard-cell");
            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an undefined element, it will return false`, () => {
            const element = undefined;
            const target = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, target, handle)).toBe(false);
        });

        it(`Given an undefined handle, it will return false`, () => {
            const element = createElement();
            const target = undefined;
            const handle = undefined;
            expect(drag_drop.canMove(element, target, handle)).toBe(false);
        });

        it(`Given an unrelated element, it will return false`, () => {
            const element = createElement("unrelated-class");
            const target = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, target, handle)).toBe(false);
        });

        it(`Given a collapsed card element, it will return false`, () => {
            const element = createElement("taskboard-card", "taskboard-card-collapsed");
            const target = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, target, handle)).toBe(false);
        });

        it(`Given a child card, it will return true`, () => {
            const element = createElement("taskboard-child");
            const target = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, target, handle)).toBe(true);
        });

        it(`Given a solo card cell, it will return true`, () => {
            const element = createElement("taskboard-cell-solo-card");
            const target = undefined;
            const handle = createElement();
            expect(drag_drop.canMove(element, target, handle)).toBe(true);
        });
    });

    describe("invalid", () => {
        it(`Given a handle marked as "no-drag", it will return true`, () => {
            const element = createElement("taskboard-card");
            const handle = createElement("taskboard-item-no-drag");
            expect(drag_drop.invalid(element, handle)).toBe(true);
        });

        it(`Given a handle not marked as "no-drag", it will return false`, () => {
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
                last_hovered_drop_zone: undefined
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

            jest.spyOn(item_finder, "hasCardBeenDroppedInAnotherSwimlane").mockReturnValue(true);

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

            jest.spyOn(item_finder, "hasCardBeenDroppedInAnotherSwimlane").mockReturnValue(false);

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(true);
        });

        it(`When the drop is not accepted by the column, then it will toggle the error overlay and return false`, () => {
            const dropped_card = createElement();
            const target_cell = createElement();
            const source_cell = createElement();

            jest.spyOn(item_finder, "hasCardBeenDroppedInAnotherSwimlane").mockReturnValue(false);

            dropped_card.setAttribute("data-tracker-id", "9");
            target_cell.setAttribute("data-accepted-trackers-ids", "10,11,12");

            expect(
                drag_drop.checkCellAcceptsDrop(store, { dropped_card, target_cell, source_cell })
            ).toBe(false);

            expect(store.commit).toHaveBeenCalledWith("swimlane/setHighlightOnLastHoveredDropZone");
        });
    });
});

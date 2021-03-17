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
import type { SuccessfulDropCallbackParameter } from "@tuleap/drag-and-drop";
import * as featurePlanner from "./ProgramIncrement/Feature/feature-planner";
import * as backlogAdder from "./ProgramIncrement/add-to-top-backlog";
import * as tlp from "tlp";
import type { State } from "../type";
import type { Store } from "vuex";
import type { ProgramIncrement } from "./ProgramIncrement/program-increment-retriever";

jest.mock("tlp");

describe(`drag-drop helper`, () => {
    describe(`isContainer()`, () => {
        it(`Given an element without a data-is-container flag, it will return false`, () => {
            const element = createElement();

            expect(drag_drop.isContainer(element)).toBe(false);
        });

        it(`Given a taskboard cell with a data-is-container flag, it will return true`, () => {
            const element = createElement();
            element.setAttribute("data-is-container", "true");

            expect(drag_drop.isContainer(element)).toBe(true);
        });
    });

    describe(`canMove()`, () => {
        it(`Given an element with no draggable attribute, it will return false`, () => {
            const element = createElement();

            expect(drag_drop.canMove(element)).toBe(false);
        });

        it(`Given a element with a draggable flag, it will return true`, () => {
            const element = createElement();
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
        it(`Given can plan attribute is not provided, Then the drop is rejected`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            expect(drag_drop.checkAcceptsDrop({ dropped_card, target_cell, source_cell })).toBe(
                false
            );
        });

        it(`Given user can not plan and given zone does not have a message, Then the drop is rejected`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            expect(drag_drop.checkAcceptsDrop({ dropped_card, target_cell, source_cell })).toBe(
                false
            );
        });

        it(`Given user can not plan and given zone have an error message, Then the drop is rejected and message is displayed`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;

            const error_message = createElement("drop-not-accepted-overlay");
            target_cell.appendChild(error_message);

            expect(drag_drop.checkAcceptsDrop({ dropped_card, target_cell, source_cell })).toBe(
                false
            );
            expect(error_message.classList).toContain("drop-not-accepted");
        });

        it(`Given user can plan and given zone have an error message, Then the drop is accepted`, () => {
            const dropped_card = createElement();
            const source_cell = createElement();
            const target_cell = source_cell;
            target_cell.setAttribute("data-can-plan", "true");

            expect(drag_drop.checkAcceptsDrop({ dropped_card, target_cell, source_cell })).toBe(
                true
            );
        });
    });

    describe(`handleDrop()`, () => {
        it(`Plan elements`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "14");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "1");
            target_dropzone.setAttribute("data-artifact-link-field-id", "1234");
            target_dropzone.setAttribute("data-planned-feature-ids", "12,13");

            const feature_planner = jest.spyOn(featurePlanner, "planElementInProgramIncrement");
            jest.spyOn(tlp, "put");

            const getProgramIncrementFromId = jest
                .fn()
                .mockReturnValue({ id: 56 } as ProgramIncrement);

            const store = ({
                getters: { getProgramIncrementFromId },
                dispatch: jest.fn(),
            } as unknown) as Store<State>;

            await drag_drop.handleDrop(
                store,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as SuccessfulDropCallbackParameter,
                101
            );

            expect(getProgramIncrementFromId).toHaveBeenCalledWith(1);

            expect(store.dispatch).toHaveBeenCalledWith("planFeatureInProgramIncrement", {
                feature_id: 14,
                program_increment: { id: 56 },
            });

            expect(feature_planner).toHaveBeenCalledWith(1, 1234, [
                { id: 14 },
                { id: 12 },
                { id: 13 },
            ]);
        });

        it(`Removes elements from program increment`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            dropped_element.setAttribute("data-artifact-link-field-id", "1234");
            dropped_element.setAttribute("data-planned-feature-ids", "12,13");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const feature_planner = jest.spyOn(featurePlanner, "planElementInProgramIncrement");
            jest.spyOn(backlogAdder, "addElementToTopBackLog");
            jest.spyOn(tlp, "put");

            const getProgramIncrementFromId = jest
                .fn()
                .mockReturnValue({ id: 56 } as ProgramIncrement);

            const store = ({
                getters: { getProgramIncrementFromId },
                dispatch: jest.fn(),
            } as unknown) as Store<State>;

            await drag_drop.handleDrop(
                store,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as SuccessfulDropCallbackParameter,
                101
            );

            expect(getProgramIncrementFromId).toHaveBeenCalledWith(1);

            expect(store.dispatch).toHaveBeenCalledWith("unplanFeatureFromProgramIncrement", {
                feature_id: 12,
                program_increment: { id: 56 },
            });

            expect(feature_planner).toHaveBeenCalledWith(1, 1234, [{ id: 13 }]);
        });

        it(`Moves elements from program increment to another`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            dropped_element.setAttribute("data-artifact-link-field-id", "1234");
            dropped_element.setAttribute("data-planned-feature-ids", "12,13");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "2");
            target_dropzone.setAttribute("data-artifact-link-field-id", "3691");
            target_dropzone.setAttribute("data-planned-feature-ids", "125,126");

            const feature_planner = jest.spyOn(featurePlanner, "planElementInProgramIncrement");
            jest.spyOn(tlp, "put");

            const getProgramIncrementFromId = jest
                .fn()
                .mockReturnValueOnce({ id: 1 } as ProgramIncrement)
                .mockReturnValueOnce({ id: 2 } as ProgramIncrement);

            const store = ({
                getters: { getProgramIncrementFromId },
                dispatch: jest.fn(),
            } as unknown) as Store<State>;

            await drag_drop.handleDrop(
                store,
                {
                    dropped_element,
                    source_dropzone,
                    target_dropzone,
                } as SuccessfulDropCallbackParameter,
                101
            );

            expect(getProgramIncrementFromId).toHaveBeenNthCalledWith(1, 1);
            expect(getProgramIncrementFromId).toHaveBeenNthCalledWith(2, 2);

            expect(store.dispatch).toHaveBeenCalledWith(
                "moveFeatureFromProgramIncrementToAnother",
                {
                    feature_id: 12,
                    from_program_increment: { id: 1 },
                    to_program_increment: { id: 2 },
                }
            );

            expect(feature_planner).toHaveBeenNthCalledWith(1, 1, 1234, [{ id: 13 }]);
            expect(feature_planner).toHaveBeenNthCalledWith(2, 2, 3691, [
                { id: 12 },
                { id: 125 },
                { id: 126 },
            ]);
        });
    });
});

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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

import * as actions from "./actions";
import type { Feature, State } from "../type";
import type { ActionContext } from "vuex";
import type { HandleDropContextWithProgramId } from "../helpers/drag-drop";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import { createElement } from "../helpers/jest/create-dom-element";
import * as tlpFetch from "@tuleap/tlp-fetch";
import type { UserStory } from "../helpers/UserStories/user-stories-retriever";
import * as FeatureReordering from "../helpers/feature-reordering";
import * as FeatureMoving from "../helpers/feature-moving";

jest.mock("@tuleap/tlp-fetch");

jest.mock("../helpers/feature-moving");
jest.mock("../helpers/feature-reordering");

describe("Actions", () => {
    let context: ActionContext<State, State>;
    beforeEach(() => {
        context = {
            commit: jest.fn(),
            state: {} as State,
            getters: {},
        } as unknown as ActionContext<State, State>;
    });

    describe(`handleDrop()`, () => {
        let moveFeatureFromBacklogToProgramIncrement: jest.SpyInstance;
        let moveFeatureFromProgramIncrementToBacklog: jest.SpyInstance;
        let moveFeatureFromProgramIncrementToAnotherProgramIncrement: jest.SpyInstance;
        let reorderFeatureInProgramBacklog: jest.SpyInstance;
        let reorderFeatureInSameProgramIncrement: jest.SpyInstance;
        beforeEach(() => {
            moveFeatureFromBacklogToProgramIncrement = jest.spyOn(
                FeatureMoving,
                "moveFeatureFromBacklogToProgramIncrement",
            );
            moveFeatureFromProgramIncrementToBacklog = jest.spyOn(
                FeatureMoving,
                "moveFeatureFromProgramIncrementToBacklog",
            );
            moveFeatureFromProgramIncrementToAnotherProgramIncrement = jest.spyOn(
                FeatureMoving,
                "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
            );
            reorderFeatureInProgramBacklog = jest.spyOn(
                FeatureReordering,
                "reorderFeatureInProgramBacklog",
            );
            reorderFeatureInSameProgramIncrement = jest.spyOn(
                FeatureReordering,
                "reorderFeatureInSameProgramIncrement",
            );
        });
        afterEach(() => {
            jest.clearAllMocks();
        });
        it("When feature are moving from backlog to Program increment, Then FeatureMoving is called", async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "14");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "1");

            const handle_drop = {
                dropped_element,
                source_dropzone,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await actions.handleDrop(context, handle_drop);

            expect(moveFeatureFromBacklogToProgramIncrement).toHaveBeenCalledWith(
                context,
                handle_drop,
                1,
            );
            expect(moveFeatureFromProgramIncrementToAnotherProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromProgramIncrementToBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInProgramBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInSameProgramIncrement).not.toHaveBeenCalled();
        });

        it("When feature are moving from Program increment to backlog, Then FeatureMoving is called", async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const handle_drop = {
                dropped_element,
                source_dropzone,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await actions.handleDrop(context, handle_drop);

            expect(moveFeatureFromProgramIncrementToBacklog).toHaveBeenCalledWith(
                context,
                handle_drop,
                1,
            );
            expect(moveFeatureFromProgramIncrementToAnotherProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromBacklogToProgramIncrement).not.toHaveBeenCalled();
            expect(reorderFeatureInProgramBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInSameProgramIncrement).not.toHaveBeenCalled();
        });

        it("When feature are moving from Program increment to another Program Increment, Then FeatureMoving is called", async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "2");

            const handle_drop = {
                dropped_element,
                source_dropzone,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await actions.handleDrop(context, handle_drop);

            expect(moveFeatureFromProgramIncrementToAnotherProgramIncrement).toHaveBeenCalledWith(
                context,
                handle_drop,
                2,
                1,
            );
            expect(moveFeatureFromBacklogToProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromProgramIncrementToBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInProgramBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInSameProgramIncrement).not.toHaveBeenCalled();
        });

        it(`When feature are moving in the same program increment, Then reorderFeatureInSameProgramIncrement is called`, async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "12");
            dropped_element.setAttribute("data-program-increment-id", "1");
            const source_dropzone = createElement();
            const target_dropzone = createElement();
            target_dropzone.setAttribute("data-program-increment-id", "1");

            const handle_drop = {
                dropped_element,
                source_dropzone,
                target_dropzone,
                program_id: 101,
            } as HandleDropContextWithProgramId;

            await actions.handleDrop(context, handle_drop);

            expect(reorderFeatureInSameProgramIncrement).toHaveBeenCalledWith(
                context,
                handle_drop,
                1,
            );

            expect(moveFeatureFromProgramIncrementToAnotherProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromBacklogToProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromProgramIncrementToBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInProgramBacklog).not.toHaveBeenCalled();
        });

        it("When feature are reordering in top backlog, Then FeatureReordering is called", async () => {
            const dropped_element = createElement();
            dropped_element.setAttribute("data-element-id", "56");
            const source_dropzone = createElement();
            const target_dropzone = createElement();

            const handle_drop = {
                dropped_element,
                source_dropzone,
                target_dropzone,
            } as HandleDropContextWithProgramId;

            await actions.handleDrop(context, handle_drop);

            expect(reorderFeatureInProgramBacklog).toHaveBeenCalledWith(context, handle_drop);
            expect(moveFeatureFromProgramIncrementToAnotherProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromBacklogToProgramIncrement).not.toHaveBeenCalled();
            expect(moveFeatureFromProgramIncrementToBacklog).not.toHaveBeenCalled();
            expect(reorderFeatureInSameProgramIncrement).not.toHaveBeenCalled();
        });
    });

    describe("linkUserStoriesToBePlannedElements", () => {
        it("When user stories are retrieved, Then they are linked to planned element and returned", async () => {
            const expected_stories = [{ id: 104 }] as UserStory[];

            const recursiveGet = jest.spyOn(tlpFetch, "recursiveGet");
            recursiveGet.mockResolvedValue(expected_stories);

            const stories = await actions.linkUserStoriesToBePlannedElements(context, 14);
            expect(context.commit).toHaveBeenCalledWith("linkUserStoriesToBePlannedElement", {
                user_stories: expected_stories,
                element_id: 14,
            });
            expect(stories).toEqual(expected_stories);
        });
    });

    describe("linkUserStoriesToFeature", () => {
        it("When user stories are retrieved, Then they are linked to planned element and returned", async () => {
            const expected_stories = [{ id: 104 }] as UserStory[];
            const program_increment: ProgramIncrement = { id: 45 } as ProgramIncrement;

            const recursiveGet = jest.spyOn(tlpFetch, "recursiveGet");
            recursiveGet.mockResolvedValue(expected_stories);

            const stories = await actions.linkUserStoriesToFeature(context, {
                artifact_id: 14,
                program_increment,
            });
            expect(context.commit).toHaveBeenCalledWith("linkUserStoriesToFeature", {
                user_stories: expected_stories,
                element_id: 14,
                program_increment,
            });
            expect(stories).toEqual(expected_stories);
        });
    });

    describe("retrieveToBePlannedElement", () => {
        it("retrieve to be planned element and store it", async () => {
            const expected_features = [{ id: 104 }] as Feature[];

            const recursiveGet = jest.spyOn(tlpFetch, "recursiveGet");
            recursiveGet.mockResolvedValue(expected_features);

            await actions.retrieveToBePlannedElement(context, 201);

            expect(context.commit).toHaveBeenCalledWith(
                "setToBePlannedElements",
                expected_features,
            );
        });
    });

    describe("getFeatureAndStoreInProgramIncrement", () => {
        it("retrieve features, store in increment and return them", async () => {
            const expected_features = [{ id: 104 }] as Feature[];

            const recursiveGet = jest.spyOn(tlpFetch, "recursiveGet");
            recursiveGet.mockResolvedValue(expected_features);

            const features = await actions.getFeatureAndStoreInProgramIncrement(context, {
                id: 101,
            } as ProgramIncrement);
            expect(context.commit).toHaveBeenCalledWith("addProgramIncrement", {
                id: 101,
                features,
            });
            expect(features).toEqual(expected_features);
        });
    });
});

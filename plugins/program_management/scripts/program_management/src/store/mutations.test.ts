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

import type { Feature, State } from "../type";
import type { LinkUserStoryToFeature, LinkUserStoryToPlannedElement } from "./mutations";
import * as mutations from "./mutations";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import type { UserStory } from "../helpers/UserStories/user-stories-retriever";
import type { FeaturePlanningChange } from "../helpers/feature-reordering";
import { AFTER, BEFORE } from "../helpers/feature-reordering";

describe("Mutations", () => {
    describe("addProgramIncrement", () => {
        it("When there is the same program increment in state, Then error is thrown", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                        features: [{ id: 588 } as Feature],
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
            } as ProgramIncrement;

            expect(() => mutations.addProgramIncrement(state, program_increment)).toThrowError(
                "Program increment with id #14 already exists",
            );
        });

        it("When program increment does not exist in state, Then it is added", () => {
            const state = {
                program_increments: [
                    {
                        id: 15,
                    } as ProgramIncrement,
                ],
            } as State;

            const program_increment = {
                id: 14,
                features: [{ id: 588 } as Feature],
            } as ProgramIncrement;

            mutations.addProgramIncrement(state, program_increment);
            expect(state.program_increments).toHaveLength(2);
            expect(state.program_increments[0]).toEqual({ id: 15 });
            expect(state.program_increments[1]).toEqual({
                id: 14,
                features: [{ id: 588 }],
            });
        });
    });

    describe("addToBePlannedElement", () => {
        it("When element already exists, Then nothing happens", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }] as Feature[],
            } as State;

            const to_be_planned_element = {
                feature: { id: 14 } as Feature,
            } as FeaturePlanningChange;

            mutations.addToBePlannedElement(state, to_be_planned_element);
            expect(state.to_be_planned_elements).toHaveLength(1);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 14 });
        });

        it("When element does not exist in state, Then it is added after sibling", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }] as Feature[],
            } as State;

            const to_be_planned_element = {
                feature: { id: 125 } as Feature,
                order: {
                    feature: { id: 125 } as Feature,
                    direction: AFTER,
                    compared_to: 14,
                },
            } as FeaturePlanningChange;

            mutations.addToBePlannedElement(state, to_be_planned_element);
            expect(state.to_be_planned_elements).toHaveLength(2);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 14 });
            expect(state.to_be_planned_elements[1]).toEqual({ id: 125 });
        });

        it("When element does not exist in state, Then it is added before sibling", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }] as Feature[],
            } as State;

            const to_be_planned_element = {
                feature: { id: 125 } as Feature,
                order: {
                    feature: { id: 125 } as Feature,
                    direction: BEFORE,
                    compared_to: 14,
                },
            } as FeaturePlanningChange;

            mutations.addToBePlannedElement(state, to_be_planned_element);
            expect(state.to_be_planned_elements).toHaveLength(2);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 125 });
            expect(state.to_be_planned_elements[1]).toEqual({ id: 14 });
        });

        it("When element does not exist in state and no elements are planned, Then it is added", () => {
            const state = {
                to_be_planned_elements: [] as Feature[],
            } as State;

            const to_be_planned_element = {
                feature: { id: 125 } as Feature,
                order: null,
            } as FeaturePlanningChange;

            mutations.addToBePlannedElement(state, to_be_planned_element);
            expect(state.to_be_planned_elements).toHaveLength(1);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 125 });
        });
    });

    describe("removeToBePlannedElement", () => {
        it("When feature exist, Then it is deleted from state", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }, { id: 125 }] as Feature[],
            } as State;

            const element_to_remove = {
                id: 125,
            } as Feature;

            mutations.removeToBePlannedElement(state, element_to_remove);
            expect(state.to_be_planned_elements).toHaveLength(1);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 14 });
        });

        it("When feature does not exist, Then it is not deleted", () => {
            const state = {
                to_be_planned_elements: [{ id: 14 }, { id: 125 }] as Feature[],
            } as State;

            const element_to_remove = {
                id: 536,
            } as Feature;

            mutations.removeToBePlannedElement(state, element_to_remove);
            expect(state.to_be_planned_elements).toHaveLength(2);
            expect(state.to_be_planned_elements[0]).toEqual({ id: 14 });
            expect(state.to_be_planned_elements[1]).toEqual({ id: 125 });
        });
    });

    describe("startMoveElementInAProgramIncrement", () => {
        it("When the element is not already moving, Then it is added in state", () => {
            const state = {
                ongoing_move_elements_id: [14],
            } as State;

            mutations.startMoveElementInAProgramIncrement(state, 536);
            expect(state.ongoing_move_elements_id).toHaveLength(2);
            expect(state.ongoing_move_elements_id[0]).toBe(14);
            expect(state.ongoing_move_elements_id[1]).toBe(536);
        });

        it("When the element is already moving, Then error is thrown", () => {
            const state = {
                ongoing_move_elements_id: [536],
            } as State;

            expect(() => mutations.startMoveElementInAProgramIncrement(state, 536)).toThrowError(
                "Program element #536 is already moving",
            );
        });
    });

    describe("finishMoveElement", () => {
        it("When element is moving, Then it is deleted from state", () => {
            const state = {
                ongoing_move_elements_id: [536, 537],
            } as State;

            mutations.finishMoveElement(state, 536);
            expect(state.ongoing_move_elements_id).toHaveLength(1);
            expect(state.ongoing_move_elements_id[0]).toBe(537);
        });

        it("When element is not moving, Then it is not deleted", () => {
            const state = {
                ongoing_move_elements_id: [536, 537],
            } as State;

            mutations.finishMoveElement(state, 14);
            expect(state.ongoing_move_elements_id).toHaveLength(2);
            expect(state.ongoing_move_elements_id[0]).toBe(536);
            expect(state.ongoing_move_elements_id[1]).toBe(537);
        });
    });

    describe("linkUserStoriesToFeature", () => {
        it("When program increment does not exist, Then error is thrown", () => {
            const state = {
                program_increments: [] as ProgramIncrement[],
            } as State;

            const link: LinkUserStoryToFeature = {
                program_increment: { id: 14 } as ProgramIncrement,
                element_id: 101,
                user_stories: [],
            };

            expect(() => mutations.linkUserStoriesToFeature(state, link)).toThrowError(
                "Program increment with id #14 does not exist",
            );
        });

        it("When program increment and feature exist, Then stories are added", () => {
            const state = {
                program_increments: [
                    {
                        id: 14,
                        features: [{ id: 101, user_stories: [] as UserStory[] }] as Feature[],
                    } as ProgramIncrement,
                ],
            } as State;

            const link: LinkUserStoryToFeature = {
                program_increment: { id: 14 } as ProgramIncrement,
                element_id: 101,
                user_stories: [{ id: 18 } as UserStory],
            };

            mutations.linkUserStoriesToFeature(state, link);
            expect(state.program_increments[0].features[0].user_stories).toEqual([{ id: 18 }]);
        });
    });

    describe("linkUserStoriesToBePlannedElement", () => {
        it("When feature does not exist, Then error is thrown", () => {
            const state = {
                to_be_planned_elements: [] as Feature[],
            } as State;

            const link: LinkUserStoryToPlannedElement = {
                element_id: 101,
                user_stories: [],
            };

            expect(() => mutations.linkUserStoriesToBePlannedElement(state, link)).toThrowError(
                "To be planned element with id #101 does not exist",
            );
        });

        it("When feature exists, Then stories are added", () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                        user_stories: [] as UserStory[],
                    } as Feature,
                ] as Feature[],
            } as State;

            const link: LinkUserStoryToPlannedElement = {
                element_id: 101,
                user_stories: [{ id: 18 } as UserStory],
            };

            mutations.linkUserStoriesToBePlannedElement(state, link);
            expect(state.to_be_planned_elements[0].user_stories).toEqual([{ id: 18 }]);
        });
    });

    describe("changeFeaturePositionInProgramBacklog", () => {
        it("When sibling does not exist, Then nothing happens", () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    } as Feature,
                ] as Feature[],
            } as State;

            mutations.changeFeaturePositionInProgramBacklog(state, {
                order: {
                    compared_to: 666,
                    direction: AFTER,
                },
                feature: { id: 101 } as Feature,
            });

            expect(state.to_be_planned_elements).toEqual([{ id: 101 }]);
        });

        it("When direction is after, Then feature is moving after sibling", () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    } as Feature,
                    {
                        id: 102,
                    } as Feature,
                ] as Feature[],
            } as State;

            mutations.changeFeaturePositionInProgramBacklog(state, {
                order: {
                    compared_to: 102,
                    direction: AFTER,
                },
                feature: { id: 101 } as Feature,
            });

            expect(state.to_be_planned_elements).toEqual([{ id: 102 }, { id: 101 }]);
        });

        it("When direction is before, Then feature is moving before sibling", () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    } as Feature,
                    {
                        id: 102,
                    } as Feature,
                ] as Feature[],
            } as State;

            mutations.changeFeaturePositionInProgramBacklog(state, {
                order: {
                    compared_to: 101,
                    direction: BEFORE,
                },
                feature: { id: 102 } as Feature,
            });

            expect(state.to_be_planned_elements).toEqual([{ id: 102 }, { id: 101 }]);
        });

        it("When order is null, Then error is thrown", () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    } as Feature,
                    {
                        id: 102,
                    } as Feature,
                ] as Feature[],
            } as State;

            expect(() =>
                mutations.changeFeaturePositionInProgramBacklog(state, {
                    order: null,
                    feature: { id: 102 } as Feature,
                }),
            ).toThrowError("No order exists in feature position");
        });
    });

    describe("moveFeatureFromBacklogToProgramIncrement", () => {
        it(`When feature is moving from backlog to Program Increment without order, Then feature is added to program increment`, () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    } as Feature,
                    {
                        id: 102,
                    } as Feature,
                ] as Feature[],
                program_increments: [{ id: 1, features: [] as Feature[] }] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromBacklogToProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                to_program_increment_id: 1,
                order: null,
            });

            expect(state).toEqual({
                to_be_planned_elements: [
                    {
                        id: 102,
                    } as Feature,
                ] as Feature[],
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });

        it(`When feature is moving from backlog to Program Increment with direction is before, Then feature is added to program increment`, () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    },
                ] as Feature[],
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 102,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromBacklogToProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                to_program_increment_id: 1,
                order: { direction: BEFORE, compared_to: 102 },
            });

            expect(state).toEqual({
                to_be_planned_elements: [] as Feature[],
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 101,
                            },
                            {
                                id: 102,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });
        it(`When feature is moving from backlog to Program Increment with direction is after, Then feature is added to program increment`, () => {
            const state = {
                to_be_planned_elements: [
                    {
                        id: 101,
                    },
                ] as Feature[],
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 102,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromBacklogToProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                to_program_increment_id: 1,
                order: { direction: AFTER, compared_to: 102 },
            });

            expect(state).toEqual({
                to_be_planned_elements: [] as Feature[],
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 102,
                            },
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });
    });
    describe("moveFeatureFromProgramIncrementToAnotherProgramIncrement", () => {
        it(`When feature is moving from increment to another increment without order, Then feature is unplanned and planned`, () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [] as Feature[],
                    },
                    {
                        id: 666,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromProgramIncrementToAnotherProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                from_program_increment_id: 666,
                to_program_increment_id: 1,
                order: null,
            });

            expect(state).toEqual({
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                    {
                        id: 666,
                        features: [] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });

        it(`When feature is moving from increment to another increment with order is before, Then feature is unplanned and planned`, () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 106,
                            },
                        ] as Feature[],
                    },
                    {
                        id: 666,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromProgramIncrementToAnotherProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                from_program_increment_id: 666,
                to_program_increment_id: 1,
                order: { compared_to: 106, direction: BEFORE },
            });

            expect(state).toEqual({
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 101,
                            },
                            {
                                id: 106,
                            },
                        ] as Feature[],
                    },
                    {
                        id: 666,
                        features: [] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });

        it(`When feature is moving from increment to another increment with order is after, Then feature is unplanned and planned`, () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 106,
                            },
                        ] as Feature[],
                    },
                    {
                        id: 666,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.moveFeatureFromProgramIncrementToAnotherProgramIncrement(state, {
                feature: { id: 101 } as Feature,
                from_program_increment_id: 666,
                to_program_increment_id: 1,
                order: { compared_to: 106, direction: AFTER },
            });

            expect(state).toEqual({
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 106,
                            },
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                    {
                        id: 666,
                        features: [] as Feature[],
                    },
                ] as ProgramIncrement[],
            });
        });
    });
    describe("removeFeatureFromProgramIncrement", () => {
        it("When feature exists, Then it is removed from program increment", () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [
                            {
                                id: 101,
                            },
                        ] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.removeFeatureFromProgramIncrement(state, {
                program_increment_id: 1,
                feature_id: 101,
            });

            expect(state.program_increments[0].features).toEqual([]);
        });
    });

    describe("changeFeaturePositionInSameProgramIncrement", () => {
        it("When order does not exist, Then error is thrown", () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [{ id: 66 }] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            expect(() =>
                mutations.changeFeaturePositionInSameProgramIncrement(state, {
                    to_program_increment_id: 1,
                    feature: { id: 66 } as Feature,
                    order: null,
                }),
            ).toThrowError("No order exists in feature position");
        });

        it("When sibling does not exist, Then nothing happens", () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [{ id: 66 }] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.changeFeaturePositionInSameProgramIncrement(state, {
                to_program_increment_id: 1,
                feature: { id: 66 } as Feature,
                order: { compared_to: 9999, direction: BEFORE },
            });

            expect(state.program_increments[0].features).toEqual([{ id: 66 }]);
        });

        it("When direction is before, Then feature is moved before sibling", () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [{ id: 14 }, { id: 666 }, { id: 569 }] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.changeFeaturePositionInSameProgramIncrement(state, {
                to_program_increment_id: 1,
                feature: { id: 569 } as Feature,
                order: { compared_to: 14, direction: BEFORE },
            });

            expect(state.program_increments[0].features).toEqual([
                { id: 569 },
                { id: 14 },
                { id: 666 },
            ]);
        });
        it("When direction is after, Then feature is moved after sibling", () => {
            const state = {
                program_increments: [
                    {
                        id: 1,
                        features: [{ id: 14 }, { id: 666 }, { id: 569 }] as Feature[],
                    },
                ] as ProgramIncrement[],
            } as State;

            mutations.changeFeaturePositionInSameProgramIncrement(state, {
                to_program_increment_id: 1,
                feature: { id: 14 } as Feature,
                order: { compared_to: 569, direction: AFTER },
            });

            expect(state.program_increments[0].features).toEqual([
                { id: 666 },
                { id: 569 },
                { id: 14 },
            ]);
        });
    });
});

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
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import { extractFeatureIndexFromProgramIncrement } from "../helpers/feature-extractor";
import type { FeatureIdWithProgramIncrement } from "../helpers/drag-drop";
import type { UserStory } from "../helpers/BacklogItems/children-feature-retriever";

export interface LinkUserStoryToPlannedElement {
    element_id: number;
    user_stories: UserStory[];
}

export interface LinkUserStoryToFeature extends LinkUserStoryToPlannedElement {
    program_increment: ProgramIncrement;
}

export default {
    addProgramIncrement(state: State, program_increment: ProgramIncrement): void {
        const existing_increment = state.program_increments.find(
            (existing_increment) => existing_increment.id === program_increment.id
        );

        if (existing_increment !== undefined) {
            throw Error("Program increment with id #" + program_increment.id + " already exists");
        }

        state.program_increments.push(program_increment);
    },

    setToBePlannedElements(state: State, to_be_planned_elements: Feature[]): void {
        state.to_be_planned_elements = to_be_planned_elements;
    },

    addToBePlannedElement(state: State, to_be_planned_elements: Feature): void {
        const element_already_exist = state.to_be_planned_elements.find(
            (element) => element.id === to_be_planned_elements.id
        );

        if (element_already_exist !== undefined) {
            throw Error(
                "To be planned element with id #" + to_be_planned_elements.id + " already exist"
            );
        }

        state.to_be_planned_elements.push(to_be_planned_elements);
    },

    removeToBePlannedElement(state: State, element_to_remove: Feature): void {
        state.to_be_planned_elements = [...state.to_be_planned_elements].filter(
            (to_be_planned_element) => to_be_planned_element.id !== element_to_remove.id
        );
    },

    setModalErrorMessage(state: State, message: string): void {
        state.modal_error_message = message;
        state.has_modal_error = true;
    },

    startMoveElementInAProgramIncrement(state: State, program_element_id: number): void {
        if (state.ongoing_move_elements_id.indexOf(program_element_id) !== -1) {
            throw Error("Program element #" + program_element_id + " is already moving");
        }

        state.ongoing_move_elements_id.push(program_element_id);
    },

    finishMoveElement(state: State, ongoing_move_elements_id_id: number): void {
        state.ongoing_move_elements_id = [...state.ongoing_move_elements_id].filter(
            (element_id) => element_id !== ongoing_move_elements_id_id
        );
    },

    linkUserStoriesToFeature(state: State, user_story_feature: LinkUserStoryToFeature): void {
        const existing_increment = state.program_increments.find(
            (existing_increment) =>
                existing_increment.id === user_story_feature.program_increment.id
        );

        if (existing_increment === undefined) {
            throw Error(
                "Program increment with id #" +
                    user_story_feature.program_increment.id +
                    " does not exist"
            );
        }

        const payload: FeatureIdWithProgramIncrement = {
            feature_id: user_story_feature.element_id,
            program_increment: existing_increment,
        };

        const feature_index = extractFeatureIndexFromProgramIncrement(payload);

        existing_increment.features[feature_index].user_stories = user_story_feature.user_stories;
    },

    linkUserStoriesToBePlannedElement(
        state: State,
        user_story_element: LinkUserStoryToPlannedElement
    ): void {
        const element = state.to_be_planned_elements.find(
            (element) => element.id === user_story_element.element_id
        );

        if (element === undefined) {
            throw Error(
                "To be planned element with id #" +
                    user_story_element.element_id +
                    " does not exist"
            );
        }

        element.user_stories = user_story_element.user_stories;
    },
};

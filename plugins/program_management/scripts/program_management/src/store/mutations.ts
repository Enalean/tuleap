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
import type { UserStory } from "../helpers/UserStories/user-stories-retriever";
import type {
    FeaturePlanningChange,
    FeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement,
    FeaturePlanningChangeInProgramIncrement,
    FeatureReorderPosition,
} from "../helpers/feature-reordering";
import { AFTER } from "../helpers/feature-reordering";
import { getProgramIncrementFromId, getFeaturesInProgramIncrement } from "./getters";

export interface LinkUserStoryToPlannedElement {
    element_id: number;
    user_stories: UserStory[];
}

export interface LinkUserStoryToFeature extends LinkUserStoryToPlannedElement {
    program_increment: ProgramIncrement;
}

export interface FeatureIdWithProgramIncrementId {
    feature_id: number;
    program_increment_id: number;
}

export function addProgramIncrement(state: State, program_increment: ProgramIncrement): void {
    const existing_increment = state.program_increments.find(
        (existing_increment) => existing_increment.id === program_increment.id,
    );

    if (existing_increment !== undefined) {
        throw Error("Program increment with id #" + program_increment.id + " already exists");
    }

    state.program_increments.push(program_increment);
}

export function setToBePlannedElements(state: State, to_be_planned_elements: Feature[]): void {
    state.to_be_planned_elements = to_be_planned_elements;
}

export function addToBePlannedElement(state: State, feature_moving: FeaturePlanningChange): void {
    const element_already_exist = state.to_be_planned_elements.find(
        (element) => element.id === feature_moving.feature.id,
    );

    if (element_already_exist !== undefined) {
        return;
    }

    if (feature_moving.order) {
        orderFeatureInProgramBacklog(state, feature_moving.order, feature_moving.feature);
        return;
    }

    state.to_be_planned_elements.push(feature_moving.feature);
}

export function removeToBePlannedElement(state: State, element_to_remove: Feature): void {
    state.to_be_planned_elements = getToBePlannedElementWithoutFeature(state, element_to_remove);
}

export function setModalErrorMessage(state: State, message: string): void {
    state.modal_error_message = message;
    state.has_modal_error = true;
}

export function startMoveElementInAProgramIncrement(
    state: State,
    program_element_id: number,
): void {
    if (state.ongoing_move_elements_id.indexOf(program_element_id) !== -1) {
        throw Error("Program element #" + program_element_id + " is already moving");
    }

    state.ongoing_move_elements_id.push(program_element_id);
}

export function finishMoveElement(state: State, ongoing_move_elements_id_id: number): void {
    state.ongoing_move_elements_id = [...state.ongoing_move_elements_id].filter(
        (element_id) => element_id !== ongoing_move_elements_id_id,
    );
}

export function linkUserStoriesToFeature(
    state: State,
    user_story_feature: LinkUserStoryToFeature,
): void {
    const existing_increment = state.program_increments.find(
        (existing_increment) => existing_increment.id === user_story_feature.program_increment.id,
    );

    if (existing_increment === undefined) {
        throw Error(
            "Program increment with id #" +
                user_story_feature.program_increment.id +
                " does not exist",
        );
    }

    const payload: FeatureIdWithProgramIncrement = {
        feature_id: user_story_feature.element_id,
        program_increment: existing_increment,
    };

    const feature_index = extractFeatureIndexFromProgramIncrement(payload);

    existing_increment.features[feature_index].user_stories = user_story_feature.user_stories;
}

export function linkUserStoriesToBePlannedElement(
    state: State,
    user_story_element: LinkUserStoryToPlannedElement,
): void {
    const element = state.to_be_planned_elements.find(
        (element) => element.id === user_story_element.element_id,
    );

    if (element === undefined) {
        throw Error(
            "To be planned element with id #" + user_story_element.element_id + " does not exist",
        );
    }

    element.user_stories = user_story_element.user_stories;
}

export function changeFeaturePositionInProgramBacklog(
    state: State,
    feature_planning_change: FeaturePlanningChange,
): void {
    if (!feature_planning_change.order) {
        throw Error("No order exists in feature position");
    }
    orderFeatureInProgramBacklog(
        state,
        feature_planning_change.order,
        feature_planning_change.feature,
    );
}

function orderFeatureInProgramBacklog(
    state: State,
    reorder_position: FeatureReorderPosition,
    feature: Feature,
): void {
    const sibling_index = getToBePlannedElementWithoutFeature(state, feature).findIndex(
        (feature) => feature.id === reorder_position.compared_to,
    );

    if (sibling_index === -1) {
        return;
    }

    removeToBePlannedElement(state, feature);

    const offset = reorder_position.direction === AFTER ? 1 : 0;
    state.to_be_planned_elements.splice(sibling_index + offset, 0, feature);
}

export function changeFeaturePositionInSameProgramIncrement(
    state: State,
    feature_planning_change: FeaturePlanningChangeInProgramIncrement,
): void {
    if (!feature_planning_change.order) {
        throw Error("No order exists in feature position");
    }
    orderFeatureInProgramIncrement(
        state,
        feature_planning_change.order,
        feature_planning_change.feature,
        feature_planning_change.to_program_increment_id,
        true,
    );
}

function orderFeatureInProgramIncrement(
    state: State,
    reorder_position: FeatureReorderPosition,
    feature: Feature,
    program_increment_id: number,
    order_in_same_program_increment: boolean,
): void {
    const sibling_index = getAllFeaturesInProgramIncrementWithoutFeature(
        state,
        feature,
        program_increment_id,
    ).findIndex((feature) => feature.id === reorder_position.compared_to);

    if (sibling_index === -1) {
        return;
    }
    if (order_in_same_program_increment) {
        removeFeatureFromProgramIncrement(state, { program_increment_id, feature_id: feature.id });
    }

    const offset = reorder_position.direction === AFTER ? 1 : 0;
    getFeaturesInProgramIncrement(state)(program_increment_id).splice(
        sibling_index + offset,
        0,
        feature,
    );
}

function getToBePlannedElementWithoutFeature(state: State, element_to_remove: Feature): Feature[] {
    return state.to_be_planned_elements.filter(
        (to_be_planned_element) => to_be_planned_element.id !== element_to_remove.id,
    );
}

function getAllFeaturesInProgramIncrementWithoutFeature(
    state: State,
    element_to_remove: Feature,
    program_increment_id: number,
): Feature[] {
    const all_features = getFeaturesInProgramIncrement(state)(program_increment_id);
    return all_features.filter((feature) => feature.id !== element_to_remove.id);
}

export function moveFeatureFromBacklogToProgramIncrement(
    state: State,
    feature_order: FeaturePlanningChangeInProgramIncrement,
): void {
    removeToBePlannedElement(state, feature_order.feature);
    if (!feature_order.order) {
        const program_increment = getProgramIncrementFromId(state)(
            feature_order.to_program_increment_id,
        );
        program_increment.features.push(feature_order.feature);
        return;
    }

    orderFeatureInProgramIncrement(
        state,
        feature_order.order,
        feature_order.feature,
        feature_order.to_program_increment_id,
        false,
    );
}

export function moveFeatureFromProgramIncrementToAnotherProgramIncrement(
    state: State,
    feature_order: FeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement,
): void {
    const from_program_increment = getProgramIncrementFromId(state)(
        feature_order.from_program_increment_id,
    );

    const feature_to_move_index = extractFeatureIndexFromProgramIncrement({
        feature_id: feature_order.feature.id,
        program_increment: from_program_increment,
    });

    from_program_increment.features.splice(feature_to_move_index, 1);

    if (!feature_order.order) {
        const program_increment = getProgramIncrementFromId(state)(
            feature_order.to_program_increment_id,
        );
        program_increment.features.push(feature_order.feature);
        return;
    }

    orderFeatureInProgramIncrement(
        state,
        feature_order.order,
        feature_order.feature,
        feature_order.to_program_increment_id,
        false,
    );
}

export function removeFeatureFromProgramIncrement(
    state: State,
    feature_id_with_program_increment_id: FeatureIdWithProgramIncrementId,
): void {
    const program_increment = getProgramIncrementFromId(state)(
        feature_id_with_program_increment_id.program_increment_id,
    );

    const feature_to_unplan_index = extractFeatureIndexFromProgramIncrement({
        feature_id: feature_id_with_program_increment_id.feature_id,
        program_increment,
    });

    program_increment.features.splice(feature_to_unplan_index, 1);
}

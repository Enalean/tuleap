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

import type { ActionContext } from "vuex";
import type { Feature, State } from "../type";
import type {
    FeatureIdToMoveFromProgramIncrementToAnother,
    FeatureIdWithProgramIncrement,
    HandleDropContextWithProgramId,
} from "../helpers/drag-drop";
import { extractFeatureIndexFromProgramIncrement } from "../helpers/feature-extractor";
import { addElementToTopBackLog } from "../helpers/ProgramIncrement/add-to-top-backlog";
import { unplanFeature, planFeatureInProgramIncrement as planFeature } from "../helpers/drag-drop";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import { getToBePlannedElements } from "../helpers/ToBePlanned/element-to-plan-retriever";
import { getFeatures } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { UserStory } from "../helpers/UserStories/user-stories-retriever";
import { getLinkedUserStoriesToFeature } from "../helpers/UserStories/user-stories-retriever";

export interface LinkUserStoriesToFeature {
    artifact_id: number;
    program_increment: ProgramIncrement;
}

export function planFeatureInProgramIncrement(
    context: ActionContext<State, State>,
    feature_id_with_increment: FeatureIdWithProgramIncrement
): void {
    const to_be_planned_element = context.getters.getToBePlannedElementFromId(
        feature_id_with_increment.feature_id
    );

    context.commit("removeToBePlannedElement", to_be_planned_element);

    feature_id_with_increment.program_increment.features.push(to_be_planned_element);
}

export function unplanFeatureFromProgramIncrement(
    context: ActionContext<State, State>,
    feature_id_with_increment: FeatureIdWithProgramIncrement
): void {
    const feature_to_unplan_index = extractFeatureIndexFromProgramIncrement(
        feature_id_with_increment
    );

    const feature_to_unplan =
        feature_id_with_increment.program_increment.features[feature_to_unplan_index];

    feature_id_with_increment.program_increment.features.splice(feature_to_unplan_index, 1);

    context.commit("addToBePlannedElement", feature_to_unplan);
}

export function moveFeatureFromProgramIncrementToAnother(
    context: ActionContext<State, State>,
    feature_to_move_id: FeatureIdToMoveFromProgramIncrementToAnother
): void {
    const feature_to_move_index = extractFeatureIndexFromProgramIncrement({
        feature_id: feature_to_move_id.feature_id,
        program_increment: feature_to_move_id.from_program_increment,
    });

    const feature_to_move =
        feature_to_move_id.from_program_increment.features[feature_to_move_index];

    feature_to_move_id.from_program_increment.features.splice(feature_to_move_index, 1);
    feature_to_move_id.to_program_increment.features.push(feature_to_move);
}

export async function handleDrop(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId
): Promise<void> {
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    const element_id = parseInt(data_element_id, 10);

    const plan_in_program_increment_id = handle_drop.target_dropzone.dataset.programIncrementId;
    const remove_from_program_increment_id = handle_drop.dropped_element.dataset.programIncrementId;

    if (plan_in_program_increment_id && !remove_from_program_increment_id) {
        const payload: FeatureIdWithProgramIncrement = {
            feature_id: element_id,
            program_increment: context.getters.getProgramIncrementFromId(
                parseInt(plan_in_program_increment_id, 10)
            ),
        };

        planFeatureInProgramIncrement(context, payload);

        try {
            context.commit("startMoveElementInAProgramIncrement", element_id);
            await planFeature(handle_drop, parseInt(plan_in_program_increment_id, 10), element_id);
        } catch (error) {
            await handleModalError(context, error);
        } finally {
            context.commit("finishMoveElement", element_id);
        }
    }

    if (!plan_in_program_increment_id && remove_from_program_increment_id) {
        const payload: FeatureIdWithProgramIncrement = {
            feature_id: element_id,
            program_increment: context.getters.getProgramIncrementFromId(
                parseInt(remove_from_program_increment_id, 10)
            ),
        };

        unplanFeatureFromProgramIncrement(context, payload);

        try {
            context.commit("startMoveElementInAProgramIncrement", element_id);
            await unplanFeature(
                handle_drop,
                parseInt(remove_from_program_increment_id, 10),
                element_id
            );

            await addElementToTopBackLog(handle_drop.program_id, element_id);
        } catch (error) {
            await handleModalError(context, error);
        } finally {
            context.commit("finishMoveElement", element_id);
        }
    }

    if (plan_in_program_increment_id && remove_from_program_increment_id) {
        const payload: FeatureIdToMoveFromProgramIncrementToAnother = {
            feature_id: element_id,
            from_program_increment: context.getters.getProgramIncrementFromId(
                parseInt(remove_from_program_increment_id, 10)
            ),
            to_program_increment: context.getters.getProgramIncrementFromId(
                parseInt(plan_in_program_increment_id, 10)
            ),
        };

        moveFeatureFromProgramIncrementToAnother(context, payload);

        try {
            context.commit("startMoveElementInAProgramIncrement", element_id);

            await Promise.all([
                unplanFeature(
                    handle_drop,
                    parseInt(remove_from_program_increment_id, 10),
                    element_id
                ),
                planFeature(handle_drop, parseInt(plan_in_program_increment_id, 10), element_id),
            ]);
        } catch (error) {
            await handleModalError(context, error);
        } finally {
            context.commit("finishMoveElement", element_id);
        }
    }
}

export async function handleModalError(
    context: ActionContext<State, State>,
    rest_error: FetchWrapperError
): Promise<void> {
    try {
        const { error } = await rest_error.response.json();
        context.commit("setModalErrorMessage", error.code + " " + error.message);
    } catch (e) {
        context.commit("setModalErrorMessage", "");
    }
}

export async function linkUserStoriesToBePlannedElements(
    context: ActionContext<State, State>,
    artifact_id: number
): Promise<UserStory[]> {
    const user_stories = await getLinkedUserStoriesToFeature(artifact_id);
    context.commit("linkUserStoriesToBePlannedElement", {
        user_stories: user_stories,
        element_id: artifact_id,
    });

    return user_stories;
}

export async function linkUserStoriesToFeature(
    context: ActionContext<State, State>,
    link_user_stories_to_feature: LinkUserStoriesToFeature
): Promise<UserStory[]> {
    const user_stories = await getLinkedUserStoriesToFeature(
        link_user_stories_to_feature.artifact_id
    );
    context.commit("linkUserStoriesToFeature", {
        user_stories: user_stories,
        element_id: link_user_stories_to_feature.artifact_id,
        program_increment: link_user_stories_to_feature.program_increment,
    });

    return user_stories;
}

export async function retrieveToBePlannedElement(
    context: ActionContext<State, State>,
    program_id: number
): Promise<void> {
    const to_be_planned_elements = await getToBePlannedElements(program_id);
    context.commit("setToBePlannedElements", to_be_planned_elements);
}

export async function getFeatureAndStoreInProgramIncrement(
    context: ActionContext<State, State>,
    program_increment: ProgramIncrement
): Promise<Feature[]> {
    const features = await getFeatures(program_increment.id);
    context.commit("addProgramIncrement", { ...program_increment, features });
    return features;
}

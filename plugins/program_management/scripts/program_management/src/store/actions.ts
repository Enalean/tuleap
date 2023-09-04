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
import type { HandleDropContextWithProgramId } from "../helpers/drag-drop";
import type { ProgramIncrement } from "../helpers/ProgramIncrement/program-increment-retriever";
import { getToBePlannedElements } from "../helpers/ToBePlanned/element-to-plan-retriever";
import { getFeatures } from "../helpers/ProgramIncrement/Feature/feature-retriever";
import type { UserStory } from "../helpers/UserStories/user-stories-retriever";
import { getLinkedUserStoriesToFeature } from "../helpers/UserStories/user-stories-retriever";
import {
    reorderFeatureInProgramBacklog,
    reorderFeatureInSameProgramIncrement,
} from "../helpers/feature-reordering";
import {
    moveFeatureFromBacklogToProgramIncrement,
    moveFeatureFromProgramIncrementToAnotherProgramIncrement,
    moveFeatureFromProgramIncrementToBacklog,
} from "../helpers/feature-moving";

export interface LinkUserStoriesToFeature {
    artifact_id: number;
    program_increment: ProgramIncrement;
}

export async function handleDrop(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId,
): Promise<void> {
    const plan_in_program_increment_id = handle_drop.target_dropzone.dataset.programIncrementId;
    const remove_from_program_increment_id = handle_drop.dropped_element.dataset.programIncrementId;

    if (plan_in_program_increment_id && !remove_from_program_increment_id) {
        await moveFeatureFromBacklogToProgramIncrement(
            context,
            handle_drop,
            parseInt(plan_in_program_increment_id, 10),
        );
        return;
    }

    if (!plan_in_program_increment_id && remove_from_program_increment_id) {
        await moveFeatureFromProgramIncrementToBacklog(
            context,
            handle_drop,
            parseInt(remove_from_program_increment_id, 10),
        );
        return;
    }

    if (
        plan_in_program_increment_id &&
        remove_from_program_increment_id &&
        plan_in_program_increment_id !== remove_from_program_increment_id
    ) {
        await moveFeatureFromProgramIncrementToAnotherProgramIncrement(
            context,
            handle_drop,
            parseInt(plan_in_program_increment_id, 10),
            parseInt(remove_from_program_increment_id, 10),
        );
        return;
    }

    if (
        plan_in_program_increment_id &&
        remove_from_program_increment_id &&
        plan_in_program_increment_id === remove_from_program_increment_id
    ) {
        await reorderFeatureInSameProgramIncrement(
            context,
            handle_drop,
            parseInt(plan_in_program_increment_id, 10),
        );

        return;
    }

    if (!plan_in_program_increment_id && !remove_from_program_increment_id) {
        await reorderFeatureInProgramBacklog(context, handle_drop);
    }
}

export async function linkUserStoriesToBePlannedElements(
    context: ActionContext<State, State>,
    artifact_id: number,
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
    link_user_stories_to_feature: LinkUserStoriesToFeature,
): Promise<UserStory[]> {
    const user_stories = await getLinkedUserStoriesToFeature(
        link_user_stories_to_feature.artifact_id,
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
    program_id: number,
): Promise<void> {
    const to_be_planned_elements = await getToBePlannedElements(program_id);
    context.commit("setToBePlannedElements", to_be_planned_elements);
}

export async function getFeatureAndStoreInProgramIncrement(
    context: ActionContext<State, State>,
    program_increment: ProgramIncrement,
): Promise<Feature[]> {
    const features = await getFeatures(program_increment.id);
    context.commit("addProgramIncrement", { ...program_increment, features });
    return features;
}

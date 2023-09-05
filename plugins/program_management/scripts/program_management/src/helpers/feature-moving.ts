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
import type { State } from "../type";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import { moveElementFromProgramIncrementToTopBackLog } from "./ProgramIncrement/add-to-top-backlog";
import { handleModalError } from "./error-handler";
import {
    getFeaturePlanningChange,
    getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement,
    getFeaturePlanningChangeInProgramIncrement,
} from "./feature-reordering";
import { planElementInProgramIncrement } from "./ProgramIncrement/Feature/feature-planner";

export async function moveFeatureFromBacklogToProgramIncrement(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId,
    plan_in_program_increment_id: number,
): Promise<void> {
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    const element_id = parseInt(data_element_id, 10);

    let sibling_feature = null;
    if (handle_drop.next_sibling instanceof HTMLElement) {
        sibling_feature = context.getters.getSiblingFeatureInProgramIncrement({
            sibling: handle_drop.next_sibling,
            program_increment_id: plan_in_program_increment_id,
        });
    }

    const feature_planning_change = getFeaturePlanningChangeInProgramIncrement(
        context.getters.getToBePlannedElementFromId(element_id),
        sibling_feature,
        context.getters.getFeaturesInProgramIncrement(plan_in_program_increment_id),
        plan_in_program_increment_id,
    );

    context.commit("moveFeatureFromBacklogToProgramIncrement", feature_planning_change);

    try {
        context.commit("startMoveElementInAProgramIncrement", element_id);
        await planElementInProgramIncrement(feature_planning_change);
    } catch (error) {
        await handleModalError(context, error);
    } finally {
        context.commit("finishMoveElement", element_id);
    }
}

export async function moveFeatureFromProgramIncrementToBacklog(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId,
    remove_from_program_increment_id: number,
): Promise<void> {
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    const element_id = parseInt(data_element_id, 10);
    let sibling_feature = null;
    if (handle_drop.next_sibling instanceof HTMLElement) {
        sibling_feature = context.getters.getSiblingFeatureFromProgramBacklog(
            handle_drop.next_sibling,
        );
    }

    const feature_to_unplan = context.getters.getFeatureInProgramIncrement({
        feature_id: element_id,
        program_increment_id: remove_from_program_increment_id,
    });

    context.commit("removeFeatureFromProgramIncrement", {
        feature_id: element_id,
        program_increment_id: remove_from_program_increment_id,
    });

    const feature_planning_change = getFeaturePlanningChange(
        feature_to_unplan,
        sibling_feature,
        context.state.to_be_planned_elements,
    );

    context.commit("addToBePlannedElement", feature_planning_change);

    try {
        context.commit("startMoveElementInAProgramIncrement", element_id);
        await moveElementFromProgramIncrementToTopBackLog(
            handle_drop.program_id,
            feature_planning_change,
        );
    } catch (error) {
        await handleModalError(context, error);
    } finally {
        context.commit("finishMoveElement", element_id);
    }
}

export async function moveFeatureFromProgramIncrementToAnotherProgramIncrement(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId,
    plan_in_program_increment_id: number,
    remove_from_program_increment_id: number,
): Promise<void> {
    if (plan_in_program_increment_id === remove_from_program_increment_id) {
        return;
    }
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    const element_id = parseInt(data_element_id, 10);

    let sibling_feature = null;
    if (handle_drop.next_sibling instanceof HTMLElement) {
        sibling_feature = context.getters.getSiblingFeatureInProgramIncrement({
            sibling: handle_drop.next_sibling,
            program_increment_id: plan_in_program_increment_id,
        });
    }

    const feature_planning_change =
        getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
            context.getters.getFeatureInProgramIncrement({
                feature_id: element_id,
                program_increment_id: remove_from_program_increment_id,
            }),
            sibling_feature,
            context.getters.getFeaturesInProgramIncrement(plan_in_program_increment_id),
            remove_from_program_increment_id,
            plan_in_program_increment_id,
        );

    context.commit(
        "moveFeatureFromProgramIncrementToAnotherProgramIncrement",
        feature_planning_change,
    );

    try {
        context.commit("startMoveElementInAProgramIncrement", element_id);

        await planElementInProgramIncrement(feature_planning_change);
    } catch (error) {
        await handleModalError(context, error);
    } finally {
        context.commit("finishMoveElement", element_id);
    }
}

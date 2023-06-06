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
import type { ActionContext } from "vuex";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import { reorderElementInTopBacklog } from "./ProgramIncrement/add-to-top-backlog";
import { handleModalError } from "./error-handler";
import { reorderElementInProgramIncrement } from "./ProgramIncrement/Feature/feature-planner";

export interface FeaturePlanningChange {
    readonly feature: Feature;
    readonly order: FeatureReorderPosition | null;
}

export interface FeaturePlanningChangeInProgramIncrement extends FeaturePlanningChange {
    to_program_increment_id: number;
}

export interface FeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement
    extends FeaturePlanningChangeInProgramIncrement {
    from_program_increment_id: number;
}

export interface FeatureReorderPosition {
    readonly direction: Direction;
    readonly compared_to: number;
}

export type Direction = "before" | "after";
export const BEFORE: Direction = "before";
export const AFTER: Direction = "after";

export interface SiblingFeatureHTMLElementWithProgramIncrement {
    sibling: HTMLElement;
    program_increment_id: number;
}

function getFeatureReorderPosition(
    sibling: Feature | null,
    features_in_program_backlog: Feature[]
): FeatureReorderPosition | null {
    if (!sibling) {
        if (features_in_program_backlog.length === 0) {
            return null;
        }

        const direction = AFTER;
        const last_feature_in_column =
            features_in_program_backlog[features_in_program_backlog.length - 1];
        const compared_to = last_feature_in_column.id;

        return { direction, compared_to };
    }

    return getFeatureToCompareWith(features_in_program_backlog, sibling);
}

export function getFeaturePlanningChange(
    feature: Feature,
    sibling: Feature | null,
    features_in_program_backlog: Feature[]
): FeaturePlanningChange {
    return {
        feature,
        order: getFeatureReorderPosition(sibling, features_in_program_backlog),
    };
}

export function getFeaturePlanningChangeInProgramIncrement(
    feature: Feature,
    sibling: Feature | null,
    features_in_program_backlog: Feature[],
    program_increment_id: number
): FeaturePlanningChangeInProgramIncrement {
    return {
        feature,
        order: getFeatureReorderPosition(sibling, features_in_program_backlog),
        to_program_increment_id: program_increment_id,
    };
}

export function getFeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement(
    feature: Feature,
    sibling: Feature | null,
    features_in_program_backlog: Feature[],
    from_program_increment_id: number,
    to_program_increment_id: number
): FeaturePlanningChangeFromProgramIncrementToAnotherProgramIncrement {
    return {
        feature,
        order: getFeatureReorderPosition(sibling, features_in_program_backlog),
        from_program_increment_id,
        to_program_increment_id,
    };
}

function getFeatureToCompareWith(
    features_to_compare: Feature[],
    sibling: Feature
): { direction: Direction; compared_to: number } {
    const index = features_to_compare.findIndex(
        (column_feature) => column_feature.id === sibling.id
    );

    if (index === -1) {
        throw new Error("Cannot find feature with id #" + sibling.id);
    }

    if (index === 0) {
        return {
            direction: BEFORE,
            compared_to: features_to_compare[0].id,
        };
    }

    return {
        direction: AFTER,
        compared_to: features_to_compare[index - 1].id,
    };
}

export async function reorderFeatureInProgramBacklog(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId
): Promise<void> {
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    let sibling_feature = null;
    if (handle_drop.next_sibling instanceof HTMLElement) {
        sibling_feature = context.getters.getSiblingFeatureFromProgramBacklog(
            handle_drop.next_sibling
        );
    }
    const element_id = parseInt(data_element_id, 10);
    const feature_planning_change = getFeaturePlanningChange(
        context.getters.getToBePlannedElementFromId(element_id),
        sibling_feature,
        context.state.to_be_planned_elements
    );

    context.commit("changeFeaturePositionInProgramBacklog", feature_planning_change);

    try {
        context.commit("startMoveElementInAProgramIncrement", element_id);

        await reorderElementInTopBacklog(handle_drop.program_id, feature_planning_change);
    } catch (error) {
        await handleModalError(context, error);
    } finally {
        context.commit("finishMoveElement", element_id);
    }
}

export async function reorderFeatureInSameProgramIncrement(
    context: ActionContext<State, State>,
    handle_drop: HandleDropContextWithProgramId,
    program_increment_id: number
): Promise<void> {
    const data_element_id = handle_drop.dropped_element.dataset.elementId;
    if (!data_element_id) {
        return;
    }
    let sibling_feature = null;
    if (handle_drop.next_sibling instanceof HTMLElement) {
        sibling_feature = context.getters.getSiblingFeatureInProgramIncrement({
            sibling: handle_drop.next_sibling,
            program_increment_id,
        });
    }

    const element_id = parseInt(data_element_id, 10);
    const feature_planning_change = getFeaturePlanningChangeInProgramIncrement(
        context.getters.getFeatureInProgramIncrement({
            feature_id: element_id,
            program_increment_id,
        }),
        sibling_feature,
        context.getters.getFeaturesInProgramIncrement(program_increment_id),
        program_increment_id
    );

    context.commit("changeFeaturePositionInSameProgramIncrement", feature_planning_change);

    try {
        context.commit("startMoveElementInAProgramIncrement", element_id);

        await reorderElementInProgramIncrement(feature_planning_change);
    } catch (error) {
        await handleModalError(context, error);
    } finally {
        context.commit("finishMoveElement", element_id);
    }
}

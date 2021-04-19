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

import type { Feature } from "../type";
import type { ActionContext } from "vuex";
import type { State } from "../type";
import type { HandleDropContextWithProgramId } from "./drag-drop";
import { reorderElementInTopBacklog } from "./ProgramIncrement/add-to-top-backlog";
import { handleModalError } from "./error-handler";

export interface FeaturePlanningChange {
    readonly feature: Feature;
    readonly order: FeatureReorderPosition | null;
}

export interface FeatureReorderPosition {
    readonly direction: Direction;
    readonly compared_to: number;
}

export enum Direction {
    BEFORE = "before",
    AFTER = "after",
}

function getFeatureReorderPosition(
    sibling: Feature | null,
    features_in_program_backlog: Feature[]
): FeatureReorderPosition | null {
    if (!sibling) {
        if (features_in_program_backlog.length === 0) {
            return null;
        }

        const direction = Direction.AFTER;
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

function getFeatureToCompareWith(
    features_in_program_backlog: Feature[],
    sibling: Feature
): { direction: Direction; compared_to: number } {
    const index = features_in_program_backlog.findIndex(
        (column_feature) => column_feature.id === sibling.id
    );

    if (index === -1) {
        throw new Error("Cannot find feature with id #" + sibling.id + " in program backlog.");
    }

    if (index === 0) {
        return {
            direction: Direction.BEFORE,
            compared_to: features_in_program_backlog[0].id,
        };
    }

    return {
        direction: Direction.AFTER,
        compared_to: features_in_program_backlog[index - 1].id,
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

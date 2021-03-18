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
import type {
    FeatureIdToMoveFromProgramIncrementToAnother,
    FeatureIdWithProgramIncrement,
    HandleDropContextWithProgramId,
} from "../helpers/drag-drop";
import { extractFeatureIndexFromProgramIncrement } from "../helpers/feature-extractor";
import { addElementToTopBackLog } from "../helpers/ProgramIncrement/add-to-top-backlog";
import { unplanFeature, planFeatureInProgramIncrement as planFeature } from "../helpers/drag-drop";
import type { FetchWrapperError } from "@tuleap/tlp-fetch";

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
    const element_id = handle_drop.dropped_element.dataset.elementId;
    if (!element_id) {
        return;
    }

    const plan_in_program_increment_id = handle_drop.target_dropzone.dataset.programIncrementId;
    const remove_from_program_increment_id = handle_drop.dropped_element.dataset.programIncrementId;

    if (plan_in_program_increment_id && !remove_from_program_increment_id) {
        const payload: FeatureIdWithProgramIncrement = {
            feature_id: parseInt(element_id, 10),
            program_increment: context.getters.getProgramIncrementFromId(
                parseInt(plan_in_program_increment_id, 10)
            ),
        };

        planFeatureInProgramIncrement(context, payload);

        try {
            await planFeature(
                handle_drop,
                parseInt(plan_in_program_increment_id, 10),
                parseInt(element_id, 10)
            );
        } catch (error) {
            await handleModalError(context, error);
        }
    }

    if (!plan_in_program_increment_id && remove_from_program_increment_id) {
        const payload: FeatureIdWithProgramIncrement = {
            feature_id: parseInt(element_id, 10),
            program_increment: context.getters.getProgramIncrementFromId(
                parseInt(remove_from_program_increment_id, 10)
            ),
        };

        unplanFeatureFromProgramIncrement(context, payload);

        try {
            await unplanFeature(
                handle_drop,
                parseInt(remove_from_program_increment_id, 10),
                parseInt(element_id, 10)
            );

            await addElementToTopBackLog(handle_drop.program_id, parseInt(element_id, 10));
        } catch (error) {
            await handleModalError(context, error);
        }
    }

    if (plan_in_program_increment_id && remove_from_program_increment_id) {
        const payload: FeatureIdToMoveFromProgramIncrementToAnother = {
            feature_id: parseInt(element_id, 10),
            from_program_increment: context.getters.getProgramIncrementFromId(
                parseInt(remove_from_program_increment_id, 10)
            ),
            to_program_increment: context.getters.getProgramIncrementFromId(
                parseInt(plan_in_program_increment_id, 10)
            ),
        };

        moveFeatureFromProgramIncrementToAnother(context, payload);

        try {
            await unplanFeature(
                handle_drop,
                parseInt(remove_from_program_increment_id, 10),
                parseInt(element_id, 10)
            );

            await planFeature(
                handle_drop,
                parseInt(plan_in_program_increment_id, 10),
                parseInt(element_id, 10)
            );
        } catch (error) {
            await handleModalError(context, error);
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

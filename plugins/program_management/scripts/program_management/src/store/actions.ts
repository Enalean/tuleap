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
} from "../helpers/drag-drop";
import { extractFeatureIndexFromProgramIncrement } from "../helpers/feature-extractor";

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

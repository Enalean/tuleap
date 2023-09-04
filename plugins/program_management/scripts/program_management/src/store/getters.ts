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
import type { FeatureIdWithProgramIncrementId } from "./mutations";
import type { SiblingFeatureHTMLElementWithProgramIncrement } from "../helpers/feature-reordering";

export const isProgramIncrementAlreadyAdded =
    (state: State) =>
    (increment_id: number): boolean => {
        return (
            state.program_increments.find((increment) => increment.id === increment_id) !==
            undefined
        );
    };

export const getProgramIncrementFromId =
    (state: State) =>
    (increment_id: number): ProgramIncrement => {
        const program_increment = state.program_increments.find(
            (increment) => increment.id === increment_id,
        );

        if (!program_increment) {
            throw Error("No program increment with id #" + increment_id);
        }

        return program_increment;
    };

export const getFeaturesInProgramIncrement =
    (state: State) =>
    (increment_id: number): Feature[] => {
        return getProgramIncrementFromId(state)(increment_id).features;
    };

export const getFeatureInProgramIncrement =
    (state: State) =>
    (feature_id_with_program_increment_id: FeatureIdWithProgramIncrementId): Feature => {
        const program_increment = getProgramIncrementFromId(state)(
            feature_id_with_program_increment_id.program_increment_id,
        );

        const feature = program_increment.features.find(
            (feature) => feature.id === feature_id_with_program_increment_id.feature_id,
        );

        if (!feature) {
            throw new Error(
                "Could not find feature with id #" +
                    feature_id_with_program_increment_id.feature_id +
                    " in program increment #" +
                    program_increment.id,
            );
        }

        return feature;
    };

export const getToBePlannedElementFromId =
    (state: State) =>
    (to_be_planned_element_id: number): Feature => {
        const to_be_planned_element = state.to_be_planned_elements.find(
            (to_be_planned_element) => to_be_planned_element.id === to_be_planned_element_id,
        );

        if (!to_be_planned_element) {
            throw Error("No to be planned element with id #" + to_be_planned_element_id);
        }

        return to_be_planned_element;
    };

export const hasAnElementMovedInsideIncrement = (state: State): boolean =>
    state.ongoing_move_elements_id.length > 0;

export const getSiblingFeatureFromProgramBacklog =
    (state: State) =>
    (next_sibling: HTMLElement): Feature | null => {
        const sibling_id = next_sibling.dataset.elementId;

        if (!sibling_id) {
            return null;
        }

        return getToBePlannedElementFromId(state)(parseInt(sibling_id, 10));
    };

export const getSiblingFeatureInProgramIncrement =
    (state: State) =>
    (sibling_with_pi: SiblingFeatureHTMLElementWithProgramIncrement): Feature | null => {
        const sibling_id = sibling_with_pi.sibling.dataset.elementId;

        if (!sibling_id) {
            return null;
        }

        return getFeatureInProgramIncrement(state)({
            feature_id: parseInt(sibling_id, 10),
            program_increment_id: sibling_with_pi.program_increment_id,
        });
    };

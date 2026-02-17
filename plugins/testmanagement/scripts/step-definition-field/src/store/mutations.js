/*
 * Copyright (c) Enalean, 2019-present. All Rights Reserved.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */
import { v4 as uuid } from "uuid";

export function initStepField(state, steps) {
    state.steps = steps.map((step) => {
        return { ...step, uuid: uuid(), is_deleted: false };
    });
}

export function setStepDeleted(state, [step, is_deleted]) {
    const index = state.steps.indexOf(step);
    if (index > -1) {
        state.steps[index].is_deleted = is_deleted;
    }
}

export function moveStep(state, [step_to_move, index]) {
    state.steps = state.steps.filter((step) => step.uuid !== step_to_move.uuid);
    state.steps.splice(index, 0, step_to_move);
}

export function addStep(state, [index, empty_step]) {
    const step = { ...empty_step };
    step.uuid = uuid();
    step.is_deleted = false;

    state.steps.splice(index, 0, step);
}

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
import uuid from "uuid/v4";
import dragula from "dragula";

export function initStepField(
    state,
    [steps, field_id, empty_step, upload_url, upload_field_name, upload_max_size]
) {
    state.steps = steps.map((step) => {
        return { ...step, uuid: uuid() };
    });
    state.field_id = field_id;
    state.empty_step = empty_step;
    state.upload_url = upload_url;
    state.upload_field_name = upload_field_name;
    state.upload_max_size = upload_max_size;
}

export function deleteStep(state, step) {
    const index = state.steps.indexOf(step);
    if (index > -1) {
        state.steps.splice(index, 1);
    }
}

export function initContainer(state, container) {
    state.container = container;
}

export function toggleIsDragging(state) {
    state.is_dragging = !state.is_dragging;
    if (state.is_dragging) {
        state.drake = dragula([state.container]);
    } else {
        if (state.drake) {
            state.drake.destroy();
        }
    }
}

export function addStep(state, index) {
    const step = Object.assign({}, state.empty_step);
    step.uuid = uuid();

    state.steps.splice(index, 0, step);
}

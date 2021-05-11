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

export function onGoingMoveFeature(
    ongoing_move_elements_id: number[],
    component: Element,
    feature_id: number,
    is_moving: boolean
): boolean {
    const feature_is_moving = ongoing_move_elements_id.indexOf(feature_id) !== -1;
    if (feature_is_moving) {
        component.classList.add("is-moving");
        return feature_is_moving;
    }

    const was_moving = is_moving && !feature_is_moving;

    if (was_moving) {
        component.classList.remove("is-moving");
        component.classList.add("has-moved");
        setTimeout(() => {
            component.classList.remove("has-moved");
        }, 1000);
    }

    return feature_is_moving;
}

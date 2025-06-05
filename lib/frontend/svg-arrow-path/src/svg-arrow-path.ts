/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { gap, threshold, startAt } from "./path";

export { gap } from "./path";

export function getDownRightArrow(width: number, height: number, element_height: number): string {
    if (width <= threshold) {
        return startAt(gap, gap, width, height)
            .halfTurnRight(element_height)
            .forwardAndStopIntoGap()
            .turnLeft()
            .forwardAndStopBeforeGap()
            .turnLeft()
            .arrowOnTheRightGap();
    }

    return startAt(gap, gap, width, height)
        .turnRight()
        .forwardAndStopBeforeGap()
        .turnLeft()
        .arrowOnTheRightGap();
}

export function getDownLeftArrow(width: number, height: number, element_height: number): string {
    return startAt(width - gap, gap, width, height)
        .halfTurnRight(element_height)
        .forwardAndStopIntoGap()
        .turnLeft()
        .forwardAndStopBeforeGap()
        .turnLeft()
        .arrowOnTheLeftGap();
}

export function getUpLeftArrow(width: number, height: number, element_height: number): string {
    return startAt(width - gap, height - gap, width, height)
        .halfTurnLeft(element_height)
        .forwardAndStopIntoGap()
        .turnRight()
        .forwardAndStopBeforeGap()
        .turnRight()
        .arrowOnTheLeftGap();
}

export function getUpRightArrow(width: number, height: number, element_height: number): string {
    if (width <= threshold) {
        return startAt(gap, height - gap, width, height)
            .halfTurnLeft(element_height)
            .forwardAndStopIntoGap()
            .turnRight()
            .forwardAndStopBeforeGap()
            .turnRight()
            .arrowOnTheRightGap();
    }

    return startAt(gap, height - gap, width, height)
        .turnLeft()
        .forwardAndStopBeforeGap()
        .turnRight()
        .arrowOnTheRightGap();
}

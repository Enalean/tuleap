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

import { onGoingMoveFeature } from "./on-going-move-feature-helper";

describe("onGoingMoveFeatureHelper", () => {
    describe("onGoingMoveFeature", () => {
        it("When feature is moving, then is-moving class is added", () => {
            const element = document.createElement("div");
            const feature_is_moving = onGoingMoveFeature([10], element, 10, false);
            expect(feature_is_moving).toBeTruthy();
            expect(element.classList).toContain("is-moving");
        });

        it("When feature was already moving and is still moving, then return true", () => {
            const element = document.createElement("div");
            const feature_is_moving = onGoingMoveFeature([10], element, 10, true);
            expect(feature_is_moving).toBeTruthy();
            expect(element.classList).toContain("is-moving");
        });

        it("When feature was stop moving, then classes changed and return false", () => {
            jest.useFakeTimers();
            const element = document.createElement("div");
            const feature_is_moving = onGoingMoveFeature([], element, 10, true);
            expect(element.classList).not.toContain("is-moving");
            expect(element.classList).toContain("has-moved");
            jest.advanceTimersByTime(1000);
            expect(element.classList).not.toContain("has-moved");
            expect(feature_is_moving).toBeFalsy();
        });

        it("When feature was not moving and is not moving, then return false", () => {
            const element = document.createElement("div");
            const feature_is_moving = onGoingMoveFeature([56], element, 10, false);
            expect(feature_is_moving).toBeFalsy();
            expect(element.classList).not.toContain("is-moving");
            expect(element.classList).not.toContain("has-moved");
        });
    });
});

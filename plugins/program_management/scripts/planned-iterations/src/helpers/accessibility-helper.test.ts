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

import { getAccessibilityClasses, showAccessibilityPattern } from "./accessibility-helper";
import type { UserStory } from "../type";

describe("accessibility-helper", () => {
    let user_story: UserStory;

    beforeEach(() => {
        user_story = {
            tracker: {
                color_name: "red-wine",
            },
            background_color: "lemon-green",
        } as UserStory;
    });

    it.each([true, false])(
        "should return background and accessibility classes when mode is enabled",
        (is_accessibility_mode_enabled: boolean) => {
            const classes = getAccessibilityClasses(user_story, is_accessibility_mode_enabled);

            expect(classes).toContain("element-card-red-wine");
            expect(classes).toContain("element-card-background-lemon-green");
            expect(classes.includes("element-card-with-accessibility")).toBe(
                is_accessibility_mode_enabled,
            );
        },
    );

    it.each([
        ["lemon-green", true, true],
        ["lemon-green", false, false],
        ["", true, false],
    ])(
        "should return true only when user_story has a bg color and accessibility mode is enabled",
        (
            card_bg_color: string,
            is_accessibility_mode_enabled: boolean,
            expected_return: boolean,
        ) => {
            const is_accessibility_patter_shown = showAccessibilityPattern(
                { background_color: card_bg_color } as UserStory,
                is_accessibility_mode_enabled,
            );

            expect(is_accessibility_patter_shown).toBe(expected_return);
        },
    );
});

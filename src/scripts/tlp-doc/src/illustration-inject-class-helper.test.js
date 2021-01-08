/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

import { injectClasses } from "./illustration-inject-class-helper";

describe("injectClasses", () => {
    it.each([
        ["#92cee5", "#92cee5", "tlp-illustration-primary-stroke tlp-illustration-primary-fill"],
        ["#92cee5", "#e0f6ff", "tlp-illustration-primary-box"],
        ["#92cee5", "#c1edff", "tlp-illustration-primary-dark-box"],
        ["#92cee5", "#d5e8f3", "tlp-illustration-primary-stroke tlp-illustration-tertiary-fill"],
        [
            "#e0f6ff",
            "#92cee5",
            "tlp-illustration-secondary-light-stroke tlp-illustration-primary-fill",
        ],
        [
            "#e0f6ff",
            "#e0f6ff",
            "tlp-illustration-secondary-light-stroke tlp-illustration-secondary-light-fill",
        ],
        [
            "#e0f6ff",
            "#c1edff",
            "tlp-illustration-secondary-light-stroke tlp-illustration-secondary-dark-fill",
        ],
        [
            "#e0f6ff",
            "#d5e8f3",
            "tlp-illustration-secondary-light-stroke tlp-illustration-tertiary-fill",
        ],
        [
            "#c1edff",
            "#92cee5",
            "tlp-illustration-secondary-dark-stroke tlp-illustration-primary-fill",
        ],
        [
            "#c1edff",
            "#e0f6ff",
            "tlp-illustration-secondary-dark-stroke tlp-illustration-secondary-light-fill",
        ],
        [
            "#c1edff",
            "#c1edff",
            "tlp-illustration-secondary-dark-stroke tlp-illustration-secondary-dark-fill",
        ],
        [
            "#c1edff",
            "#d5e8f3",
            "tlp-illustration-secondary-dark-stroke tlp-illustration-tertiary-fill",
        ],
        ["#d5e8f3", "#92cee5", "tlp-illustration-tertiary-stroke tlp-illustration-primary-fill"],
        [
            "#d5e8f3",
            "#e0f6ff",
            "tlp-illustration-tertiary-stroke tlp-illustration-secondary-light-fill",
        ],
        [
            "#d5e8f3",
            "#c1edff",
            "tlp-illustration-tertiary-stroke tlp-illustration-secondary-dark-fill",
        ],
        ["#d5e8f3", "#d5e8f3", "tlp-illustration-tertiary-stroke tlp-illustration-tertiary-fill"],
    ])(
        "injects tlp class accordingly to matching hexa colors set by illustrator",
        (stroke, fill, expected) => {
            const element = document.createElement("rect");
            element.setAttribute("stroke", stroke);
            element.setAttribute("fill", fill);

            injectClasses([element]);

            expect(element.getAttribute("class")).toBe(expected);
        }
    );
});

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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { ElementWithAccessibility } from "../type";

export function getAccessibilityClasses(
    element: ElementWithAccessibility,
    should_display_accessibility: boolean,
): string[] {
    const classnames = [`element-card-${element.tracker.color_name}`];

    if (element.background_color) {
        classnames.push(`element-card-background-${element.background_color}`);
    }

    if (showAccessibilityPattern(element, should_display_accessibility)) {
        classnames.push("element-card-with-accessibility");
    }

    return classnames;
}

export function showAccessibilityPattern(
    element: ElementWithAccessibility,
    should_display_accessibility: boolean,
): boolean {
    return should_display_accessibility && element.background_color !== "";
}

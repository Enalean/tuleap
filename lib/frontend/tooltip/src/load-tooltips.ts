/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

import { createTooltip } from "./create-tooltip";

const selectors = ["a.cross-reference", "a[class^=direct-link-to]"];

export const loadTooltipOnAnchorElement = function (
    element: HTMLAnchorElement,
    at_cursor_position?: boolean,
): void {
    const options = {
        at_cursor_position: Boolean(at_cursor_position),
    };

    createTooltip(element, options);
};

export const loadTooltips = function (element?: HTMLElement, at_cursor_position?: boolean): void {
    const targets = (element || document).querySelectorAll(selectors.join(","));

    targets.forEach(function (a) {
        if (a instanceof HTMLAnchorElement) {
            loadTooltipOnAnchorElement(a, at_cursor_position);
        }
    });
};

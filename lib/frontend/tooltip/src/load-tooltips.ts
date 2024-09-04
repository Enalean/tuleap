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

import type { Tooltip } from "./type";
import { elementWithCrossrefHref } from "./element-with-crossref-href";
import { createTooltip } from "./create-tooltip";
import type { Option } from "@tuleap/option";

const selectors = [".cross-reference", "a[class^=direct-link-to]"];

export const loadTooltipOnAnchorElement = function (
    element: HTMLAnchorElement,
    at_cursor_position?: boolean,
): void {
    loadTooltipOnElement(element, Boolean(at_cursor_position));
};

const stored_tooltips = new WeakMap<HTMLElement | Document, Tooltip[]>();
export const loadTooltips = function (element?: HTMLElement, at_cursor_position?: boolean): void {
    const container = element || document;

    stored_tooltips.get(container)?.forEach((tooltip: Tooltip) => tooltip.destroy());

    const targets = container.querySelectorAll(selectors.join(","));

    const tooltips: Tooltip[] = [];
    targets.forEach(function (a) {
        if (!(a instanceof HTMLElement)) {
            return;
        }

        loadTooltipOnElement(a, Boolean(at_cursor_position)).apply((tooltip: Tooltip) => {
            tooltips.push(tooltip);
        });
    });

    stored_tooltips.set(container, tooltips);
};

function loadTooltipOnElement(element: HTMLElement, at_cursor_position: boolean): Option<Tooltip> {
    const options = {
        at_cursor_position,
    };

    return elementWithCrossrefHref(element).map((crossref) => createTooltip(crossref, options));
}

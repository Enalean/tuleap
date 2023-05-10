/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { define, html, dispatch } from "hybrids";
import type { ColorVariant } from "@tuleap/core-constants";
import badge_style from "./selection-badge.scss";

export const TAG = "tuleap-lazybox-selection-badge";

export type SelectionBadge = {
    color: ColorVariant;
    outline: boolean;
};
export type InternalSelectionBadge = Readonly<SelectionBadge>;
export type HostElement = InternalSelectionBadge & HTMLElement;

export const getBadgeClasses = (host: SelectionBadge): Record<string, boolean> => ({
    "lazybox-badge": true,
    [`tlp-badge-${host.color}`]: true,
    "tlp-badge-outline": host.outline,
});

export const onClick = (host: HostElement): void => {
    dispatch(host, "remove-badge");
};

export const SelectionBadge = define<SelectionBadge>({
    tag: TAG,
    color: "primary",
    outline: false,
    render: (host) =>
        html`
            <span class="${getBadgeClasses(host)}">
                <button type="button" class="lazybox-badge-remove-button" onclick=${onClick}>
                    ×
                </button>
                <slot></slot>
            </span>
        `.style(badge_style),
});

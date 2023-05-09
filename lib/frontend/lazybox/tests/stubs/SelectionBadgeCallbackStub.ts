/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { LazyboxSelectionBadgeCallback } from "../../src/type";
import type { SelectionBadge } from "../../src/selection/SelectionBadge";
import { TAG } from "../../src/selection/SelectionBadge";

const isBadge = (element: HTMLElement): element is SelectionBadge & HTMLElement =>
    element.tagName === TAG.toUpperCase();

export const SelectionBadgeCallbackStub = {
    build: (): LazyboxSelectionBadgeCallback => (item) => {
        if (item) {
            //Do nothing
        }

        const badge = document.createElement(TAG);
        if (!isBadge(badge)) {
            throw Error("Could not create selection badge");
        }
        badge.color = "inca-silver";
        return badge;
    },
};

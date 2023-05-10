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

import { html } from "hybrids";
import type { LazyboxOptions, LazyboxSelectionBadgeCallback } from "./Options";
import type { SelectionBadge } from "./selection/SelectionBadge";
import { TAG as SELECTION_BADGE_TAG } from "./selection/SelectionBadge";
import type { LazyboxItem } from "./GroupCollection";

export const isBadge = (element: Element | null): element is HTMLElement & SelectionBadge =>
    element?.tagName === SELECTION_BADGE_TAG.toUpperCase();

export const getSelectionBadgeCallback = (
    options: LazyboxOptions
): LazyboxSelectionBadgeCallback => {
    return "selection_badge_callback" in options
        ? options.selection_badge_callback
        : (item: LazyboxItem): SelectionBadge & HTMLElement => {
              const target = document.createElement("span");
              const renderFunction = html`<tuleap-lazybox-selection-badge outline
                  >${options.templating_callback(html, item)}</tuleap-lazybox-selection-badge
              >`;
              renderFunction(target, target);
              const badge = target.firstElementChild;
              if (!isBadge(badge)) {
                  throw Error("Could not create badge");
              }
              return badge;
          };
};

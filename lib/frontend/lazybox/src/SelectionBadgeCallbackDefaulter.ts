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

import { render, html } from "lit/html.js";
import type { LazyboxOptions, LazyboxSelectionBadgeCallback, RenderedItem } from "./type";
import type { SelectionBadge } from "./selection/SelectionBadge";
import { TAG as SELECTION_BADGE_TAG } from "./selection/SelectionBadge";

export const isBadge = (element: Element | null): element is HTMLElement & SelectionBadge =>
    element?.tagName === SELECTION_BADGE_TAG.toUpperCase();

export const getSelectionBadgeCallback = (
    options: LazyboxOptions
): LazyboxSelectionBadgeCallback => {
    return options.selection_badge_callback
        ? options.selection_badge_callback
        : (item: RenderedItem): SelectionBadge & HTMLElement => {
              const document_fragment = document.createDocumentFragment();
              render(
                  html`
                      <tuleap-lazybox-selection-badge outline>
                          ${item.template}
                      </tuleap-lazybox-selection-badge>
                  `,
                  document_fragment
              );

              const selected_value_badge = document_fragment.firstElementChild;
              if (!isBadge(selected_value_badge)) {
                  throw new Error("Cannot create the selected value badge");
              }
              return selected_value_badge;
          };
};

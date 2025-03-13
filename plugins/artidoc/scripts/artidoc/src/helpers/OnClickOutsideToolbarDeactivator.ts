/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import type { Ref } from "vue";
import type { ToolbarBus } from "@tuleap/prose-mirror-editor";
import type { HeadingsButtonState } from "@/toolbar/HeadingsButtonState";

export type DeactivateToolbarOnClickOutside = {
    startListening(): void;
    stopListening(): void;
};

export const getOnClickOutsideToolbarDeactivator = (
    doc: Document,
    toolbar_element: Ref<HTMLElement | undefined>,
    toolbar_bus: ToolbarBus,
    headings_button_state: HeadingsButtonState,
): DeactivateToolbarOnClickOutside => {
    const handler = (event: Event): void => {
        if (!toolbar_element.value || !(event.target instanceof HTMLElement)) {
            return;
        }

        const editors = Array.from(doc.querySelectorAll(".editor"));
        const composed_path = event.composedPath();
        const is_click_on_editor = editors.some((editor) => composed_path.includes(editor));

        if (is_click_on_editor) {
            return;
        }

        const is_click_on_toolbar = composed_path.includes(toolbar_element.value);
        if (is_click_on_toolbar) {
            return;
        }

        const popovers = Array.from(doc.querySelectorAll(".prose-mirror-toolbar-popover"));
        const is_click_on_toolbar_popover = popovers.some((popover) =>
            composed_path.includes(popover),
        );
        if (is_click_on_toolbar_popover) {
            return;
        }

        toolbar_bus.disableToolbar();
        headings_button_state.deactivateButton();
    };

    return {
        startListening: (): void => {
            doc.addEventListener("click", handler, { passive: true });
        },
        stopListening: (): void => {
            doc.removeEventListener("click", handler);
        },
    };
};

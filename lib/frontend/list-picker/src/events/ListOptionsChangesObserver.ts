/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import type { ItemsMapManager } from "../items/ItemsMapManager";
import type { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import type { SelectionManager } from "../type";
import type { EventManager } from "./EventManager";

export class ListOptionsChangesObserver {
    private readonly observer: MutationObserver;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly list_picker_element_attributes_updater: () => void,
        private readonly items_map_manager: ItemsMapManager,
        private readonly dropdown_content_renderer: DropdownContentRenderer,
        private readonly selection_manager: SelectionManager,
        private readonly event_manager: EventManager,
    ) {
        this.observer = new MutationObserver(this.refreshListPickerOnOptionsChanges());
    }

    public startWatchingChanges(): void {
        this.observer.observe(this.source_select_box, {
            childList: true,
            subtree: true,
            attributes: true,
        });
    }

    public stopWatchingChangesInSelectOptions(): void {
        this.observer.disconnect();
    }

    private refreshListPickerOnOptionsChanges(): (mutations: Array<MutationRecord>) => void {
        return (mutations: Array<MutationRecord>): void => {
            this.list_picker_element_attributes_updater();
            if (!this.isChildrenMutation(mutations)) {
                return;
            }

            this.items_map_manager.refreshItemsMap();
            this.dropdown_content_renderer.renderAfterDependenciesUpdate();
            this.selection_manager.resetAfterChangeInOptions();
            this.event_manager.attachItemListEvent();
        };
    }

    private isChildrenMutation(mutations: Array<MutationRecord>): boolean {
        return mutations.some(
            (mutation) =>
                mutation.type === "childList" ||
                (mutation.type === "attributes" &&
                    ["disabled", "value"].includes(mutation.attributeName ?? "")),
        );
    }
}

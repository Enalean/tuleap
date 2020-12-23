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

import { ItemsMapManager } from "../items/ItemsMapManager";
import { DropdownContentRenderer } from "../renderers/DropdownContentRenderer";
import { SelectionManager } from "../type";
import { EventManager } from "./EventManager";

export class ListOptionsChangesObserver {
    private readonly observer: MutationObserver;

    constructor(
        private readonly source_select_box: HTMLSelectElement,
        private readonly items_map_manager: ItemsMapManager,
        private readonly dropdown_content_renderer: DropdownContentRenderer,
        private readonly selection_manager: SelectionManager,
        private readonly event_manager: EventManager
    ) {
        this.observer = new MutationObserver(this.refreshListPickerOnOptionsChanges());
    }

    public startWatchingChangesInSelectOptions(): void {
        this.observer.observe(this.source_select_box, {
            childList: true,
            subtree: true,
        });
    }

    public stopWatchingChangesInSelectOptions(): void {
        this.observer.disconnect();
    }

    private refreshListPickerOnOptionsChanges(): (mutations: Array<MutationRecord>) => void {
        return async (mutations: Array<MutationRecord>): Promise<void> => {
            const children_mutation = mutations.find((mutation) => mutation.type === "childList");
            if (!children_mutation) {
                return;
            }

            await this.items_map_manager.refreshItemsMap();
            this.dropdown_content_renderer.renderAfterDependenciesUpdate();
            this.selection_manager.resetAfterDependenciesUpdate();
            this.event_manager.attachItemListEvent();
        };
    }
}

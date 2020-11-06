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
import { ListOptionsChangesObserver } from "./ListOptionsChangesObserver";
import {
    appendGroupedOptionsToSourceSelectBox,
    appendSimpleOptionsToSourceSelectBox,
} from "../test-helpers/select-box-options-generator";

describe("ListOptionsChangesObserver", () => {
    let source_select_box: HTMLSelectElement,
        items_map_manager: ItemsMapManager,
        dropdown_content_renderer: DropdownContentRenderer,
        selection_manager: SelectionManager,
        event_manager: EventManager,
        list_options_changes_observer: ListOptionsChangesObserver;

    beforeEach(() => {
        source_select_box = document.createElement("select");
        dropdown_content_renderer = ({
            renderAfterDependenciesUpdate: jest.fn(),
        } as unknown) as DropdownContentRenderer;

        items_map_manager = ({
            refreshItemsMap: jest.fn().mockReturnValue(Promise.resolve()),
        } as unknown) as ItemsMapManager;

        selection_manager = ({
            resetAfterDependenciesUpdate: jest.fn(),
        } as unknown) as SelectionManager;

        event_manager = ({ attachItemListEvent: jest.fn() } as unknown) as EventManager;

        list_options_changes_observer = new ListOptionsChangesObserver(
            source_select_box,
            items_map_manager,
            dropdown_content_renderer,
            selection_manager,
            event_manager
        );
    });

    it("should refresh the list-picker when options are added in the source <select>", async () => {
        await new Promise((done) => {
            list_options_changes_observer.startWatchingChangesInSelectOptions();

            appendGroupedOptionsToSourceSelectBox(source_select_box);
            done();
        });

        await expect(items_map_manager.refreshItemsMap).toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).toHaveBeenCalled();
    });

    it("should refresh the list-picker when options are removed in the source <select>", async () => {
        await new Promise((done) => {
            appendSimpleOptionsToSourceSelectBox(source_select_box);
            list_options_changes_observer.startWatchingChangesInSelectOptions();

            source_select_box.innerHTML = "";
            done();
        });

        await expect(items_map_manager.refreshItemsMap).toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).toHaveBeenCalled();
    });

    it("should not react otherwise", async () => {
        await new Promise((done) => {
            appendSimpleOptionsToSourceSelectBox(source_select_box);
            list_options_changes_observer.startWatchingChangesInSelectOptions();

            source_select_box.options[0].setAttribute("selected", "selected");
            done();
        });

        await expect(items_map_manager.refreshItemsMap).not.toHaveBeenCalled();
        expect(dropdown_content_renderer.renderAfterDependenciesUpdate).not.toHaveBeenCalled();
        expect(selection_manager.resetAfterDependenciesUpdate).not.toHaveBeenCalled();
        expect(event_manager.attachItemListEvent).not.toHaveBeenCalled();
    });
});

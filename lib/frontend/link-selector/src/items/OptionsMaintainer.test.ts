/*
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

import { OptionsMaintainer } from "./OptionsMaintainer";
import { ItemsMapManager } from "./ItemsMapManager";
import { ListItemMapBuilder } from "./ListItemMapBuilder";
import { GroupCollectionBuilder } from "../../tests/builders/GroupCollectionBuilder";

describe(`OptionsMaintainer`, () => {
    let select_element: HTMLSelectElement, doc: Document;

    beforeEach(() => {
        doc = document.implementation.createHTMLDocument();
        select_element = doc.createElement("select");
    });

    const rebuild = (): void => {
        const items_map_manager = new ItemsMapManager(new ListItemMapBuilder());
        const maintainer = OptionsMaintainer(select_element, items_map_manager);
        items_map_manager.refreshItemsMap(GroupCollectionBuilder.withTwoGroups());

        return maintainer.rebuildOptions();
    };

    it(`Rebuilds the options of the select element from the map of items`, () => {
        rebuild();

        expect(select_element.options).toHaveLength(6);
    });

    it(`Erases existing options`, () => {
        const first_option = doc.createElement("option");
        first_option.innerText = "First Option";
        const second_option = doc.createElement("option");
        second_option.innerText = "Second Option";
        select_element.append(first_option, second_option);

        rebuild();

        const options = Array.from(select_element.options);
        expect(options).not.toContain(first_option);
        expect(options).not.toContain(second_option);
    });
});

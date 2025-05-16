/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import DocumentGlobalPropertyForCreate from "./DocumentGlobalPropertyForCreate.vue";
import type { Folder, ItemFile } from "../../../../../type";

describe("DocumentGlobalPropertyForCreate", () => {
    function createWrapper(
        item: ItemFile,
        parent: Folder,
    ): VueWrapper<InstanceType<typeof DocumentGlobalPropertyForCreate>> {
        return shallowMount(DocumentGlobalPropertyForCreate, {
            props: { currentlyUpdatedItem: item, parent },
        });
    }

    it(`renders component`, () => {
        const item = {
            id: 1,
            title: "My document",
            status: "Draft",
            description: "A custom description",
        } as ItemFile;
        const parent = {
            id: 2,
        } as Folder;
        const wrapper = createWrapper(item, parent);

        expect(wrapper.element).toMatchSnapshot();
    });
});

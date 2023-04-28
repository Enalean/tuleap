/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
import RootFolder from "./RootFolder.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";

describe("RootFolder", () => {
    let load_root_folder: jest.Mock;
    let remove_quick_look: jest.Mock;
    let reset_ascendent_hierarchy: jest.Mock;

    beforeEach(() => {
        load_root_folder = jest.fn();
        remove_quick_look = jest.fn();
        reset_ascendent_hierarchy = jest.fn();
    });

    function createWrapper(): VueWrapper<InstanceType<typeof RootFolder>> {
        return shallowMount(RootFolder, {
            global: {
                ...getGlobalTestOptions({
                    actions: {
                        loadRootFolder: load_root_folder,
                        removeQuickLook: remove_quick_look,
                    },
                    mutations: {
                        resetAscendantHierarchy: reset_ascendent_hierarchy,
                    },
                }),
            },
        });
    }

    it(`Should load folder content`, (): void => {
        createWrapper();

        expect(load_root_folder).toHaveBeenCalled();
        expect(remove_quick_look).toHaveBeenCalled();
        expect(reset_ascendent_hierarchy).toHaveBeenCalled();
    });
});

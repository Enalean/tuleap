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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import RootFolder from "./RootFolder.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import { PROJECT } from "../../configuration-keys";
import { ProjectBuilder } from "../../../tests/builders/ProjectBuilder";

describe("RootFolder", () => {
    let load_root_folder: vi.Mock;
    let remove_quick_look: vi.Mock;
    let reset_ascendent_hierarchy: vi.Mock;

    beforeEach(() => {
        load_root_folder = vi.fn();
        remove_quick_look = vi.fn();
        reset_ascendent_hierarchy = vi.fn();
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
                provide: {
                    [PROJECT.valueOf()]: new ProjectBuilder(101).build(),
                },
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

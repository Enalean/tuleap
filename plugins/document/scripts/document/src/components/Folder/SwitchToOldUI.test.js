/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import { beforeEach, describe, it, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import SwitchToOldUI from "./SwitchToOldUI.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import * as router from "vue-router";

vi.mock("vue-router");

describe("SwitchToOldUI", () => {
    let factory;
    let current_folder = null;

    beforeEach(() => {
        factory = () => {
            return shallowMount(SwitchToOldUI, {
                global: {
                    ...getGlobalTestOptions({
                        modules: {
                            configuration: {
                                state: { project_id: 101 },
                                namespaced: true,
                            },
                        },
                        state: {
                            current_folder,
                        },
                    }),
                },
            });
        };
    });

    it(`Given an user who browse a folder ( != root folder)
        The user wants to switch to old UI from this folder
        Then he is redirected on the old UI into the good folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            params: { name: "folder" },
        });

        const wrapper = factory();
        wrapper.get("a").element.href = "/plugins/docman/?group_id=100&action=show&id=20";
    });

    it(`Given an user toggle the quick look of an item
        The user wants to switch to old UI
        Then he is redirected on the old UI into the current folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            params: { name: "prview" },
        });
        current_folder = { id: 25 };

        const wrapper = factory();
        wrapper.get("a").element.href = "/plugins/docman/?group_id=100&action=show&id=25";
    });

    it(`Given an user who browse the root folder
        The user wants to switch to old UI
        Then he is redirected on the old UI into the root folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            params: { name: "root_folder" },
        });
        const wrapper = factory();
        wrapper.get("a").element.href = "/plugins/docman/?group_id=100";
    });
});

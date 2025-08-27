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

import { beforeEach, describe, expect, it, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import SwitchToOldUI from "./SwitchToOldUI.vue";
import { getGlobalTestOptions } from "../../helpers/global-options-for-test";
import type { RouteLocationNormalized } from "vue-router";
import * as router from "vue-router";
import type { Folder, RootState } from "../../type";
import { PROJECT_ID } from "../../configuration-keys";

vi.mock("vue-router");

describe("SwitchToOldUI", () => {
    let factory: () => VueWrapper<SwitchToOldUI>;
    let current_folder: Folder | null = null;

    beforeEach(() => {
        factory = (): VueWrapper<SwitchToOldUI> => {
            return shallowMount(SwitchToOldUI, {
                global: {
                    ...getGlobalTestOptions({
                        state: {
                            current_folder,
                        } as unknown as RootState,
                    }),
                    provide: {
                        [PROJECT_ID.valueOf()]: 101,
                    },
                },
            });
        };
    });

    it(`Given an user who browse a folder ( != root folder)
        The user wants to switch to old UI from this folder
        Then he is redirected on the old UI into the good folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            name: "folder",
            params: { item_id: "20" },
        } as unknown as RouteLocationNormalized);

        const wrapper = factory();
        expect(wrapper.get("a").element.href).toStrictEqual(
            "http://localhost:3000/plugins/docman/?group_id=101&action=show&id=20",
        );
    });

    it(`Given an user toggle the quick look of an item
        The user wants to switch to old UI
        Then he is redirected on the old UI into the current folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            name: "preview",
        } as unknown as RouteLocationNormalized);
        current_folder = { id: 25 } as Folder;

        const wrapper = factory();
        expect(wrapper.get("a").element.href).toStrictEqual(
            "http://localhost:3000/plugins/docman/?group_id=101&action=show&id=25",
        );
    });

    it(`Given an user who browse the root folder
        The user wants to switch to old UI
        Then he is redirected on the old UI into the root folder`, () => {
        vi.spyOn(router, "useRoute").mockReturnValue({
            name: "root_folder",
        } as unknown as RouteLocationNormalized);
        const wrapper = factory();
        expect(wrapper.get("a").element.href).toStrictEqual(
            "http://localhost:3000/plugins/docman/?group_id=101",
        );
    });
});

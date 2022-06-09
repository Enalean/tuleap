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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../helpers/local-vue";

import SwitchToOldUI from "./SwitchToOldUI.vue";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";

import VueRouter from "vue-router";

describe("SwitchToOldUI", () => {
    let factory, state, store, router;

    beforeEach(() => {
        state = {
            current_folder: null,
            configuration: { project_id: 100 },
        };

        const store_options = {
            state,
        };

        router = new VueRouter({
            routes: [
                {
                    path: "/preview/3",
                    name: "preview",
                },
                {
                    path: "/folder/20",
                    name: "folder",
                },
                {
                    path: "/",
                    name: "root_folder",
                },
            ],
        });
        store = createStoreMock(store_options);

        factory = () => {
            return shallowMount(SwitchToOldUI, {
                localVue,
                mocks: {
                    $store: store,
                },
                router,
            });
        };
    });

    it(`Given an user who browse a folder ( != root folder)
        The user wants to switch to old UI from this folder
        Then he is redirected on the old UI into the good folder`, () => {
        router.push({
            name: "folder",
            params: {
                item_id: 20,
            },
        });
        const wrapper = factory();

        expect(wrapper.vm.$route.name).toBe("folder");

        wrapper.get("a").element.href = "/plugins/docman/?group_id=100&action=show&id=20";
    });

    it(`Given an user toggle the quick look of an item
        The user wants to switch to old UI
        Then he is redirected on the old UI into the current folder`, () => {
        router.push({
            name: "preview",
            params: {
                preview_item_id: 3,
            },
        });
        store.state.current_folder = { id: 25 };

        const wrapper = factory();

        expect(wrapper.vm.$route.name).toBe("preview");

        wrapper.get("a").element.href = "/plugins/docman/?group_id=100&action=show&id=25";
    });

    it(`Given an user who browse the root folder
        The user wants to switch to old UI
        Then he is redirected on the old UI into the root folder`, () => {
        router.push({
            name: "root_folder",
        });

        const wrapper = factory();

        expect(wrapper.vm.$route.name).toBe("root_folder");

        wrapper.get("a").element.href = "/plugins/docman/?group_id=100";
    });
});

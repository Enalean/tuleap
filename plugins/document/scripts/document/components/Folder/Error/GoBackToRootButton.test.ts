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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import VueRouter from "vue-router";
import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import GoBackToRootButton from "./GoBackToRootButton.vue";

describe("GoBackToRootButton", () => {
    function getWrapper(router: VueRouter): Wrapper<GoBackToRootButton> {
        const store_options = {};
        const store = createStoreMock(store_options);

        return shallowMount(GoBackToRootButton, {
            localVue,
            router,
            mocks: {
                $store: store,
            },
        });
    }

    it(`Given we are not displaying root folder
        When error is displayed
        Then a button go back to root is displayed`, () => {
        const router = new VueRouter({
            routes: [
                {
                    path: "/folder/3/42",
                    name: "item",
                },
            ],
        });
        const wrapper = getWrapper(router);
        expect(wrapper.find("[data-test=item-can-go-to-root-button]").exists()).toBeTruthy();
    });

    it(`Given we are displaying root folder
        When error is displayed
        Then no button is displayed`, () => {
        const router = new VueRouter({
            routes: [
                {
                    path: "/",
                    name: "root_folder",
                },
            ],
        });

        const wrapper = getWrapper(router);
        expect(wrapper.find("[data-test=can-go-to-root-button]").exists()).toBeFalsy();
    });
});

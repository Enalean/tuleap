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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";

import QuickLookButton from "./QuickLookButton.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

describe("QuickLookButton", () => {
    it(`Emit displayQuickLook event with correct parameters when user click on button`, () => {
        const state = {
            project_id: 101
        };

        const store_options = {
            state
        };

        const store = createStoreMock(store_options);

        store.state.currently_previewed_item = {
            id: 42,
            title: "my item title"
        };

        const wrapper = shallowMount(QuickLookButton, {
            localVue,
            mocks: { $store: store }
        });

        wrapper.find("[data-test=quick-look-button]").trigger("click");

        expect(wrapper.emitted().displayQuickLook[0]).toEqual([
            store.state.currently_previewed_item
        ]);
    });
});

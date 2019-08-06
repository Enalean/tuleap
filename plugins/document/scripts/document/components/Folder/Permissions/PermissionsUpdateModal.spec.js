/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

import PermissionsUpdateModal from "./PermissionsUpdateModal.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

describe("PermissionsUpdateModal", () => {
    let factory, store;

    beforeEach(() => {
        store = createStoreMock({}, { project_id: 102 });

        factory = (props = {}) => {
            return shallowMount(PermissionsUpdateModal, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store }
            });
        };
    });

    it("Given the user want to edit the permissions the corresponding modal can be opened", () => {
        const item_to_update = {
            id: 104,
            title: "My item"
        };
        const wrapper = factory({ item: item_to_update });

        expect(wrapper.html()).toBeTruthy();
    });
});

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
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";
import localVue from "../../../support/local-vue.js";
import EmptyPostAction from "./EmptyPostAction.vue";

describe(`EmptyPostAction`, () => {
    it(`When I click on the "Add action" button,
        it will commit a mutation to create a new post action`, () => {
        const store = createStoreMock({});
        const wrapper = shallowMount(EmptyPostAction, {
            localVue,
            mocks: { $store: store },
        });

        const add_action_button = wrapper.get("[data-test=add-post-action]");
        add_action_button.trigger("click");

        expect(store.commit).toHaveBeenCalledWith("transitionModal/addPostAction");
    });
});

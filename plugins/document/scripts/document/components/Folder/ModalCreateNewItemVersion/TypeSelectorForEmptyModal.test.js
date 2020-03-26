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

import { shallowMount } from "@vue/test-utils";
import localVue from "../../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

import TypeSelectorForEmptyModal from "./TypeSelectorForEmptyModal.vue";

describe("TypeSelectorForEmptyModal", () => {
    let factory, state, store, store_options;
    beforeEach(() => {
        state = {};
        store_options = {
            state,
        };
        store = createStoreMock(store_options);

        factory = (props = {}) => {
            return shallowMount(TypeSelectorForEmptyModal, {
                localVue,
                propsData: { ...props },
                mocks: { $store: store },
            });
        };
    });
    it(`Given embedded files are not enabled in project
        Then the type selector does not display embedded box to user`, () => {
        store.state.embedded_are_allowed = false;
        const wrapper = factory();
        expect(wrapper.find("[data-test=document-type-selector-file]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-embedded]").exists()).toBeFalsy();
    });

    it(`Given embedded files are available in project
        Then the type selector display embedded box to user`, () => {
        store.state.embedded_are_allowed = true;
        const wrapper = factory();
        expect(wrapper.find("[data-test=document-type-selector-file]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-embedded]").exists()).toBeTruthy();
    });
});

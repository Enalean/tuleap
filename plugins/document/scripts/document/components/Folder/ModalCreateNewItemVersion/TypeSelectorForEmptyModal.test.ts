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

import type { Wrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import TypeSelectorForEmptyModal from "./TypeSelectorForEmptyModal.vue";
import { createStoreMock } from "@tuleap/core/scripts/vue-components/store-wrapper-jest";
import localVue from "../../../helpers/local-vue";

describe("TypeSelectorForEmptyModal", () => {
    function createWrapper(embedded_are_allowed: boolean): Wrapper<TypeSelectorForEmptyModal> {
        return shallowMount(TypeSelectorForEmptyModal, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        configuration: {
                            embedded_are_allowed,
                        },
                    },
                }),
            },
            propsData: { value: "My empty name" },
            localVue,
        });
    }

    it(`Given embedded files are not enabled in project
        Then the type selector does not display embedded box to user`, () => {
        const wrapper = createWrapper(false);
        expect(wrapper.find("[data-test=document-type-selector-file]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-embedded]").exists()).toBeFalsy();
    });

    it(`Given embedded files are available in project
        Then the type selector display embedded box to user`, () => {
        const wrapper = createWrapper(true);
        expect(wrapper.find("[data-test=document-type-selector-file]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-link]").exists()).toBeTruthy();
        expect(wrapper.find("[data-test=document-type-selector-embedded]").exists()).toBeTruthy();
    });
});

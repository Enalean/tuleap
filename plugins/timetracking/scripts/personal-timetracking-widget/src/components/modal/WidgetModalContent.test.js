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
import WidgetModalContent from "./WidgetModalContent.vue";
import localVue from "../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/www/scripts/vue-components/store-wrapper-jest.js";

function getWidgetModalContentInstance(store) {
    const component_options = {
        localVue,
        mocks: { $store: store },
    };
    return shallowMount(WidgetModalContent, component_options);
}

describe("Given a personal timetracking widget modal", () => {
    let store_options, store;
    beforeEach(() => {
        store_options = {
            state: {
                rest_feedback: "",
                is_add_mode: false,
            },
            getters: {
                current_artifact: { artifact: "artifact" },
            },
        };
        store = createStoreMock(store_options);

        getWidgetModalContentInstance(store_options, store);
    });

    it("When there is no REST feedback, then feedback message should not be displayed", () => {
        const wrapper = getWidgetModalContentInstance(store);
        expect(wrapper.contains("[data-test=feedback]")).toBeFalsy();
    });

    it("When there is REST feedback, then feedback message should be displayed", () => {
        store.state.rest_feedback = { type: "success" };
        const wrapper = getWidgetModalContentInstance(store);
        expect(wrapper.contains("[data-test=feedback]")).toBeTruthy();
    });

    it("When add mode button is triggered, then setAddMode should be called", () => {
        const wrapper = getWidgetModalContentInstance(store);

        wrapper.get("[data-test=button-set-add-mode]").trigger("click");
        expect(store.commit).toHaveBeenCalledWith("setAddMode", true);
    });
});

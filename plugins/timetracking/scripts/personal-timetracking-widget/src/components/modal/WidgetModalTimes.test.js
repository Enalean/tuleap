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
import WidgetModalTimes from "./WidgetModalTimes.vue";
import localVue from "../../helpers/local-vue.js";
import { createStoreMock } from "../../../../../../../src/scripts/vue-components/store-wrapper-jest.js";

function getWidgetModalTimesInstance(store_options) {
    const store = createStoreMock(store_options);

    const component_options = {
        localVue,
        mocks: { $store: store },
    };
    return shallowMount(WidgetModalTimes, component_options);
}

describe("Given a personal timetracking widget modal", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            getters: {
                current_artifact: { artifact: "artifact" },
            },
        };

        getWidgetModalTimesInstance(store_options);
    });

    it("When current artifact is not empty, then modal content should be displayed", () => {
        const wrapper = getWidgetModalTimesInstance(store_options);
        expect(wrapper.contains("[data-test=modal-content]")).toBeTruthy();
    });

    it("When current artifact is empty, then modal content should not be displayed", () => {
        store_options.getters.current_artifact = null;
        const wrapper = getWidgetModalTimesInstance(store_options);
        expect(wrapper.contains("[data-test=modal-content]")).toBeFalsy();
    });
});

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
import App from "./App.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper.js";

const project_id = 102;
function getPersonalWidgetInstance(store_options) {
    const store = createStoreMock(store_options);
    const component_options = {
        propsData: {
            project_id
        },
        mocks: { $store: store }
    };
    return shallowMount(App, component_options);
}

describe("Given a release widget", () => {
    let store_options;
    beforeEach(() => {
        store_options = {
            state: {
                is_loading: false
            },
            getters: {
                has_rest_error: false
            }
        };

        getPersonalWidgetInstance(store_options);
    });

    it("When there are no errors, then the widget content will be displayed", () => {
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=widget-content]")).toBeTruthy();
        expect(wrapper.contains("[data-test=show-error-message]")).toBeFalsy();
        expect(wrapper.contains("[data-test=is-loading]")).toBeFalsy();
    });

    it("When there is an error, then the widget content will not be displayed", () => {
        store_options.getters.has_rest_error = true;
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=show-error-message]")).toBeTruthy();
        expect(wrapper.contains("[data-test=widget-content]")).toBeFalsy();
        expect(wrapper.contains("[data-test=is-loading]")).toBeFalsy();
    });

    it("When it is loading rest data, then a loader will be displayed", () => {
        store_options.state.is_loading = true;
        const wrapper = getPersonalWidgetInstance(store_options);

        expect(wrapper.contains("[data-test=is-loading]")).toBeTruthy();
        expect(wrapper.contains("[data-test=widget-content]")).toBeFalsy();
        expect(wrapper.contains("[data-test=show-error-message]")).toBeFalsy();
    });
});

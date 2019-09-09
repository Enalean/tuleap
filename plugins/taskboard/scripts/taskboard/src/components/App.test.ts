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
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";

describe("App", () => {
    it("displays misconfiguration error when there are no column", () => {
        const wrapper = shallowMount(App, {
            mocks: { $store: createStoreMock({ state: { columns: [], has_content: true } }) }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays misconfiguration error even if there are no content", () => {
        const wrapper = shallowMount(App, {
            mocks: { $store: createStoreMock({ state: { columns: [], has_content: false } }) }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays the board when there are columns", () => {
        const wrapper = shallowMount(App, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }],
                        has_content: true
                    }
                })
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays empty state when there is no content", () => {
        const wrapper = shallowMount(App, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }],
                        has_content: false
                    }
                })
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });
});

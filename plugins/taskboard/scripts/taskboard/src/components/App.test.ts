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

import { shallowMount, type VueWrapper } from "@vue/test-utils";
import App from "./App.vue";
import type { ColumnDefinition } from "../type";
import type { RootState } from "../store/type";
import { getGlobalTestOptions } from "../helpers/global-options-for-test";

describe("App", () => {
    function createWrapper(
        has_content: boolean,
        columns: Array<ColumnDefinition>,
        has_global_error: boolean,
        has_modal_error: boolean,
    ): VueWrapper<InstanceType<typeof App>> {
        return shallowMount(App, {
            global: {
                ...getGlobalTestOptions({
                    state: {
                        has_content: has_content,
                        error: {
                            has_global_error,
                            has_modal_error,
                        },
                        column: {
                            columns,
                        },
                    } as RootState,
                    modules: {
                        swimlane: {
                            actions: {
                                loadSwimlanes: jest.fn(),
                            },
                            namespaced: true,
                        },
                    },
                }),
            },
        });
    }

    it("displays misconfiguration error when there are no column", () => {
        const wrapper = createWrapper(true, [], false, false);
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays misconfiguration error even if there are no content", () => {
        const wrapper = createWrapper(false, [], false, false);
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays the board when there are columns", () => {
        const wrapper = createWrapper(
            true,
            [
                { id: 2, label: "To do" },
                { id: 3, label: "Done" },
            ] as Array<ColumnDefinition>,
            false,
            false,
        );
        expect(wrapper.element).toMatchSnapshot();
    });
    it("displays empty state when there is no content", () => {
        const wrapper = createWrapper(
            false,
            [
                { id: 2, label: "To do" },
                { id: 3, label: "Done" },
            ] as Array<ColumnDefinition>,
            false,
            false,
        );
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays global error state when there is an error", () => {
        const wrapper = createWrapper(true, [], true, false);
        expect(wrapper.element).toMatchSnapshot();
    });
});

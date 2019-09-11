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
import TaskBoardBody from "./TaskBoardBody.vue";
import { createStoreMock } from "@tuleap-vue-components/store-wrapper-jest";

describe("TaskBoardBody", () => {
    it("displays swimlanes with empty columns", () => {
        const wrapper = shallowMount(TaskBoardBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        columns: [{ id: 2, label: "To do" }, { id: 3, label: "Done" }],
                        swimlanes: [
                            {
                                card: {
                                    id: 43,
                                    label: "Story 2",
                                    xref: "story #43",
                                    rank: 11
                                }
                            },
                            {
                                card: {
                                    id: 44,
                                    label: "Story 3",
                                    xref: "story #44",
                                    rank: 12
                                }
                            }
                        ]
                    }
                })
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("loads all swimlanes as soon as the component is created", () => {
        const $store = createStoreMock({});
        shallowMount(TaskBoardBody, { mocks: { $store } });
        expect($store.dispatch).toHaveBeenCalledWith("loadSwimlanes");
    });
});

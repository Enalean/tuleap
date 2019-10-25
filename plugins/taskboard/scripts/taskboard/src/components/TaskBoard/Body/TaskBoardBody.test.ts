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
import SwimlaneSkeleton from "./Swimlane/Skeleton/SwimlaneSkeleton.vue";
import CollapsedSwimlane from "./Swimlane/CollapsedSwimlane.vue";
import { Swimlane } from "../../../type";

describe("TaskBoardBody", () => {
    it("displays swimlanes for solo cards or cards with children", () => {
        const wrapper = shallowMount(TaskBoardBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        swimlane: {
                            swimlanes: [
                                {
                                    card: {
                                        id: 43,
                                        has_children: false,
                                        is_open: true,
                                        is_collapsed: false
                                    }
                                } as Swimlane,
                                {
                                    card: {
                                        id: 44,
                                        has_children: true,
                                        is_open: true,
                                        is_collapsed: false
                                    }
                                } as Swimlane
                            ]
                        }
                    }
                })
            }
        });
        expect(wrapper.element).toMatchSnapshot();
    });

    it("displays collapsed swimlanes", () => {
        const wrapper = shallowMount(TaskBoardBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        swimlane: {
                            swimlanes: [
                                {
                                    card: {
                                        id: 43,
                                        has_children: false,
                                        is_open: true,
                                        is_collapsed: true
                                    }
                                } as Swimlane
                            ]
                        }
                    }
                })
            }
        });
        expect(wrapper.contains(CollapsedSwimlane)).toBe(true);
    });

    it("does not display swimlane that are closed if user wants to hide them", () => {
        const wrapper = shallowMount(TaskBoardBody, {
            mocks: {
                $store: createStoreMock({
                    state: {
                        are_closed_items_displayed: false,
                        swimlane: {
                            swimlanes: [
                                {
                                    card: {
                                        id: 43,
                                        has_children: false,
                                        is_open: false,
                                        is_collapsed: true
                                    }
                                } as Swimlane
                            ]
                        }
                    }
                })
            }
        });
        expect(wrapper.element.children.length).toBe(0);
    });

    it("loads all swimlanes as soon as the component is created", () => {
        const $store = createStoreMock({ state: { swimlane: {} } });
        shallowMount(TaskBoardBody, { mocks: { $store } });
        expect($store.dispatch).toHaveBeenCalledWith("swimlane/loadSwimlanes");
    });

    it("displays skeletons when swimlanes are being loaded", () => {
        const $store = createStoreMock({ state: { swimlane: { is_loading_swimlanes: true } } });
        const wrapper = shallowMount(TaskBoardBody, { mocks: { $store } });
        expect(wrapper.contains(SwimlaneSkeleton)).toBe(true);
    });
});

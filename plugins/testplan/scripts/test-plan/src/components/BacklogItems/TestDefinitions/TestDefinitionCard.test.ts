/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
import TestDefinitionCard from "./TestDefinitionCard.vue";
import type { BacklogItem, TestDefinition } from "../../../type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

jest.useFakeTimers();

describe("TestDefinitionCard", () => {
    it("Display a test definition as a card", () => {
        const wrapper = shallowMount(TestDefinitionCard, {
            props: {
                test_definition: {
                    id: 123,
                    short_type: "test_def",
                    summary: "Test definition summary",
                    test_status: "notrun",
                    category: "Some category",
                } as TestDefinition,
                backlog_item: { id: 456 } as BacklogItem,
            },
            global: {
                ...getGlobalTestOptions({}),
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Marks the test as just refreshed", () => {
        const remove_is_just_refreshed_flag_on_test_definition_spy = jest.fn();
        const wrapper = shallowMount(TestDefinitionCard, {
            props: {
                test_definition: {
                    id: 123,
                    is_just_refreshed: true,
                } as TestDefinition,
                backlog_item: { id: 456 } as BacklogItem,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        backlog_item: {
                            namespaced: true,
                            state: {},
                            mutations: {
                                removeIsJustRefreshedFlagOnTestDefinition:
                                    remove_is_just_refreshed_flag_on_test_definition_spy,
                            },
                        },
                    },
                }),
            },
        });

        expect(wrapper.classes("test-plan-test-definition-is-just-refreshed")).toBe(true);

        jest.advanceTimersByTime(1000);

        expect(remove_is_just_refreshed_flag_on_test_definition_spy).toHaveBeenCalledWith(
            expect.any(Object),
            {
                backlog_item: { id: 456 },
                test_definition: { id: 123, is_just_refreshed: true },
            },
        );
    });
});

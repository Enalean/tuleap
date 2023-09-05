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

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { BacklogItem, TestDefinition } from "../../../type";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import type { RootState } from "../../../store/type";
import ListOfTestDefinitions from "./ListOfTestDefinitions.vue";
import TestDefinitionSkeleton from "./TestDefinitionSkeleton.vue";
import TestDefinitionCard from "./TestDefinitionCard.vue";
import TestDefinitionEmptyState from "./TestDefinitionEmptyState.vue";
import TestDefinitionErrorState from "./TestDefinitionErrorState.vue";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("ListOfTestDefinitions", () => {
    function createWrapper(
        backlog_item: BacklogItem,
    ): VueWrapper<InstanceType<typeof ListOfTestDefinitions>> {
        return shallowMount(ListOfTestDefinitions, {
            props: {
                backlog_item,
            },
            global: {
                ...getGlobalTestOptions({
                    modules: {
                        backlog_item: {
                            namespaced: true,
                            state: backlog_item,
                        },
                    },
                }),
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        backlog_item: {},
                    } as RootState,
                }),
            },
        });
    }

    it("Displays skeletons while loading", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(true);
    });

    it("Does not display skeletons when not loading", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(false);
    });

    it("Does not display any cards when there is no test definitions", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionCard).exists()).toBe(false);
    });

    it("Displays a card for each test definitions", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [{ id: 123 }, { id: 234 }] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findAllComponents(TestDefinitionCard)).toHaveLength(2);
    });

    it("Displays skeletons even if there are test definitions to show loading indication", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(true);
    });

    it("Displays empty state when there is no test definitions", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(true);
    });

    it("Does not display empty state when there is no test definitions but it is still loading", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Does not display empty state when there are test definitions", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [{ id: 123 }, { id: 234 }] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Does not display empty state when there is an error", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: true,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Displays error state when there is an error", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: true,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionErrorState).exists()).toBe(true);
    });

    it("Does not display error state when there is no error", async () => {
        const wrapper = await createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionErrorState).exists()).toBe(false);
    });
});

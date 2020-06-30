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

import { shallowMount, Wrapper } from "@vue/test-utils";
import { BacklogItem, TestDefinition } from "../../../type";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../store/type";
import ListOfTestDefinitions from "./ListOfTestDefinitions.vue";
import TestDefinitionSkeleton from "./TestDefinitionSkeleton.vue";
import TestDefinitionCard from "./TestDefinitionCard.vue";
import TestDefinitionEmptyState from "./TestDefinitionEmptyState.vue";
import TestDefinitionErrorState from "./TestDefinitionErrorState.vue";

describe("ListOfTestDefinitions", () => {
    function createWrapper(backlog_item: BacklogItem): Wrapper<ListOfTestDefinitions> {
        return shallowMount(ListOfTestDefinitions, {
            propsData: {
                backlog_item,
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

    it("Displays skeletons while loading", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(true);
    });

    it("Does not display skeletons when not loading", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(false);
    });

    it("Does not display any cards when there is no test definitions", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionCard).exists()).toBe(false);
    });

    it("Displays a card for each test definitions", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [{ id: 123 }, { id: 234 }] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findAllComponents(TestDefinitionCard).length).toBe(2);
    });

    it("Displays skeletons even if there are test definitions to show loading indication", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionSkeleton).exists()).toBe(true);
    });

    it("Displays empty state when there is no test definitions", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(true);
    });

    it("Does not display empty state when there is no test definitions but it is still loading", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: true,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Does not display empty state when there are test definitions", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [{ id: 123 }, { id: 234 }] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Does not display empty state when there is an error", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: true,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionEmptyState).exists()).toBe(false);
    });

    it("Displays error state when there is an error", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: true,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionErrorState).exists()).toBe(true);
    });

    it("Does not display error state when there is no error", () => {
        const wrapper = createWrapper({
            are_test_definitions_loaded: false,
            is_loading_test_definitions: false,
            test_definitions: [] as TestDefinition[],
            has_test_definitions_loading_error: false,
        } as BacklogItem);

        expect(wrapper.findComponent(TestDefinitionErrorState).exists()).toBe(false);
    });
});

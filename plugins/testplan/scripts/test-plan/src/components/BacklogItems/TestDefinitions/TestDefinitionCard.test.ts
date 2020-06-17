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
import { TestDefinition } from "../../../type";

describe("TestDefinitionCard", () => {
    it("Display a test definition as a card", () => {
        const wrapper = shallowMount(TestDefinitionCard, {
            propsData: {
                test_definition: {
                    id: 123,
                    short_type: "test_def",
                    summary: "Test definition summary",
                } as TestDefinition,
            },
        });

        expect(wrapper.element).toMatchSnapshot();
    });

    it("Display an icon for automated tests", () => {
        const wrapper = shallowMount(TestDefinitionCard, {
            propsData: {
                test_definition: {
                    id: 123,
                    short_type: "test_def",
                    summary: "Test definition summary",
                    automated_tests: "Automated test name",
                },
            },
        });

        expect(wrapper.find("[data-test=automated-test-icon]").exists()).toBe(true);
    });
});

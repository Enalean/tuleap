/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import TestDefinitionCardStatus from "./TestDefinitionCardStatus.vue";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import { RootState } from "../../../store/type";
import { TestDefinition } from "../../../type";

describe("TestDefinitionCardStatus", () => {
    it("has a link to go to the test exec in TTM when the test definition is planned", () => {
        const wrapper = shallowMount(TestDefinitionCardStatus, {
            propsData: {
                test_definition: {
                    id: 123,
                    test_status: "notrun",
                    test_execution_used_to_define_status: {
                        id: 123,
                    },
                    test_campaign_defining_status: {
                        id: 41,
                    },
                } as TestDefinition,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                    } as RootState,
                }),
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <a href="/plugins/testmanagement/?group_id=102&amp;milestone_id=11#!/campaigns/41/123/123">
              <test-definition-card-status-tooltip-icon-stub test_definition="[object Object]"></test-definition-card-status-tooltip-icon-stub>
            </a>
        `);
    });

    it("only shows the icon when the test definition is not planned", () => {
        const wrapper = shallowMount(TestDefinitionCardStatus, {
            propsData: {
                test_definition: {
                    id: 123,
                    test_status: null,
                    test_execution_used_to_define_status: null,
                    test_campaign_defining_status: null,
                } as TestDefinition,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                    } as RootState,
                }),
            },
        });

        expect(wrapper).toMatchInlineSnapshot(
            `<test-definition-card-status-tooltip-icon-stub test_definition="[object Object]"></test-definition-card-status-tooltip-icon-stub>`
        );
    });
});

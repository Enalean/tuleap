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
import type { BacklogItem, TestDefinition } from "../../../type";
import { createStoreMock } from "../../../../../../../../src/scripts/vue-components/store-wrapper-jest";
import type { RootState } from "../../../store/type";
import TestDefinitionCardXrefCategoryStatus from "./TestDefinitionCardXrefCategoryStatus.vue";
import { createTestPlanLocalVue } from "../../../helpers/local-vue-for-test";

describe("TestDefinitionCardXrefCategoryStatus", () => {
    it("has a link to go to the test def and a link to to the test exec in a dropdown menu when the element is planned", async () => {
        const wrapper = shallowMount(TestDefinitionCardXrefCategoryStatus, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                test_definition: {
                    id: 123,
                    short_type: "test_def",
                    summary: "Test Def Summary",
                    test_status: "passed",
                    test_execution_used_to_define_status: {
                        id: 123,
                    },
                    test_campaign_defining_status: {
                        id: 41,
                    },
                } as TestDefinition,
                backlog_item: {
                    id: 741,
                } as BacklogItem,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                        milestone_title: "Milestone Title",
                    } as RootState,
                }),
            },
        });

        expect(wrapper).toMatchInlineSnapshot(`
            <div class="test-plan-test-definition-metadata">
              <div class="tlp-dropdown"><a href="/plugins/tracker/?aid=123&amp;ttm_backlog_item_id=741&amp;ttm_milestone_id=11" class="test-plan-test-definition-xref" data-dropdown="trigger"><span class="test-plan-test-definition-xref-text">
                            test_def #123
                        </span> <i class="fa fa-caret-down test-plan-test-definition-xref-icon"></i></a>
                <div role="menu" class="tlp-dropdown-menu tlp-dropdown-menu-left" data-dropdown="menu"><a href="/plugins/tracker/?aid=123&amp;ttm_backlog_item_id=741&amp;ttm_milestone_id=11" role="menuitem" class="tlp-dropdown-menu-item"><i class="fas fa-fw tlp-dropdown-menu-item-icon fa-pencil-alt"></i>
                    <translate-stub tag="span" translateparams="[object Object]">
                      Edit %{ item_type } #%{ item_id }
                    </translate-stub>
                  </a> <span role="separator" class="tlp-dropdown-menu-separator"></span> <a href="/plugins/testmanagement/?group_id=102&amp;milestone_id=11#!/campaigns/41/123/123" role="menuitem" data-test="go-to-last-test-exec" class="tlp-dropdown-menu-item"><i class="fas fa-fw tlp-dropdown-menu-item-icon fa-long-arrow-alt-right"></i>
                    <translate-stub tag="span" translateparams="[object Object]">
                      Go to the last execution in release %{ release_name }
                    </translate-stub>
                  </a></div>
              </div>
              <div class="test-plan-test-definition-card-category-status"><span data-test="test-category" class="tlp-badge-secondary tlp-badge-outline test-plan-test-definition-category">

                    </span>
                <div class="test-plan-test-definition-icons">
                  <!---->
                  <test-definition-card-status-stub test_definition="[object Object]"></test-definition-card-status-stub>
                </div>
              </div>
            </div>
        `);
    });

    it("does not have a link to the last test exec when the item is not planned", async () => {
        const wrapper = shallowMount(TestDefinitionCardXrefCategoryStatus, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                test_definition: {
                    id: 124,
                    short_type: "test_def",
                    summary: "Test Def Summary",
                    test_status: null,
                    test_execution_used_to_define_status: null,
                    test_campaign_defining_status: null,
                } as TestDefinition,
                backlog_item: {
                    id: 741,
                } as BacklogItem,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                        milestone_title: "Milestone Title",
                    } as RootState,
                }),
            },
        });

        expect(wrapper.find("[data-test=go-to-last-test-exec]").exists()).toBe(false);
    });

    it("Display an icon for automated tests", async () => {
        const wrapper = shallowMount(TestDefinitionCardXrefCategoryStatus, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                test_definition: {
                    id: 123,
                    short_type: "test_def",
                    summary: "Test definition summary",
                    automated_tests: "Automated test name",
                    test_status: null,
                    test_execution_used_to_define_status: null,
                    test_campaign_defining_status: null,
                } as TestDefinition,
                backlog_item: {
                    id: 741,
                } as BacklogItem,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                        milestone_title: "Milestone Title",
                    } as RootState,
                }),
            },
        });

        expect(wrapper.find("[data-test=automated-test-icon]").exists()).toBe(true);
    });

    it("does not display a category when none is set for the test", async () => {
        const wrapper = shallowMount(TestDefinitionCardXrefCategoryStatus, {
            localVue: await createTestPlanLocalVue(),
            propsData: {
                test_definition: {
                    id: 125,
                    short_type: "test_def",
                    summary: "Test definition summary",
                    category: null,
                    test_status: null,
                    test_execution_used_to_define_status: null,
                    test_campaign_defining_status: null,
                } as TestDefinition,
                backlog_item: {
                    id: 741,
                } as BacklogItem,
            },
            mocks: {
                $store: createStoreMock({
                    state: {
                        project_id: 102,
                        milestone_id: 11,
                        milestone_title: "Milestone Title",
                    } as RootState,
                }),
            },
        });

        expect(wrapper.find("[data-test=test-category]").exists()).toBe(false);
    });
});

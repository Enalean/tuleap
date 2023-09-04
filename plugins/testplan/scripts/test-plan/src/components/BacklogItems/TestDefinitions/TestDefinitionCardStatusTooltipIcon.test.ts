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
import TestDefinitionCardStatusTooltipIcon from "./TestDefinitionCardStatusTooltipIcon.vue";
import type { RootState } from "../../../store/type";
import { getGlobalTestOptions } from "../../../helpers/global-options-for-test";

describe("TestDefinitionCardStatusTooltipIcon", () => {
    it.each([
        ["passed", "Passed", "fa-check-circle"],
        ["failed", "Failed", "fa-times-circle"],
        ["blocked", "Blocked", "fa-exclamation-circle"],
        ["notrun", "Not run", "fa-question-circle"],
        [null, "Not planned in release MyRelease", "fa-circle-thin"],
    ])(
        "Displays an icon for test with %s status with the appropriate tooltip",
        (test_status: string | null, expected_tooltip: string, expected_icon: string) => {
            const wrapper = shallowMount(TestDefinitionCardStatusTooltipIcon, {
                props: {
                    test_definition: {
                        id: 123,
                        short_type: "test_def",
                        summary: "Test definition summary",
                        test_status: test_status,
                    } as TestDefinition,
                    backlog_item: { id: 456 } as BacklogItem,
                },
                global: {
                    ...getGlobalTestOptions({
                        state: {
                            milestone_title: "MyRelease",
                        } as RootState,
                    }),
                },
            });

            expect(wrapper.find("[data-test=test-status]").attributes("data-tlp-tooltip")).toBe(
                expected_tooltip,
            );
            expect(wrapper.find("[data-test=test-status-icon]").classes(expected_icon)).toBe(true);
        },
    );
});

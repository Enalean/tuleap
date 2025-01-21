/*
 * Copyright (c) Enalean, 2024-present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../helpers/global-options-for-tests";
import SwitchModeInput from "./SwitchModeInput.vue";
import { describe, expect, it } from "vitest";
import { DOCUMENTATION_BASE_URL, REPORT_ID } from "../injection-symbols";

describe("SwitchModeInput", () => {
    function getWrapper(
        is_in_expert_mode: boolean,
    ): VueWrapper<InstanceType<typeof SwitchModeInput>> {
        return shallowMount(SwitchModeInput, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [REPORT_ID.valueOf()]: 15,
                    [DOCUMENTATION_BASE_URL.valueOf()]: "/doc/en/",
                },
            },
            props: {
                is_in_expert_mode,
            },
        });
    }

    describe("Check at component creation", () => {
        it("already in checked state and the helper is displayed when the current report is expert mode", () => {
            const wrapper = getWrapper(true);

            const input: HTMLInputElement = wrapper.find("[data-test=switch-to-expert-input]")
                .element as HTMLInputElement;

            const is_helper_exist: boolean = wrapper
                .find("[data-test=documentation-helper]")
                .exists();

            expect(input.checked).toBe(true);
            expect(is_helper_exist).toBe(true);
        });

        it("not checked and the helper is not displayed when the current report is default mode", () => {
            const wrapper = getWrapper(false);

            const is_helper_exist: boolean = wrapper
                .find("[data-test=documentation-helper]")
                .exists();

            const input: HTMLInputElement = wrapper.find("[data-test=switch-to-expert-input]")
                .element as HTMLInputElement;
            expect(input.checked).toBe(false);
            expect(is_helper_exist).toBe(false);
        });
    });
});

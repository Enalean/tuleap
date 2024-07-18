/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import {
    injected_show_closed_pull_requests,
    InjectionSymbolsStub,
} from "../../../../tests/InjectionSymbolsStub";
import { getGlobalTestOptions } from "../../../../tests/global-options-for-tests";
import ClosedPullRequestsFilterSwitch from "./ClosedPullRequestsFilterSwitch.vue";

describe("ClosedPullRequestsFilterSwitch", () => {
    it("When the switch is toggled, then it should change the value of SHOW_CLOSED_PULL_REQUESTS", async () => {
        const wrapper = shallowMount(ClosedPullRequestsFilterSwitch, {
            global: {
                ...getGlobalTestOptions(),
                provide: InjectionSymbolsStub.withDefaults(),
            },
        });

        const status_switch = wrapper.find<HTMLInputElement>("[data-test=status-switch]");
        if (!status_switch) {
            throw new Error("Unable to find the status switch.");
        }

        await status_switch.setValue(true);
        expect(injected_show_closed_pull_requests.value).toBe(true);

        await status_switch.setValue(false);
        expect(injected_show_closed_pull_requests.value).toBe(false);
    });
});

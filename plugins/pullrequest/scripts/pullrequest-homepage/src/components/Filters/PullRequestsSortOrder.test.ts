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

import { shallowMount } from "@vue/test-utils";
import { describe, it, expect, vi } from "vitest";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import {
    injected_pull_requests_sort_order,
    StubInjectionSymbols,
} from "../../../tests/injection-symbols-stub";
import { SORT_ASCENDANT, SORT_DESCENDANT } from "../../injection-symbols";
import PullRequestsSortOrder from "./PullRequestsSortOrder.vue";

describe("PullRequestsSort", () => {
    it("When its value changes, then the PULL_REQUEST_SORT_ORDER should be updated with the currently selected value", () => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withDefaults(),
        );

        const wrapper = shallowMount(PullRequestsSortOrder, {
            global: {
                ...getGlobalTestOptions(),
            },
        });

        const select = wrapper.find<HTMLSelectElement>("[data-test=sort-order-select]");

        select.setValue(SORT_ASCENDANT);
        expect(injected_pull_requests_sort_order.value).toBe(SORT_ASCENDANT);

        select.setValue(SORT_DESCENDANT);
        expect(injected_pull_requests_sort_order.value).toBe(SORT_DESCENDANT);
    });
});

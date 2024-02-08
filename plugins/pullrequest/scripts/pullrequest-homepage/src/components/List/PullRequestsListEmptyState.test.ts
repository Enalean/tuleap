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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import * as strict_inject from "@tuleap/vue-strict-inject";
import {
    injected_show_closed_pull_requests,
    StubInjectionSymbols,
} from "../../../tests/injection-symbols-stub";
import { getGlobalTestOptions } from "../../../tests/global-options-for-tests";
import PullRequestsListEmptyState from "./PullRequestsListEmptyState.vue";

describe("PullRequestsListEmptyState", () => {
    let are_some_filters_defined: boolean;

    beforeEach(() => {
        are_some_filters_defined = false;

        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withDefaults(),
        );
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(PullRequestsListEmptyState, {
            global: {
                ...getGlobalTestOptions(),
            },
            props: {
                are_some_filters_defined,
            },
        });

    it('When some filters are defined, Then it should display the "No matching pull-requests" message', () => {
        are_some_filters_defined = true;

        expect(
            getWrapper().find("[data-test=empty-state-no-matching-pull-requests]").exists(),
        ).toBe(true);
        expect(getWrapper().find("[data-test=empty-state-no-open-pull-requests]").exists()).toBe(
            false,
        );
    });

    it('When no filters are defined and closed pull-requests are hidden, Then it should display the "No open pull-requests" message', () => {
        are_some_filters_defined = false;
        injected_show_closed_pull_requests.value = false;

        expect(
            getWrapper().find("[data-test=empty-state-no-matching-pull-requests]").exists(),
        ).toBe(false);
        expect(getWrapper().find("[data-test=empty-state-no-open-pull-requests]").exists()).toBe(
            true,
        );
    });
});

/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { describe, beforeEach, it, expect, vi } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import * as strict_inject from "@tuleap/vue-strict-inject";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import { StubInjectionSymbols } from "../../../tests/injection-symbols-stub";
import type { DisplayErrorCallback } from "../../injection-symbols";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import PullRequestsList from "./PullRequestsList.vue";

describe("PullRequestsList", () => {
    let tuleap_api_error_callback: DisplayErrorCallback;

    beforeEach(() => {
        tuleap_api_error_callback = vi.fn();
    });

    const getWrapper = (): VueWrapper => {
        vi.spyOn(strict_inject, "strictInject").mockImplementation(
            StubInjectionSymbols.withTuleapApiErrorCallback(tuleap_api_error_callback),
        );

        return shallowMount(PullRequestsList);
    };

    it("should load all the pull-requests of the repository and display them", async () => {
        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(
            okAsync([
                PullRequestStub.buildOpenPullRequest({ id: 6 }),
                PullRequestStub.buildOpenPullRequest({ id: 5 }),
                PullRequestStub.buildOpenPullRequest({ id: 3 }),
            ]),
        );

        const wrapper = getWrapper();

        await wrapper.vm.$nextTick();

        expect(wrapper.findAll("[data-test=pull-request-card]").length).toBe(3);
    });

    it("should call the tuleap_api_error_callback when an error occurres while the pull-requests are being retrieved", async () => {
        const tuleap_ap_fault = Fault.fromMessage("Nope");

        vi.spyOn(tuleap_api, "fetchAllPullRequests").mockReturnValue(errAsync(tuleap_ap_fault));

        const wrapper = getWrapper();
        await wrapper.vm.$nextTick();

        expect(tuleap_api_error_callback).toHaveBeenCalledOnce();
        expect(tuleap_api_error_callback).toHaveBeenCalledWith(tuleap_ap_fault);
    });
});

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

import { describe, it, expect, beforeEach, vi } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { okAsync } from "neverthrow";
import { PullRequestStub } from "@tuleap/plugin-pullrequest-stub";
import type { PullRequest } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as tuleap_api from "../../../api/tuleap-rest-querier";
import PullRequestLabels from "./PullRequestLabels.vue";

describe("PullRequestLabels", () => {
    let pull_request: PullRequest;

    beforeEach(() => {
        pull_request = PullRequestStub.buildOpenPullRequest();
    });

    const getWrapper = (): VueWrapper => {
        return shallowMount(PullRequestLabels, {
            props: {
                pull_request,
            },
        });
    };

    it("should not load the labels when the git reference of the pull-request is broken", () => {
        const fetchPullRequestLabels = vi.spyOn(tuleap_api, "fetchPullRequestLabels");

        pull_request = PullRequestStub.buildOpenPullRequest({ is_git_reference_broken: true });

        const wrapper = getWrapper();

        expect(fetchPullRequestLabels).not.toHaveBeenCalled();
        expect(wrapper.find("[data-test=pull-request-card-labels]").exists()).toBe(false);
    });

    it("should load the labels and display them with the right classes", async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(
            okAsync([
                { id: 1, label: "Salade", is_outline: true, color: "neon-green" },
                { id: 2, label: "Tomates", is_outline: true, color: "fiesta-red" },
                { id: 3, label: "Oignons", is_outline: false, color: "plum-crazy" },
            ]),
        );

        const wrapper = getWrapper();
        await wrapper.vm.$nextTick();
        const labels = wrapper.findAll("[data-test=pull-request-card-label]");

        expect(labels).toHaveLength(3);
        expect(labels[0].classes()).toStrictEqual(["tlp-badge-neon-green", "tlp-badge-outline"]);
        expect(labels[1].classes()).toStrictEqual(["tlp-badge-fiesta-red", "tlp-badge-outline"]);
        expect(labels[2].classes()).toStrictEqual(["tlp-badge-plum-crazy"]);
    });
});

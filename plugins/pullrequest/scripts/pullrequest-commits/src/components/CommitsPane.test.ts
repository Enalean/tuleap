/*
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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
import { shallowMount, flushPromises } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import type { RouteLocationNormalizedLoaded } from "vue-router";
import * as router from "vue-router";
import { okAsync, errAsync } from "neverthrow";
import { Fault } from "@tuleap/fault";
import { CommitStub } from "../../tests/stubs/CommitStub";
import * as tuleap_api from "../api/rest-querier";
import CommitsCardsSkeletons from "./commits/CommitsCardsSkeletons.vue";
import CommitCard from "./commits/CommitCard.vue";
import CommitsPane from "./CommitsPane.vue";

vi.mock("vue-router");

const pull_request_id = 15;

describe("CommitsPane", () => {
    const getWrapper = (): VueWrapper =>
        shallowMount(CommitsPane, {
            global: {
                plugins: [createGettext({ silent: true })],
            },
        });

    beforeEach(() => {
        vi.spyOn(router, "useRoute").mockImplementationOnce(
            () =>
                ({
                    params: {
                        id: String(pull_request_id),
                    },
                }) as unknown as RouteLocationNormalizedLoaded,
        );
    });

    it("Should load the pull-request's commits, then display them.", async () => {
        const commits = [
            CommitStub.withDefaults("f9b6ec23a9c1a1e8989a5dca335a86b435000d85"),
            CommitStub.withDefaults("d8fb8fc8e9d384402eec582fe504eae109f6fc9a"),
            CommitStub.withDefaults("4a178d8dc96b284801177865d5897da5e1ff8030"),
        ];
        vi.spyOn(tuleap_api, "fetchPullRequestCommits").mockReturnValue(okAsync(commits));

        const wrapper = getWrapper();

        expect(wrapper.findComponent(CommitsCardsSkeletons).exists()).toBe(true);

        await flushPromises();

        expect(wrapper.findComponent(CommitsCardsSkeletons).exists()).toBe(false);
        expect(wrapper.findAllComponents(CommitCard).length).toBe(commits.length);
    });

    it("Should display a warning when the pull-request has no commit.", async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestCommits").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();
        await flushPromises();

        expect(wrapper.findComponent(CommitsCardsSkeletons).exists()).toBe(false);
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(false);
        expect(wrapper.find("[data-test=no-commits-warning]").exists()).toBe(true);
    });

    it("Should display an error when the loading of the data has failed.", async () => {
        vi.spyOn(tuleap_api, "fetchPullRequestCommits").mockReturnValue(
            errAsync(Fault.fromMessage("Nope")),
        );

        const wrapper = getWrapper();
        await flushPromises();

        expect(wrapper.findComponent(CommitsCardsSkeletons).exists()).toBe(false);
        expect(wrapper.find("[data-test=no-commits-warning]").exists()).toBe(false);
        expect(wrapper.find("[data-test=error-message]").exists()).toBe(true);
    });
});

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

import { describe, it, expect } from "vitest";
import { mount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import PullRequestCreationDate from "./PullRequestCreationDate.vue";

describe("PullRequestCreationDate", () => {
    it("should display a skeleton while the pull request is loading, and the creation date when finished", async () => {
        const wrapper = mount(PullRequestCreationDate, {
            global: {
                stubs: {
                    PullRequestRelativeDate: true,
                },
                ...getGlobalTestOptions(),
            },
            props: {
                pull_request_info: null,
            },
        });

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);
        expect(wrapper.find("[data-test=pullrequest-creation-date]").exists()).toBe(false);

        wrapper.setProps({
            pull_request_info: {
                creation_date: "2023-02-17T11:00:00Z",
            },
        });

        await wrapper.vm.$nextTick();

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(wrapper.find("[data-test=pullrequest-creation-date]").exists()).toBe(true);
        expect(
            wrapper
                .find("[data-test=pullrequest-creation-date-as-relative-date]")
                .attributes("date"),
        ).toBe("2023-02-17T11:00:00Z");
    });
});

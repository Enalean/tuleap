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
import type { MockInstance } from "vitest";
import { okAsync, errAsync } from "neverthrow";
import { mount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import type { PullRequest, ProjectLabel } from "@tuleap/plugin-pullrequest-rest-api-types";
import * as tuleap_api from "../../api/tuleap-rest-querier";
import { getGlobalTestOptions } from "../../../tests/helpers/global-options-for-tests";
import { DISPLAY_TULEAP_API_ERROR, PULL_REQUEST_ID_KEY } from "../../constants";
import PullRequestLabels from "./PullRequestLabels.vue";

const pull_request_id = 50;
const labels: ProjectLabel[] = [
    {
        id: 1,
        label: "Emergency",
        is_outline: false,
        color: "red-wine",
    },
    {
        id: 2,
        label: "Easy fix",
        is_outline: true,
        color: "acid-green",
    },
];

vi.useFakeTimers();

describe("PullRequestLabels", () => {
    let display_error_callback: MockInstance,
        user_can_update_labels: boolean,
        is_git_reference_broken: boolean;

    const getWrapper = (): VueWrapper => {
        const wrapper = mount(PullRequestLabels, {
            global: {
                ...getGlobalTestOptions(),
                provide: {
                    [PULL_REQUEST_ID_KEY.valueOf()]: pull_request_id,
                    [DISPLAY_TULEAP_API_ERROR.valueOf()]: display_error_callback,
                },
            },
            props: {
                pull_request: null,
            },
        });

        wrapper.setProps({
            pull_request: {
                user_can_update_labels,
                is_git_reference_broken,
                repository: {
                    project: {
                        id: 102,
                    },
                },
            } as PullRequest,
        });

        return wrapper;
    };

    beforeEach(() => {
        display_error_callback = vi.fn();
        user_can_update_labels = true;
        is_git_reference_broken = false;
    });

    it("should display a skeleton while the labels are loading, and display them when it is done loading", async () => {
        vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(okAsync(labels));
        vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(okAsync(labels));

        const wrapper = getWrapper();

        await wrapper.vm.$nextTick();
        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(true);

        await vi.runOnlyPendingTimersAsync();
        const displayed_labels = wrapper.findAll("[data-test=pull-request-label]");

        expect(wrapper.find("[data-test=pullrequest-property-skeleton]").exists()).toBe(false);
        expect(displayed_labels).toHaveLength(labels.length);

        const [emergency_label, easy_fix_label] = displayed_labels;

        expect(emergency_label.classes("tlp-badge-outline")).toBe(false);
        expect(emergency_label.classes("tlp-badge-red-wine")).toBe(true);
        expect(emergency_label.text()).toBe("Emergency");

        expect(easy_fix_label.classes("tlp-badge-outline")).toBe(true);
        expect(easy_fix_label.classes("tlp-badge-acid-green")).toBe(true);
        expect(easy_fix_label.text()).toBe("Easy fix");
    });

    it("should display an empty state text when there are no labels assigned to the pull-request yet", async () => {
        vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(okAsync(labels));
        vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(okAsync([]));

        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=no-labels-yet-text]").exists()).toBe(true);
    });

    it.each([[false], [true]])(
        "should display the button to edit the labels only when the user_can_update_labels is %s",
        async (can_update_labels) => {
            vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(okAsync(labels));
            vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(okAsync([]));

            user_can_update_labels = can_update_labels;

            const wrapper = getWrapper();

            await vi.runOnlyPendingTimersAsync();

            expect(wrapper.find("[data-test=manage-labels-button]").exists()).toBe(
                can_update_labels,
            );
        },
    );

    it("should not display the button when the pull-request git reference is broken", async () => {
        vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(okAsync(labels));
        vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(okAsync([]));

        user_can_update_labels = true;
        is_git_reference_broken = true;

        const wrapper = getWrapper();

        await vi.runOnlyPendingTimersAsync();

        expect(wrapper.find("[data-test=manage-labels-button]").exists()).toBe(false);
    });

    describe("Errors", () => {
        let tuleap_api_fault: Fault;

        beforeEach(() => {
            tuleap_api_fault = Fault.fromMessage("Forbidden");
        });

        it("When an error occurs while retrieving the project labels, then it should trigger the display error callback", async () => {
            vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(errAsync(tuleap_api_fault));
            vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(okAsync([]));
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(display_error_callback).toHaveBeenCalledOnce();
            expect(display_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
        });

        it("When an error occurs while retrieving the pull-request's labels, then it should trigger the display error callback", async () => {
            vi.spyOn(tuleap_api, "fetchProjectLabels").mockReturnValue(okAsync(labels));
            vi.spyOn(tuleap_api, "fetchPullRequestLabels").mockReturnValue(
                errAsync(tuleap_api_fault),
            );
            getWrapper();
            await vi.runOnlyPendingTimersAsync();

            expect(display_error_callback).toHaveBeenCalledOnce();
            expect(display_error_callback).toHaveBeenCalledWith(tuleap_api_fault);
        });
    });
});

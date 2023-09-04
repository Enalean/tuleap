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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../tests/global-options-for-tests";
import { useSelectorsStore } from "../stores/selectors";
import { useDryRunStore } from "../stores/dry-run";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";

const getWrapper = (): VueWrapper =>
    shallowMount(MoveModalSelectors, {
        global: {
            ...getGlobalTestOptions(),
        },
    });

describe("MoveModalSelectors", () => {
    it.each([
        ["should not", true, false],
        ["should", false, true],
    ])(
        "%s display the selectors when are_projects_loading is %s.",
        async (what, are_projects_loading, expected) => {
            const wrapper = getWrapper();

            await useSelectorsStore().$patch({
                are_projects_loading,
                are_trackers_loading: false,
            });

            expect(wrapper.findComponent(ProjectSelector).exists()).toBe(expected);
            expect(wrapper.findComponent(TrackerSelector).exists()).toBe(expected);
        },
    );

    it.each([
        ["should", true],
        ["should not", false],
    ])(
        "%s display the spinner when are_trackers_loading is %s.",
        async (what, are_trackers_loading) => {
            const wrapper = getWrapper();

            await useSelectorsStore().$patch({
                are_projects_loading: false,
                are_trackers_loading,
            });

            expect(
                wrapper
                    .find("[data-test=move-modal-selectors-spinner]")
                    .classes("move-artifact-tracker-loader-spinner"),
            ).toBe(are_trackers_loading);
        },
    );

    it.each([
        ["The selectors container should have", true],
        ["The selectors container should not have", false],
    ])(
        "%s the preview class when has_processed_dry_run is %s",
        async (what, has_processed_dry_run) => {
            const wrapper = getWrapper();

            await useSelectorsStore().$patch({
                are_projects_loading: false,
                are_trackers_loading: false,
            });

            await useDryRunStore().$patch({
                has_processed_dry_run,
            });

            expect(
                wrapper
                    .find("[data-test=move-modal-selectors]")
                    .classes("move-artifact-selectors-preview"),
            ).toBe(has_processed_dry_run);
        },
    );
});

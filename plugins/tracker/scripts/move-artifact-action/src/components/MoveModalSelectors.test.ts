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

import type { Wrapper } from "@vue/test-utils";
import type { RootState } from "../store/types";

import { shallowMount } from "@vue/test-utils";
import { createStoreMock } from "@tuleap/vuex-store-wrapper-jest";
import MoveModalSelectors from "./MoveModalSelectors.vue";
import ProjectSelector from "./ProjectSelector.vue";
import TrackerSelector from "./TrackerSelector.vue";

const getWrapper = (state: RootState): Wrapper<MoveModalSelectors> =>
    shallowMount(MoveModalSelectors, {
        mocks: {
            $store: createStoreMock({
                state: state,
            }),
        },
    });

describe("MoveModalSelectors", () => {
    it.each([
        ["should not", true, false],
        ["should", false, true],
    ])(
        "%s display the selectors when is_loading_initial is %s.",
        (what, is_loading_initial, expected) => {
            const wrapper = getWrapper({
                is_loading_initial,
                are_trackers_loading: false,
                has_processed_dry_run: false,
            } as RootState);

            expect(wrapper.findComponent(ProjectSelector).exists()).toBe(expected);
            expect(wrapper.findComponent(TrackerSelector).exists()).toBe(expected);
        }
    );

    it.each([
        ["should", true],
        ["should not", false],
    ])("%s display the spinner when are_trackers_loading is %s.", (what, are_trackers_loading) => {
        const wrapper = getWrapper({
            is_loading_initial: false,
            are_trackers_loading,
            has_processed_dry_run: false,
        } as RootState);

        expect(
            wrapper
                .find("[data-test=move-modal-selectors-spinner]")
                .classes("move-artifact-tracker-loader-spinner")
        ).toBe(are_trackers_loading);
    });

    it.each([
        ["The selectors container should have", true],
        ["The selectors container should not have", false],
    ])("%s the preview class when has_processed_dry_run is %s", (what, has_processed_dry_run) => {
        const wrapper = getWrapper({
            is_loading_initial: false,
            are_trackers_loading: false,
            has_processed_dry_run,
        } as RootState);

        expect(
            wrapper
                .find("[data-test=move-modal-selectors]")
                .classes("move-artifact-selectors-preview")
        ).toBe(has_processed_dry_run);
    });
});

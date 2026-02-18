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
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import { CURRENT_USER_ID_KEY } from "../../constants";
import * as tuleap_api from "../../api/rest-querier";
import type { PullRequestDiffMode } from "./diff-modes";
import { SIDE_BY_SIDE_DIFF, UNIFIED_DIFF } from "./diff-modes";
import FileDiffTypeSelector from "./FileDiffTypeSelector.vue";

const current_user_id = 102;

describe("FileDiffTypeSelector", () => {
    let current_diff_mode: PullRequestDiffMode;

    beforeEach(() => {
        current_diff_mode = SIDE_BY_SIDE_DIFF;
    });

    const getWrapper = (): VueWrapper =>
        shallowMount(FileDiffTypeSelector, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [CURRENT_USER_ID_KEY.valueOf()]: current_user_id,
                },
            },
            propsData: {
                current_diff_mode,
            },
        });

    it.each([
        [SIDE_BY_SIDE_DIFF, "side-by-side-diff-button"],
        [UNIFIED_DIFF, "unified-diff-button"],
    ])(
        "When the current diff mode is '%s', then the %s input should be checked",
        (active_mode, input_id) => {
            current_diff_mode = active_mode;

            const input = getWrapper().find(`#${input_id}`);

            expect(input.attributes("checked")).toBeDefined();
        },
    );

    it.each([
        ["unified-diff-button", UNIFIED_DIFF],
        ["side-by-side-diff-button", SIDE_BY_SIDE_DIFF],
    ])(
        `When the %s is clicked, then:
        - a 'diff-mode-changed' event should be emitted with the %s diff mode
        - the corresponding user preference should be updated
    `,
        (button_selector, expected_mode) => {
            current_diff_mode = expected_mode === UNIFIED_DIFF ? SIDE_BY_SIDE_DIFF : UNIFIED_DIFF;

            vi.spyOn(tuleap_api, "setUserPreferenceForDiffDisplayMode");

            const wrapper = getWrapper();

            wrapper.find(`[data-test=${button_selector}]`).trigger("click");
            expect(wrapper.emitted("diff-mode-changed")).toStrictEqual([[expected_mode]]);
            expect(tuleap_api.setUserPreferenceForDiffDisplayMode).toHaveBeenCalledWith(
                current_user_id,
                expected_mode,
            );
        },
    );
});

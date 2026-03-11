/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import { describe, it, expect, vi } from "vitest";
import { computed, ref } from "vue";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { createGettext } from "vue3-gettext";
import CreatePullrequestButton from "./CreatePullrequestButton.vue";
import { CAN_CREATE_PULLREQUEST, HAS_ERROR_WHILE_LOADING_BRANCHES } from "../injection-keys";

function createWrapper(
    can_create_pullrequest: boolean,
    has_error_while_loading_branches: boolean,
): VueWrapper {
    const show_modal = vi.fn();
    return shallowMount(CreatePullrequestButton, {
        props: { showModal: show_modal },
        global: {
            plugins: [createGettext({ silent: true })],
            provide: {
                [CAN_CREATE_PULLREQUEST.valueOf()]: computed(() => can_create_pullrequest),
                [HAS_ERROR_WHILE_LOADING_BRANCHES.valueOf()]: ref(has_error_while_loading_branches),
            },
        },
    });
}

describe("CreatePullrequestButton", () => {
    it("is disabled when no pull request can be created and there is no loading error", () => {
        const wrapper = createWrapper(false, false);

        expect(
            wrapper.find("[data-test=create-pull-request]").attributes("disabled"),
        ).toBeDefined();
    });

    it("is enabled when a pull request can be created", () => {
        const wrapper = createWrapper(true, false);

        expect(
            wrapper.find("[data-test=create-pull-request]").attributes("disabled"),
        ).toBeUndefined();
    });

    it("is enabled when an error occurred while loading branches", () => {
        const wrapper = createWrapper(false, true);

        expect(
            wrapper.find("[data-test=create-pull-request]").attributes("disabled"),
        ).toBeUndefined();
    });

    it("shows an explanation message when the button is disabled", () => {
        const wrapper = createWrapper(false, false);

        expect(wrapper.find("[data-test=create-pull-request]").attributes("title")).toBe(
            "No pull request can currently be created",
        );
    });

    it("has no title when the button is enabled", () => {
        const wrapper = createWrapper(true, false);

        expect(wrapper.find("[data-test=create-pull-request]").attributes("title")).toBe("");
    });
});

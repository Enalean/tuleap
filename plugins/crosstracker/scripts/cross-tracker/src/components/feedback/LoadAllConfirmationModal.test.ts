/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

import { describe, expect, it } from "vitest";
import { shallowMount } from "@vue/test-utils";
import type { VueWrapper } from "@vue/test-utils";
import { getGlobalTestOptions } from "../../helpers/global-options-for-tests";
import LoadAllConfirmationModal from "./LoadAllConfirmationModal.vue";

describe("LoadAllConfirmationModal", () => {
    function getWrapper(): VueWrapper {
        return shallowMount(LoadAllConfirmationModal, {
            global: {
                ...getGlobalTestOptions(),
            },
        });
    }

    it("should emit an should-load-all event with true, if 'load all' button is clicked", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=modal-action-button]").trigger("click");

        const should_load_all_event = wrapper.emitted("should-load-all");
        if (!should_load_all_event) {
            throw new Error("Expected a should-load-all event");
        }

        expect(should_load_all_event[0]).toStrictEqual([true]);
    });

    it("should emit an should-load-all event with false, if 'cancel' button is clicked", () => {
        const wrapper = getWrapper();
        wrapper.find("[data-test=modal-cancel-button]").trigger("click");

        const should_load_all_event = wrapper.emitted("should-load-all");
        if (!should_load_all_event) {
            throw new Error("Expected a should-load-all event");
        }

        expect(should_load_all_event[0]).toStrictEqual([false]);
    });
});

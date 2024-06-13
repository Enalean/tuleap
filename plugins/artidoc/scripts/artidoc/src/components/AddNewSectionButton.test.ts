/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
import AddNewSectionButton from "@/components/AddNewSectionButton.vue";
import { CONFIGURATION_STORE } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { mockStrictInject } from "@/helpers/mock-strict-inject";
import {
    OPEN_CONFIGURATION_MODAL_BUS,
    useOpenConfigurationModalBus,
} from "@/composables/useOpenConfigurationModalBus";
import { createGettext } from "vue3-gettext";

describe("AddNewSectionButton", () => {
    describe("when the tracker is not configured", () => {
        it("should ask to open the configuration modal on click", async () => {
            let has_been_called = false;

            const bus = useOpenConfigurationModalBus();
            bus.registerHandler(() => {
                has_been_called = true;
            });

            mockStrictInject([
                [CONFIGURATION_STORE, ConfigurationStoreStub.withSelectedTracker(0)],
                [OPEN_CONFIGURATION_MODAL_BUS, bus],
            ]);

            const wrapper = shallowMount(AddNewSectionButton, {
                global: { plugins: [createGettext({ silent: true })] },
            });

            await wrapper.find("button").trigger("click");

            expect(has_been_called).toBe(true);
        });
    });
});

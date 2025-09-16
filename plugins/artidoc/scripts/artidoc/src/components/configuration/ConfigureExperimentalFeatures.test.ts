/*
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

import { shallowMount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import { ref } from "vue";
import ConfigureExperimentalFeatures from "@/components/configuration/ConfigureExperimentalFeatures.vue";
import { ARE_VERSIONS_DISPLAYED } from "@/can-user-display-versions-injection-key";
import { createGettext } from "vue3-gettext";
import { CLOSE_CONFIGURATION_MODAL } from "@/components/configuration/configuration-modal";

describe("ConfigureExperimentalFeatures", () => {
    it("when user decides to display the versions, then the injected variable is updated", async () => {
        const are_versions_displayed = ref(false);

        const wrapper = shallowMount(ConfigureExperimentalFeatures, {
            global: {
                plugins: [createGettext({ silent: true })],
                provide: {
                    [ARE_VERSIONS_DISPLAYED.valueOf()]: are_versions_displayed,
                    [CLOSE_CONFIGURATION_MODAL.valueOf()]: () => {},
                },
            },
        });

        await wrapper.find("[data-test=switch]").trigger("change");

        expect(are_versions_displayed.value).toBe(true);
    });
});

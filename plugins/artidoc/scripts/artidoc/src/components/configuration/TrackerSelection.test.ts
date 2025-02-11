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
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { shallowMount } from "@vue/test-utils";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import { createGettext } from "vue3-gettext";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";

describe("TrackerSelection", () => {
    it("should display error if there is no allowed trackers", () => {
        const wrapper = shallowMount(TrackerSelection, {
            props: {
                configuration_helper: useConfigurationScreenHelper(
                    ConfigurationStoreStub.withoutAllowedTrackers(),
                ),
                disabled: false,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(
            wrapper.find("[data-test=artidoc-configuration-form-element-trackers]").classes(),
        ).toContain("tlp-form-element-error");
    });
});

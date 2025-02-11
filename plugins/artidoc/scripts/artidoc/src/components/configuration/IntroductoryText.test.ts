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
import type { Tracker } from "@/stores/configuration-store";
import { ConfigurationStoreStub } from "@/helpers/stubs/ConfigurationStoreStub";
import { shallowMount } from "@vue/test-utils";
import { useConfigurationScreenHelper } from "@/composables/useConfigurationScreenHelper";
import { createGettext } from "vue3-gettext";
import IntroductoryText from "@/components/configuration/IntroductoryText.vue";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

describe("IntroductoryText", () => {
    it.each<[Tracker, boolean]>([
        [TrackerStub.withoutTitleAndDescription(), true],
        [TrackerStub.withTitle(), true],
        [TrackerStub.withDescription(), true],
        [TrackerStub.withTitleAndDescription(), false],
    ])(
        `Given the tracker %s Then warning will be displayed = %s`,
        (tracker: Tracker, expected: boolean) => {
            const wrapper = shallowMount(IntroductoryText, {
                props: {
                    configuration_helper: useConfigurationScreenHelper(
                        ConfigurationStoreStub.withSelectedTracker(tracker),
                    ),
                },
                global: { plugins: [createGettext({ silent: true })] },
            });

            expect(wrapper.find("[data-test=warning]").exists()).toBe(expected);
        },
    );
});

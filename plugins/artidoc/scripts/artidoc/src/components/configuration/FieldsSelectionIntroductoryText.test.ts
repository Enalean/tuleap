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
import { createGettext } from "vue3-gettext";
import { shallowMount } from "@vue/test-utils";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import FieldsSelectionIntroductoryText from "@/components/configuration/FieldsSelectionIntroductoryText.vue";

describe("FieldsSelectionIntroductoryText", () => {
    it("should display the tracker label in the information message", () => {
        const tracker = TrackerStub.build(3, "Hello");
        const wrapper = shallowMount(FieldsSelectionIntroductoryText, {
            props: {
                tracker: tracker,
            },
            global: { plugins: [createGettext({ silent: true })] },
        });

        expect(wrapper.find("[data-test=tracker-information]").text().includes(tracker.label)).toBe(
            true,
        );
    });
});

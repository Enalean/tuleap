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
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import { createGettext } from "vue3-gettext";
import { Option } from "@tuleap/option";
import TrackerSelectionIntroductoryText from "@/components/configuration/TrackerSelectionIntroductoryText.vue";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

describe("TrackerSelectionIntroductoryText", () => {
    function getWrapper(selected_tracker: Tracker): VueWrapper {
        return shallowMount(TrackerSelectionIntroductoryText, {
            global: { plugins: [createGettext({ silent: true })] },
            props: { selected_tracker: Option.fromValue(selected_tracker) },
        });
    }

    function* generateTrackersWithMissingSemantics(): Generator<Tracker> {
        yield TrackerStub.withoutTitleAndDescription();
        yield TrackerStub.withTitle();
        yield TrackerStub.withDescription();
    }

    it.each([...generateTrackersWithMissingSemantics()])(
        `Given trackers with missing semantics, it will display a warning`,
        (tracker: Tracker) => {
            const wrapper = getWrapper(tracker);
            expect(wrapper.find("[data-test=warning]").exists()).toBe(true);
        },
    );

    it(`When the tracker has a title and description, then no warning will be shown`, () => {
        const wrapper = getWrapper(TrackerStub.withTitleAndDescription());
        expect(wrapper.find("[data-test=warning]").exists()).toBe(false);
    });
});

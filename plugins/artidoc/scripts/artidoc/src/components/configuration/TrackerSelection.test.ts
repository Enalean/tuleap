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

import { beforeEach, describe, expect, it } from "vitest";
import { createGettext } from "vue3-gettext";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";
import { Option } from "@tuleap/option";
import TrackerSelection from "@/components/configuration/TrackerSelection.vue";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import { buildAllowedTrackersCollection } from "@/configuration/AllowedTrackersCollection";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";

describe("TrackerSelection", () => {
    let allowed_trackers: Tracker[], is_tracker_selection_disabled: boolean;

    beforeEach(() => {
        allowed_trackers = [TrackerStub.withTitleAndDescription()];
        is_tracker_selection_disabled = false;
    });

    function getWrapper(): VueWrapper {
        return shallowMount(TrackerSelection, {
            global: { plugins: [createGettext({ silent: true })] },
            props: {
                allowed_trackers: buildAllowedTrackersCollection(allowed_trackers),
                selected_tracker: Option.nothing<Tracker>(),
                is_tracker_selection_disabled,
            },
        });
    }

    it("should display error if there is no allowed trackers", () => {
        allowed_trackers = [];
        const wrapper = getWrapper();

        expect(
            wrapper.find("[data-test=artidoc-configuration-form-element-trackers]").classes(),
        ).toContain("tlp-form-element-error");
    });

    it("should display text information as long as the tracker selection can be saved", () => {
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=information-message]").exists()).toBe(true);
    });

    it("should not display text information if the tracker selection is saved", () => {
        is_tracker_selection_disabled = true;
        const wrapper = getWrapper();

        expect(wrapper.find("[data-test=information-message]").exists()).toBe(false);
    });

    it(`emits an event when the selected tracker changes`, async () => {
        const wrapper = getWrapper();
        await wrapper
            .get("[data-test=artidoc-configuration-tracker]")
            .setValue(allowed_trackers[0]);
        const emitted_event = wrapper.emitted("select-tracker");
        if (emitted_event === undefined) {
            throw Error("Expected event to be emitted");
        }
        const option = emitted_event[0][0] as Option<Tracker>;
        expect(option.unwrapOr(null)).toStrictEqual(allowed_trackers[0]);
    });
});

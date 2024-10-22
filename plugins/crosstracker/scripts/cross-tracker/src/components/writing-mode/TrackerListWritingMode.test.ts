/*
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
import type { TrackerToUpdate } from "../../type";
import TrackerListWritingMode from "./TrackerListWritingMode.vue";

describe("TrackerListWritingMode", () => {
    function instantiateComponent(): VueWrapper<InstanceType<typeof TrackerListWritingMode>> {
        return shallowMount(TrackerListWritingMode, {
            props: {
                trackers: [
                    { tracker_label: "fake_tracker", tracker_id: 1 } as TrackerToUpdate,
                    { tracker_label: "bugs", tracker_id: 2 } as TrackerToUpdate,
                ],
            },
        });
    }

    it("when I remove a tracker, then an event will be emitted", () => {
        const wrapper = instantiateComponent();
        const tracker = { tracker_label: "fake_tracker", tracker_id: 1 } as TrackerToUpdate;

        wrapper.get("[data-test=remove-tracker]").trigger("click");

        const emitted = wrapper.emitted("tracker-removed");
        if (!emitted) {
            throw new Error("Event has not been emitted");
        }
        expect(emitted[0][0]).toStrictEqual(tracker);
    });
});

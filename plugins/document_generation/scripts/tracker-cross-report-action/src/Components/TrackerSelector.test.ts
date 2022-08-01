/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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
import { flushPromises, shallowMount } from "@vue/test-utils";
import TrackerSelector from "./TrackerSelector.vue";
import { getGlobalTestOptions } from "./global-options-for-test";
import * as rest_querier from "../rest-querier";
import type { MinimalTrackerResponse } from "@tuleap/plugin-tracker-rest-api-types/src";

const tracker_a = {
    id: 2,
    label: "Tracker A",
};
const trackers: MinimalTrackerResponse[] = [
    tracker_a,
    {
        id: 5,
        label: "Tracker B",
    },
];

describe("ProjectSelector", () => {
    it("displays possible trackers", async () => {
        vi.spyOn(rest_querier, "getTrackers").mockResolvedValue(trackers);

        const wrapper = shallowMount(TrackerSelector, {
            global: getGlobalTestOptions(),
            props: {
                project_id: 102,
                tracker: null,
            },
        });

        await flushPromises();

        const selector = wrapper.get("select");

        expect(selector.findAll("option")).toHaveLength(2);
        expect(selector.element.disabled).toBe(false);
    });

    it("disables the selector if no project is provided", async () => {
        const get_trackers_api_spy = vi.spyOn(rest_querier, "getTrackers");

        const wrapper = shallowMount(TrackerSelector, {
            global: getGlobalTestOptions(),
            props: {
                project_id: null,
                tracker: null,
            },
        });

        await flushPromises();

        const selector = wrapper.get("select");

        expect(selector.element.disabled).toBe(true);
        expect(get_trackers_api_spy).not.toBeCalled();
    });

    it("returns selected tracker", async () => {
        vi.spyOn(rest_querier, "getTrackers").mockResolvedValue(trackers);

        const wrapper = shallowMount(TrackerSelector, {
            global: getGlobalTestOptions(),
            props: {
                project_id: 102,
                tracker: null,
            },
        });

        await flushPromises();

        const selector = wrapper.get("select");

        await selector.setValue(tracker_a);

        const emitted_input = wrapper.emitted("update:tracker");
        expect(emitted_input).toBeDefined();
        if (emitted_input === undefined) {
            throw new Error("Expected an update event to be emitted");
        }
        expect(emitted_input[0]).toStrictEqual([tracker_a]);
    });
});

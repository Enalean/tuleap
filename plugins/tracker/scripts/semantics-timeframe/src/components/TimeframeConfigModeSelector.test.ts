/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

import { describe, it, expect } from "vitest";
import type { VueWrapper } from "@vue/test-utils";
import { shallowMount } from "@vue/test-utils";

import TimeframeConfigModeSelector from "./TimeframeConfigModeSelector.vue";
import { MODE_BASED_ON_TRACKER_FIELDS, MODE_IMPLIED_FROM_ANOTHER_TRACKER } from "../constants";
import { createGettext } from "vue3-gettext";

describe("TimeframeConfigModeSelector", () => {
    function getWrapper(
        is_implied: boolean,
        should_send_event_in_notification: boolean,
        has_other_trackers_implying_their_timeframes: boolean,
    ): VueWrapper<InstanceType<typeof TimeframeConfigModeSelector>> {
        return shallowMount(TimeframeConfigModeSelector, {
            global: { plugins: [createGettext({ silent: true })] },
            props: {
                implied_from_tracker_id: is_implied ? 150 : "",
                should_send_event_in_notification,
                has_other_trackers_implying_their_timeframes,
            },
        });
    }

    function getTimeframeModeSelectBox(
        wrapper: VueWrapper<InstanceType<typeof TimeframeConfigModeSelector>>,
    ): HTMLSelectElement {
        const select_box = wrapper.find("[data-test=timeframe-mode-select-box]").element;
        if (!(select_box instanceof HTMLSelectElement)) {
            throw new Error("<select> not found");
        }
        return select_box;
    }

    it("should display the inherited mode when there is an implied_from_tracker_id", async () => {
        const wrapper = await getWrapper(true, false, false);
        const select_box = getTimeframeModeSelectBox(wrapper);

        expect(select_box.value).toStrictEqual(MODE_IMPLIED_FROM_ANOTHER_TRACKER);
    });

    it("should display the based on tracker fields mode", async () => {
        const wrapper = await getWrapper(false, false, false);
        const select_box = getTimeframeModeSelectBox(wrapper);

        expect(select_box.value).toStrictEqual(MODE_BASED_ON_TRACKER_FIELDS);
    });

    it("should display the inherited mode as disabled when calendar events are used", async () => {
        const wrapper = await getWrapper(false, true, false);

        expect(
            wrapper
                .find("[data-test=timeframe-mode-implied-from-another-tracker]")
                .attributes("disabled"),
        ).toBeDefined();
    });

    it("should display the inherited mode as disabled when other trackers implying their timeframes", async () => {
        const wrapper = await getWrapper(false, false, true);

        expect(
            wrapper
                .find("[data-test=timeframe-mode-implied-from-another-tracker]")
                .attributes("disabled"),
        ).toBeDefined();
    });

    it("should not display the inherited mode as disabled when calendar events are not used", async () => {
        const wrapper = await getWrapper(false, false, false);

        expect(
            wrapper
                .find("[data-test=timeframe-mode-implied-from-another-tracker]")
                .attributes("disabled"),
        ).toBeUndefined();
    });

    it("should emit an event each time a new mode is selected", async () => {
        const wrapper = await getWrapper(false, false, false);
        expect(getEmittedEventValueAt(0)).toBe(MODE_BASED_ON_TRACKER_FIELDS);

        await wrapper
            .find("[data-test=timeframe-mode-select-box]")
            .setValue(MODE_IMPLIED_FROM_ANOTHER_TRACKER);
        expect(getEmittedEventValueAt(1)).toBe(MODE_IMPLIED_FROM_ANOTHER_TRACKER);

        await wrapper
            .find("[data-test=timeframe-mode-select-box]")
            .setValue(MODE_BASED_ON_TRACKER_FIELDS);
        expect(getEmittedEventValueAt(2)).toBe(MODE_BASED_ON_TRACKER_FIELDS);

        function getEmittedEventValueAt(n: number): string {
            const emitted_event = wrapper.emitted<string>("timeframe-mode-selected");
            if (emitted_event === undefined) {
                throw Error("Event not emitted");
            }

            return emitted_event[n][0];
        }
    });
});

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

import { describe, it, vi, expect, beforeEach } from "vitest";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { ConfigurationStore } from "@/stores/configuration-store";
import { initConfigurationStore } from "@/stores/configuration-store";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import * as rest from "@/helpers/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import { flushPromises } from "@vue/test-utils";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { buildSelectedTracker } from "@/configuration/SelectedTracker";

const tracker_for_fields = {
    fields: [],
    semantics: { title: { field_id: 100 } },
};

describe("configuration-store", () => {
    let bugs: Tracker, selected_tracker: SelectedTrackerRef, store: ConfigurationStore;

    beforeEach(() => {
        bugs = TrackerStub.build(101, "Bugs");
        selected_tracker = buildSelectedTracker(Option.nothing());
        store = initConfigurationStore(1, selected_tracker, []);
    });

    describe("saveTrackerConfiguration", () => {
        it("should save the new tracker configuration", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(okAsync(tracker_for_fields));

            expect(selected_tracker.value.isNothing()).toBe(true);

            store.saveTrackerConfiguration(bugs);
            await flushPromises();

            expect(selected_tracker.value.unwrapOr(null)).toBe(bugs);
            expect(store.is_success.value).toBe(true);
            expect(store.is_error.value).toBe(false);
        });

        it("should display the error if putConfiguration returns an error", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            expect(selected_tracker.value.isNothing()).toBe(true);

            store.saveTrackerConfiguration(bugs);
            await flushPromises();

            expect(selected_tracker.value.isNothing()).toBe(true);
            expect(store.is_success.value).toBe(false);
            expect(store.is_error.value).toBe(true);
            expect(store.error_message.value).toBe("Bad request");
        });

        it("should display the error if getAvailableFields returns an error", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );
            expect(selected_tracker.value.isNothing()).toBe(true);

            store.saveTrackerConfiguration(bugs);
            await flushPromises();

            expect(selected_tracker.value.isNothing()).toBe(true);
            expect(store.is_success.value).toBe(false);
            expect(store.is_error.value).toBe(true);
            expect(store.error_message.value).toBe("Bad request");
        });
    });

    describe("saveFieldsConfiguration", () => {
        const fields = [ConfigurationFieldStub.build(), ConfigurationFieldStub.build()];

        it("should save the new fields configuration", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(okAsync(tracker_for_fields));

            selected_tracker.value = Option.fromValue(bugs);
            expect(store.selected_fields.value).toStrictEqual([]);

            store.saveFieldsConfiguration(fields);
            await flushPromises();

            expect(store.selected_fields.value).toStrictEqual(fields);
            expect(store.is_success.value).toBe(true);
            expect(store.is_error.value).toBe(false);
        });
    });

    describe("resetSuccessFlagFromPreviousCalls", () => {
        it("should put the success flag to false", () => {
            store.is_success.value = true;

            store.resetSuccessFlagFromPreviousCalls();

            expect(store.is_success.value).toBe(false);
        });
    });
});

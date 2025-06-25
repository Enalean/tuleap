/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { beforeEach, describe, expect, it, vi } from "vitest";
import * as rest from "@/helpers/rest-querier";
import { errAsync, okAsync } from "neverthrow";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { buildSelectedTracker } from "@/configuration/SelectedTracker";
import type { SelectedFieldsCollection } from "@/configuration/SelectedFieldsCollection";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import { ref } from "vue";
import { Fault } from "@tuleap/fault";
import { Option } from "@tuleap/option";
import type { SaveTrackerConfiguration } from "@/configuration/TrackerConfigurationSaver";
import { buildTrackerConfigurationSaver } from "@/configuration/TrackerConfigurationSaver";

describe("TrackerConfigurationSaver", () => {
    let bugs: Tracker, selected_tracker: SelectedTrackerRef, saver: SaveTrackerConfiguration;
    let selected_fields: SelectedFieldsCollection;
    const tracker_for_fields = {
        fields: [],
        semantics: { title: { field_id: 100 } },
    };

    beforeEach(() => {
        bugs = TrackerStub.build(101, "Bugs");
        selected_tracker = buildSelectedTracker(Option.nothing());
        selected_fields = ref([]);
        saver = buildTrackerConfigurationSaver(1, selected_tracker, selected_fields, ref([]));
    });

    describe("saveTrackerConfiguration", () => {
        it("should save the new tracker configuration", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(okAsync(tracker_for_fields));

            expect(selected_tracker.value.isNothing()).toBe(true);

            const result = await saver.saveTrackerConfiguration(bugs);

            expect(selected_tracker.value.unwrapOr(null)).toBe(bugs);
            expect(result.isOk()).toBe(true);
        });

        it("should display the error if putConfiguration returns an error", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );

            expect(selected_tracker.value.isNothing()).toBe(true);

            const result = await saver.saveTrackerConfiguration(bugs);

            expect(selected_tracker.value.isNothing()).toBe(true);
            expect(result.isErr()).toBe(true);
            result.mapErr((fault) => expect(fault.toString()).toStrictEqual("Bad request"));
        });

        it("should display the error if getAvailableFields returns an error", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(
                errAsync(Fault.fromMessage("Bad request")),
            );
            expect(selected_tracker.value.isNothing()).toBe(true);

            const result = await saver.saveTrackerConfiguration(bugs);

            expect(selected_tracker.value.isNothing()).toBe(true);
            expect(result.isErr()).toBe(true);
            result.mapErr((fault) => expect(fault.toString()).toStrictEqual("Bad request"));
        });
    });
});

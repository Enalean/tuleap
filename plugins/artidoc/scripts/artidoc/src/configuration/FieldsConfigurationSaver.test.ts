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
import { ConfigurationFieldStub } from "@/sections/stubs/ConfigurationFieldStub";
import * as rest from "@/helpers/rest-querier";
import { okAsync } from "neverthrow";
import type { Tracker } from "@/configuration/AllowedTrackersCollection";
import type { SelectedTrackerRef } from "@/configuration/SelectedTracker";
import { buildSelectedTracker } from "@/configuration/SelectedTracker";
import type { SelectedFieldsCollection } from "@/configuration/SelectedFieldsCollection";
import { TrackerStub } from "@/helpers/stubs/TrackerStub";
import { ref } from "vue";
import { Option } from "@tuleap/option";
import type { SaveFieldsConfiguration } from "@/configuration/FieldsConfigurationSaver";
import { buildFieldsConfigurationSaver } from "@/configuration/FieldsConfigurationSaver";

describe("FieldsConfigurationSaver", () => {
    let bugs: Tracker, selected_tracker: SelectedTrackerRef, saver: SaveFieldsConfiguration;
    let selected_fields: SelectedFieldsCollection;
    const tracker_for_fields = {
        fields: [],
        semantics: { title: { field_id: 100 } },
    };

    beforeEach(() => {
        bugs = TrackerStub.build(101, "Bugs");
        selected_tracker = buildSelectedTracker(Option.nothing());
        selected_fields = ref([]);
        saver = buildFieldsConfigurationSaver(1, selected_tracker, selected_fields, ref([]));
    });

    describe("saveFieldsConfiguration", () => {
        const fields = [ConfigurationFieldStub.build(), ConfigurationFieldStub.build()];

        it("should save the new fields configuration", async () => {
            vi.spyOn(rest, "putConfiguration").mockReturnValue(okAsync(new Response()));
            vi.spyOn(rest, "getTracker").mockReturnValue(okAsync(tracker_for_fields));

            selected_tracker.value = Option.fromValue(bugs);
            expect(selected_fields.value).toStrictEqual([]);

            const result = await saver.saveFieldsConfiguration(fields);

            expect(selected_fields.value).toStrictEqual(fields);
            expect(result.isOk()).toBe(true);
        });
    });
});

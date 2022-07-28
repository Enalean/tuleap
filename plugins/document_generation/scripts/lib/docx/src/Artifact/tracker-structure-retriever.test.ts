/**
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

import { describe, it, expect, vi } from "vitest";
import * as tlp from "@tuleap/tlp-fetch";
import type { TrackerDefinition } from "../type";
import { mockFetchSuccess } from "@tuleap/tlp-fetch/mocks/tlp-fetch-mock-helper";
import { getTrackerDefinition, retrieveTrackerStructure } from "./tracker-structure-retriever";

vi.mock("@tuleap/tlp-fetch");

describe("tracker-structure-retriever", () => {
    describe("retrieveTrackerStructure", () => {
        it("Given a tracrek id, Then it will get the tracker structure", async () => {
            const tracker_id = 101;
            const tlpGet = vi.spyOn(tlp, "get");

            const tracker_definition_response: TrackerDefinition = {
                id: 102,
                label: "Label",
                item_name: "shortname",
                fields: [{ field_id: 2, type: "date", is_time_displayed: false }],
                structure: [
                    {
                        id: 4,
                        content: [{ id: 2, content: null }],
                    },
                ],
            };
            mockFetchSuccess(tlpGet, {
                return_json: tracker_definition_response,
            });

            const structure = await retrieveTrackerStructure(tracker_id);
            expect(structure.fields.size).toBe(1);
            expect(structure.fields.get(2)).toStrictEqual({
                field_id: 2,
                type: "date",
                is_time_displayed: false,
            });
            expect(structure.disposition).toStrictEqual([
                {
                    id: 4,
                    content: [{ id: 2, content: null }],
                },
            ]);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/trackers/101");
        });
    });
    describe("getTrackerDefinition", () => {
        it("Given a tracker id, Then it will get the tracker definition", async () => {
            const tracker_id = 101;
            const tlpGet = vi.spyOn(tlp, "get");

            const tracker_definition_response: TrackerDefinition = {
                id: 102,
                label: "Label",
                item_name: "shortname",
                fields: [{ field_id: 2, type: "date", is_time_displayed: false }],
                structure: [
                    {
                        id: 4,
                        content: [{ id: 2, content: null }],
                    },
                ],
            };
            mockFetchSuccess(tlpGet, {
                return_json: tracker_definition_response,
            });

            await getTrackerDefinition(tracker_id);

            expect(tlpGet).toHaveBeenCalledWith("/api/v1/trackers/101");
        });
    });
});

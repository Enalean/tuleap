/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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
import type { Tracker } from "../store/types";
import { TrackerSelectorOptions } from "./TrackerSelectorOptions";

describe("TrackerSelectorOptions", () => {
    it("should return a list of trackers select options with the current one disabled", () => {
        const current_tracker_id = 11;
        const trackers: Tracker[] = [
            {
                id: 10,
                label: "Tasks",
            },
            {
                id: current_tracker_id,
                label: "User stories",
            },
        ];

        const options = TrackerSelectorOptions.fromTrackers(trackers, current_tracker_id);

        expect(options).toHaveLength(trackers.length);
        expect(options[0].disabled).toBe(false);
        expect(options[1].disabled).toBe(true);
    });
});

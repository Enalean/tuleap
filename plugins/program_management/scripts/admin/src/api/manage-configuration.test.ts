/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { describe, expect, it, vi } from "vitest";
import { saveConfiguration } from "./manage-configuration";
import type { ProgramConfiguration } from "../type";
import * as tlp from "@tuleap/tlp-fetch";

describe("manageConfiguration", () => {
    describe("saveConfiguration", () => {
        it("Given configuration, Then API is called", () => {
            const configuration: ProgramConfiguration = {
                permissions: { can_prioritize_features: ["105_3"] },
                plannable_tracker_ids: [8, 9],
                program_increment_tracker_id: 50,
                program_id: 105,
                iteration: {
                    iteration_tracker_id: 125,
                },
            };
            const put = vi.spyOn(tlp, "put");

            saveConfiguration(configuration);

            expect(put).toHaveBeenCalledWith("/api/v1/projects/105/program_plan", {
                body: '{"program_increment_tracker_id":50,"plannable_tracker_ids":[8,9],"permissions":{"can_prioritize_features":["105_3"]},"iteration":{"iteration_tracker_id":125}}',
                headers: { "Content-Type": "application/json" },
            });
        });
    });
});

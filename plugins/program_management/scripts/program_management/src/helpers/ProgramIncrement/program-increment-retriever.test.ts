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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import type { ProgramIncrement } from "./program-increment-retriever";
import { getProgramIncrements } from "./program-increment-retriever";

describe("Tracker reports retriever", () => {
    it("retrieves tracker reports", async () => {
        const recursiveGetSpy = jest.spyOn(tlp_fetch, "recursiveGet");

        const program_increments: ProgramIncrement[] = [
            {
                title: "PI 1",
                status: "Planned",
                start_date: null,
                end_date: null,
                id: 1,
                user_can_update: true,
                user_can_plan: true,
                artifact_link_field_id: 1,
            } as ProgramIncrement,
            {
                title: "PI 2",
                status: "Planned",
                start_date: "2021-01-20T00:00:00+01:00",
                end_date: "2021-01-20T00:00:00+01:00",
                id: 2,
                user_can_update: true,
                user_can_plan: true,
                artifact_link_field_id: 1,
            } as ProgramIncrement,
        ];

        recursiveGetSpy.mockResolvedValueOnce(program_increments);

        const increments = await getProgramIncrements(146);

        expect(increments).toBe(program_increments);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/projects/146/program_increments", {
            params: { limit: 50, offset: 0 },
        });
    });
});

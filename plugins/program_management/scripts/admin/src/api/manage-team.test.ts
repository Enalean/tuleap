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
import * as tlp from "@tuleap/tlp-fetch";
import { manageTeamOfProgram } from "./manage-team";

describe("ManageTeam", () => {
    describe("manageTeamOfProgram", () => {
        it("Given program id and team ids, Then rest route is called to manage teams", () => {
            const put = vi.spyOn(tlp, "put");

            manageTeamOfProgram({ program_id: 105, team_ids: [123, 125] });

            expect(put).toHaveBeenCalledWith("/api/v1/projects/105/program_teams", {
                body: '{"team_ids":[123,125]}',
                headers: { "Content-Type": "application/json" },
            });
        });
    });
});

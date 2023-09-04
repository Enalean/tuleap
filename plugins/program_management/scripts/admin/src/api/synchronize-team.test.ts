/*
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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
 *
 */

import * as tlp from "@tuleap/tlp-fetch";
import { synchronizeTeamOfProgram } from "./synchronize-team";

jest.mock("@tuleap/tlp-fetch");
describe("SynchronizeTeam", () => {
    describe("synchronizeTeamOfProgram", () => {
        it("Given team id and program label, Then rest route is called to synchronize team", () => {
            const put = jest.spyOn(tlp, "put");

            synchronizeTeamOfProgram("my-program", 103);

            expect(put).toHaveBeenCalledWith(
                "/program_management/admin/my-program/synchronize/103",
                {
                    headers: { "Content-Type": "application/json" },
                },
            );
        });
    });
});

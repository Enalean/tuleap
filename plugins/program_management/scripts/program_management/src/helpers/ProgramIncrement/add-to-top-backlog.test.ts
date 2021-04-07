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

import * as tlp from "tlp";
import { moveElementFromProgramIncrementToTopBackLog } from "./add-to-top-backlog";

jest.mock("tlp");

describe("Add to top backlog", () => {
    it("Move element from program increment to top backlog", async () => {
        const tlpPatch = jest.spyOn(tlp, "patch");
        await moveElementFromProgramIncrementToTopBackLog(101, 1);

        expect(tlpPatch).toHaveBeenCalledWith(`/api/projects/101/program_backlog`, {
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                add: [{ id: 1 }],
                remove: [],
                remove_from_program_increment_to_add_to_the_backlog: true,
            }),
        });
    });
});

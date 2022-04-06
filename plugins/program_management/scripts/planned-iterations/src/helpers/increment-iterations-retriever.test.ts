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

import * as tlp_fetch from "@tuleap/tlp-fetch";
import { getIncrementIterations } from "./increment-iterations-retriever";

import type { Iteration } from "../type";

describe("increment-iterations-retriever", () => {
    it("retrieves the iterations of a given increment", async () => {
        const recursiveGetSpy = jest.spyOn(tlp_fetch, "recursiveGet");
        const increment_iterations: Iteration[] = [
            {
                id: 1279,
                title: "Iteration 1",
                status: "On going",
                start_date: "2021-10-01T00:00:00+02:00",
                end_date: "2021-10-15T00:00:00+02:00",
                user_can_update: true,
            },
            {
                id: 1280,
                title: "Iteration 2",
                status: "Planned",
                start_date: "2021-10-01T00:00:00+02:00",
                end_date: "2021-10-15T00:00:00+02:00",
                user_can_update: true,
            },
        ];

        recursiveGetSpy.mockResolvedValueOnce(increment_iterations);

        const iterations = await getIncrementIterations(666);

        expect(iterations).toBe(increment_iterations);
        expect(recursiveGetSpy).toHaveBeenCalledWith("/api/v1/program_increment/666/iterations", {
            params: { limit: 50, offset: 0 },
        });
    });
});

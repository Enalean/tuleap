/*
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
import { retrieveTrackers } from "./trackers-retriever";

jest.mock("tlp");

describe("trackers-retriever", () => {
    it("Retrieves each tracker only once", async () => {
        const spy_tlp_get = jest.spyOn(tlp, "get");

        const expected_trackers = [{ id: 85 }, { id: 23 }];

        spy_tlp_get.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({ id: 85 }),
        } as Response);
        spy_tlp_get.mockResolvedValueOnce({
            ok: true,
            json: () => Promise.resolve({ id: 23 }),
        } as Response);

        const trackers = await retrieveTrackers([...expected_trackers, { id: 85 }]);

        expect(trackers).toStrictEqual(expected_trackers);
        expect(spy_tlp_get).toHaveBeenCalledTimes(2);
    });
});

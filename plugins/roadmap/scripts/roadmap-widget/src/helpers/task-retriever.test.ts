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

import * as tlp from "tlp";
import { retrieveAllTasks } from "./task-retriever";

jest.mock("tlp");

describe("task-retriever", () => {
    it("Retrieves tasks and transform their dates from string to Date object", async () => {
        jest.spyOn(tlp, "recursiveGet").mockResolvedValue([
            {
                id: 6422,
                xref: "epic #6422",
                title: "Do this",
                html_url: "/plugins/tracker/?aid=6422",
                color_name: "panther-pink",
                start: "2020-03-01T10:00:00+01:00",
                end: "2020-03-14T10:00:00+01:00",
            },
        ]);

        const tasks = await retrieveAllTasks(123);

        expect(tasks[0].id).toBe(6422);
        expect(tasks[0].start?.toDateString()).toBe("Sun Mar 01 2020");
        expect(tasks[0].end?.toDateString()).toBe("Sat Mar 14 2020");
    });
});

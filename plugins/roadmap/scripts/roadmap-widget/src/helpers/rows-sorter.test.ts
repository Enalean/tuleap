/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import type { SkeletonRow, SubtaskRow, TaskRow } from "../type";
import { sortRows } from "./rows-sorter";

describe("rows-sorter", () => {
    it("sort rows", () => {
        const unsorted_rows = [
            {
                for_task: {
                    id: 10,
                    start: null,
                },
            } as SkeletonRow,
            {
                for_task: {
                    id: 5,
                    start: null,
                },
            } as SkeletonRow,
            {
                subtask: {
                    id: 30,
                    start: new Date(30),
                },
            } as SubtaskRow,
            {
                task: {
                    id: 20,
                    start: new Date(20),
                },
            } as TaskRow,
            {
                task: {
                    id: 50,
                    start: null,
                },
            } as TaskRow,
        ];

        expect(sortRows(unsorted_rows)).toMatchInlineSnapshot(`
            [
              {
                "task": {
                  "id": 20,
                  "start": 1970-01-01T00:00:00.020Z,
                },
              },
              {
                "subtask": {
                  "id": 30,
                  "start": 1970-01-01T00:00:00.030Z,
                },
              },
              {
                "for_task": {
                  "id": 5,
                  "start": null,
                },
              },
              {
                "for_task": {
                  "id": 10,
                  "start": null,
                },
              },
              {
                "task": {
                  "id": 50,
                  "start": null,
                },
              },
            ]
        `);
    });
});

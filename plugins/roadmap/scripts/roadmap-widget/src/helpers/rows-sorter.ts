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

import type { Row, Task } from "../type";

export function sortRows(rows: ReadonlyArray<Row>): Row[] {
    return [...rows].sort(compareRow);
}

function compareRow(row_a: Row, row_b: Row): number {
    const task_a = getTaskAssociatedWithRow(row_a);
    const task_b = getTaskAssociatedWithRow(row_b);

    if (task_a.start !== null && task_b.start !== null) {
        return task_a.start.getTime() - task_b.start.getTime();
    }

    if (task_a.start === null && task_b.start === null) {
        return task_a.id - task_b.id;
    }

    if (task_a.start === null) {
        return 1;
    }

    return -1;
}

function getTaskAssociatedWithRow(row: Row): Task {
    if ("for_task" in row) {
        return row.for_task;
    }

    if ("subtask" in row) {
        return row.subtask;
    }

    return row.task;
}

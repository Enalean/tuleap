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

import type { Task, TasksDependencies } from "../type";
import { NaturesLabels } from "../type";

export function getNatureLabelsForTasks(
    tasks: Task[],
    dependencies: TasksDependencies,
    visible_natures: NaturesLabels,
): NaturesLabels {
    return tasks.reduce((available_natures: NaturesLabels, task: Task): NaturesLabels => {
        const dependencies_for_task = dependencies.get(task);
        if (!dependencies_for_task) {
            return available_natures;
        }

        const unknown_used_natures = Array.from(dependencies_for_task.keys()).filter(
            (nature) => !available_natures.has(nature) && visible_natures.has(nature),
        );

        for (const nature of unknown_used_natures) {
            const label = visible_natures.get(nature);
            if (label !== undefined) {
                available_natures.set(nature, label);
            }
        }

        return available_natures;
    }, new NaturesLabels());
}

/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { State } from "./type";
import { Project, UserHistory, UserHistoryEntry } from "../type";

export function filtered_history(state: State): UserHistory {
    return {
        entries: state.history.entries.reduce(
            (
                matching_entriess: UserHistoryEntry[],
                entry: UserHistoryEntry
            ): UserHistoryEntry[] => {
                if (isMatchingFilterValue(entry.title, state)) {
                    matching_entriess.push(entry);
                } else if (isMatchingFilterValue(entry.xref, state)) {
                    matching_entriess.push(entry);
                }

                return matching_entriess;
            },
            []
        ),
    };
}

export const filtered_projects = (state: State): Project[] => {
    return state.projects.reduce((matching_projects: Project[], project: Project): Project[] => {
        if (isMatchingFilterValue(project.project_name, state)) {
            matching_projects.push(project);
        }

        return matching_projects;
    }, []);
};

function isMatchingFilterValue(s: string | null, state: State): boolean {
    if (!s) {
        return false;
    }

    return s.toLowerCase().indexOf(state.filter_value.toLowerCase()) !== -1;
}

/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

import { okAsync, type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ProjectInfo } from "../type";
import type { RetrieveProjects } from "../domain/RetrieveProjects";

export const ProjectsCache = (actual_retriever: RetrieveProjects): RetrieveProjects => {
    let is_first_call = true;
    let cache: ReadonlyArray<ProjectInfo> = [];
    return {
        getSortedProjectsIAmMemberOf(): ResultAsync<ReadonlyArray<ProjectInfo>, Fault> {
            if (!is_first_call) {
                return okAsync(cache);
            }
            is_first_call = false;
            return actual_retriever.getSortedProjectsIAmMemberOf().map((projects) => {
                cache = projects;
                return cache;
            });
        },
    };
};

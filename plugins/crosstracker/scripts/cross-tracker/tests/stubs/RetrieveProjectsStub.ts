/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

import { errAsync, okAsync, type ResultAsync } from "neverthrow";
import type { Fault } from "@tuleap/fault";
import type { ProjectInfo } from "../../src/type";
import type { RetrieveProjects } from "../../src/domain/RetrieveProjects";

export const RetrieveProjectsStub = {
    withProjects: (project: ProjectInfo, ...other_projects: ProjectInfo[]): RetrieveProjects => ({
        getSortedProjectsIAmMemberOf: () => okAsync([project, ...other_projects]),
    }),

    withSuccessiveProjects(
        projects: ReadonlyArray<ProjectInfo>,
        ...other_projects: ReadonlyArray<ProjectInfo>[]
    ): RetrieveProjects {
        const all_batches = [projects, ...other_projects];
        return {
            getSortedProjectsIAmMemberOf(): ResultAsync<ReadonlyArray<ProjectInfo>, Fault> {
                const batch = all_batches.shift();
                if (batch === undefined) {
                    throw Error("Did not expect to be called, no projects configured");
                }
                return okAsync(batch);
            },
        };
    },

    withFault: (fault: Fault): RetrieveProjects => ({
        getSortedProjectsIAmMemberOf: () => errAsync(fault),
    }),
};

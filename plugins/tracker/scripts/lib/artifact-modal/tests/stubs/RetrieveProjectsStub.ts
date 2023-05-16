/*
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

import { errAsync, okAsync } from "neverthrow";
import type { Project } from "../../src/domain/Project";
import type { RetrieveProjects } from "../../src/domain/fields/link-field/creation/RetrieveProjects";
import type { Fault } from "@tuleap/fault";

export const RetrieveProjectsStub = {
    withProjects: (first_project: Project, ...other_projects: Project[]): RetrieveProjects => ({
        getProjects: () => okAsync([first_project, ...other_projects]),
    }),

    withFault: (fault: Fault): RetrieveProjects => ({
        getProjects: () => errAsync(fault),
    }),
};

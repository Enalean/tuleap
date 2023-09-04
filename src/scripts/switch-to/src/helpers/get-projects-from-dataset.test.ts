/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { Project } from "../type";
import { getProjectsFromDataset } from "./get-projects-from-dataset";

describe("getProjectFromDataset", () => {
    const $gettext = (s: string): string => s;

    it("should return empty array if undefined", () => {
        expect(getProjectsFromDataset(undefined, $gettext)).toStrictEqual([]);
    });

    it("should return empty array if list of projects is empty", () => {
        expect(getProjectsFromDataset("[]", $gettext)).toStrictEqual([]);
    });

    it("should get a project where current user is not admin", () => {
        const project: Project = getProjectsFromDataset(
            `[{"project_uri":"/project","project_config_uri":"/admin","is_current_user_admin":false}]`,
            $gettext,
        )[0];

        expect(project.project_uri).toBe("/project");
        expect(project.quick_links).toStrictEqual([]);
    });

    it("should get a project where current user is admin", () => {
        const project: Project = getProjectsFromDataset(
            `[{"project_uri":"/project","project_config_uri":"/admin","is_current_user_admin":true}]`,
            $gettext,
        )[0];

        expect(project.project_uri).toBe("/project");
        expect(project.quick_links[0].html_url).toBe("/admin");
    });
});

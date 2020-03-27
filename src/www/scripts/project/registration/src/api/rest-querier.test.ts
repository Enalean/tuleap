/*
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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

import { postProject, getProjectUserIsAdminOf, getTermOfService } from "./rest-querier";

import * as tlp from "tlp";
import { mockFetchSuccess } from "../../../../../../../src/www/themes/common/tlp/mocks/tlp-fetch-mock-helper";
import { MinimalProjectRepresentation, ProjectProperties, TemplateData } from "../type";

jest.mock("tlp");

describe("rest-querier", () => {
    it("Post project - creates a new project", async () => {
        const project_properties: ProjectProperties = {
            shortname: "short-name",
            label: "My project public name",
            is_public: true,
            allow_restricted: true,
            xml_template_name: "scrum",
            categories: [],
            description: "",
            fields: [],
        };

        const tlpPost = jest.spyOn(tlp, "post");

        mockFetchSuccess(tlpPost, project_properties);

        await postProject(project_properties);
        expect(tlpPost).toHaveBeenCalled();
    });

    it("getProjectUserIsAdminOf - retrieves project user is admin of and format them", async () => {
        const project_a: MinimalProjectRepresentation = {
            resources: [],
            is_member_of: true,
            id: "101",
            uri: "project/101",
            label: "My A project",
            shortname: "My A project",
            status: "A",
            access: "public",
            is_template: false,
        };

        const project_b: MinimalProjectRepresentation = {
            resources: [],
            is_member_of: true,
            id: "101",
            uri: "project/101",
            label: "My B project",
            shortname: "My B project",
            status: "B",
            access: "public",
            is_template: true,
        };

        const project_list = [project_a, project_b];

        const recursiveGet = jest
            .spyOn(tlp, "recursiveGet")
            .mockReturnValue(Promise.resolve(project_list));

        const formatted_projects = await getProjectUserIsAdminOf();
        expect(recursiveGet).toHaveBeenCalled();

        const formatted_project_a: TemplateData = {
            title: "My A project",
            description: "",
            id: "101",
            glyph: "",
            is_built_in: false,
        };

        expect(formatted_projects).toEqual([formatted_project_a]);
    });

    it("getTermOfService - retrieves the term of service", async () => {
        const response_text = jest.fn();
        jest.spyOn(tlp, "get").mockImplementation(() => {
            return ({
                text: response_text,
            } as unknown) as Promise<Response>;
        });

        await getTermOfService();

        expect(response_text).toHaveBeenCalled();
    });
});

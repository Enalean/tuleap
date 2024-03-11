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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

import type { ProjectProperties, TemplateData } from "../type";
import { createPinia, setActivePinia } from "pinia";
import { useStore } from "./root";
import * as rest_querier from "../api/rest-querier";
import * as uploadFile from "../helpers/upload-file";
import type { ProjectArchiveReference, ProjectReference } from "@tuleap/core-rest-api-types";

describe("mutation", () => {
    beforeEach(() => {
        setActivePinia(createPinia());
    });

    describe("setSelectedTemplate()", () => {
        it(`stores the tuleap template and make sure the company template is null`, () => {
            const store = useStore();
            store.selected_tuleap_template = null;
            store.selected_company_template = {
                title: "Whole lot company",
                description: "I have got whole lot",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false,
            } as TemplateData;

            const selected_template = {
                title: "scrum template",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            };
            store.setSelectedTemplate(selected_template);
            expect(store.selected_tuleap_template).toStrictEqual(selected_template);
            expect(store.selected_company_template).toBeNull();
        });

        it(`stores the company template and make sure the tuleap template is null`, () => {
            const store = useStore();
            store.selected_tuleap_template = {
                title: "scrum template",
                description: "scrum desc",
                id: "scrum",
                glyph: "<svg></svg>",
                is_built_in: true,
            } as TemplateData;
            store.selected_company_template = null;

            const selected_template = {
                title: "Whole lot company",
                description: "I have got whole lot",
                id: "10",
                glyph: "<svg></svg>",
                is_built_in: false,
            };
            store.setSelectedTemplate(selected_template);
            expect(store.selected_company_template).toStrictEqual(selected_template);
            expect(store.selected_tuleap_template).toBeNull();
        });
    });
    describe("resetProjectCreationError() -", () => {
        it("reset the project creation error", () => {
            const store = useStore();
            store.error = "It does not work :(";

            store.resetProjectCreationError();
            expect(store.error).toBeNull();
        });
    });
    describe("createProject() -", () => {
        it("Creates the project from a template", async () => {
            const uploadFileMock = jest.spyOn(uploadFile, "uploadFile");
            const store = useStore();

            const response: ProjectReference = {
                id: 101,
                label: "cts-v",
                icon: "",
                uri: "project/cts-v",
            };

            jest.spyOn(rest_querier, "postProject").mockResolvedValue(response);

            const project: ProjectProperties = {
                shortname: "Cadillac",
                label: "cts-v",
                is_public: false,
                allow_restricted: false,
                xml_template_name: "kanban",
                template_id: null,
                from_archive: null,
                categories: [],
                description: "",
                fields: [],
            };
            const result = await store.createProject(project);

            expect(uploadFileMock).not.toHaveBeenCalled();
            expect(result).toStrictEqual(response);
            expect(store.is_creating_project).toBe(false);
        });
        it("Creates the project from an archive", async () => {
            const uploadFileMock = jest.spyOn(uploadFile, "uploadFile").mockImplementation();
            const store = useStore();
            store.selected_company_template = {
                id: "from_project_archive",
                title: "From project template upload",
                is_built_in: false,
                glyph: "",
                description: "Create a project based on a template exported from another platform",
                archive: new File([], "export_102.zip"),
            };

            const response: ProjectArchiveReference = {
                id: 101,
                upload_href: "project/ongoing-upload/20",
                uri: "project/cts-v",
            };

            jest.spyOn(rest_querier, "postProject").mockResolvedValue(response);

            const project: ProjectProperties = {
                shortname: "Cadillac",
                label: "cts-v",
                is_public: false,
                allow_restricted: false,
                xml_template_name: null,
                template_id: null,
                from_archive: {
                    file_name: "export_102.zip",
                    file_size: 25,
                },
                categories: [],
                description: "",
                fields: [],
            };
            const result = await store.createProject(project);

            expect(uploadFileMock).toHaveBeenCalled();
            expect(result).toStrictEqual(response);
            expect(store.is_creating_project).toBe(false);
        });
        it("Throws an error if the project cannot be created", async () => {
            const uploadFileMock = jest.spyOn(uploadFile, "uploadFile").mockImplementation();
            const store = useStore();
            store.selected_company_template = {
                id: "from_project_archive",
                title: "From project template upload",
                is_built_in: false,
                glyph: "",
                description: "Create a project based on a template exported from another platform",
                archive: new File([], "export_102.zip"),
            };

            jest.spyOn(rest_querier, "postProject").mockImplementation(() => {
                throw new Error("cannot be created :'(");
            });

            const project: ProjectProperties = {
                shortname: "Cadillac",
                label: "cts-v",
                is_public: false,
                allow_restricted: false,
                xml_template_name: null,
                template_id: null,
                from_archive: {
                    file_name: "export_102.zip",
                    file_size: 25,
                },
                categories: [],
                description: "",
                fields: [],
            };

            await expect(() => store.createProject(project)).rejects.toThrow();
            expect(uploadFileMock).not.toHaveBeenCalled();
            expect(store.is_creating_project).toBe(false);
        });
    });
});

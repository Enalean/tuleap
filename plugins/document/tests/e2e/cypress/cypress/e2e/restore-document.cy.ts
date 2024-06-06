/*
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

import type {
    CreatedItemResponse,
    ProjectServiceResponse,
} from "@tuleap/plugin-document-rest-api-types";
import { deleteDocumentDisplayedInQuickLook, openQuickLook } from "../support/helpers";

describe("Document restoration", () => {
    let now: number;
    let project_shortname: string;

    before(() => {
        cy.projectMemberSession();
        now = Date.now();
        project_shortname = `doc-restore-${now}`;
        cy.createNewPublicProject(project_shortname, "issues").then((project_id) =>
            cy
                .getFromTuleapAPI<ProjectServiceResponse>(
                    `api/projects/${project_id}/docman_service`,
                )
                .then((response) => {
                    const root_folder_id = response.body.root_item.id;

                    const embedded_payload = {
                        title: "document",
                        description: "",
                        type: "embedded",
                        embedded_properties: {
                            content: "<p>embedded</p>\n",
                        },
                        should_lock_file: false,
                    };
                    cy.postFromTuleapApi<CreatedItemResponse>(
                        `api/docman_folders/${root_folder_id}/embedded_files`,
                        embedded_payload,
                    );

                    const embedded_version_payload = {
                        title: "versions",
                        description: "",
                        type: "embedded",
                        embedded_properties: {
                            content: "<p>embedded with versions</p>\n",
                        },
                        should_lock_file: false,
                    };
                    return cy.postFromTuleapApi<CreatedItemResponse>(
                        `api/docman_folders/${root_folder_id}/embedded_files`,
                        embedded_version_payload,
                    );
                })
                .then((response) => response.body.id)
                .then((item) => {
                    const updated_embedded_payload = {
                        embedded_properties: {
                            content: "<p>updated content</p>\n",
                        },
                        should_lock_file: false,
                    };
                    cy.postFromTuleapApi(
                        `api/docman_embedded_files/${item}/versions`,
                        updated_embedded_payload,
                    );
                    cy.visit(`/plugins/document/${project_shortname}/versions/${item}`);
                }),
        );
    });

    it("site administrator must be able to restore a document", function () {
        cy.projectMemberSession();

        cy.visitProjectService(project_shortname, "Documents");

        openQuickLook("document");
        deleteDocumentDisplayedInQuickLook();

        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=group-name-search]").type(`${project_shortname}{enter}`);
        cy.get("[data-test=pending-deleted-documents]").click();
        cy.get("[data-test=restore-document]").click();

        cy.on("window:confirm", () => {
            // do nothing, cypress validate alert by default
        });

        cy.projectMemberSession();
        cy.visitProjectService(project_shortname, "Documents");
        cy.log("Document is restored it can be displayed in quick look");
        openQuickLook("document");
    });

    it("site administrator must be able to restore a specific version of a document", function () {
        cy.projectMemberSession();

        cy.visitProjectService(project_shortname, "Documents");

        openQuickLook("versions");

        cy.log("delete a given version of a document");
        cy.get(`[data-test=document-drop-down-button]`).last().click();
        cy.get(`[data-test=document-versions]`).last().click();
        cy.get(`[data-test=delete-button]`).eq(0).click();
        cy.get("[data-test=confirm-button]").eq(0).click();
        cy.get("[data-test=display-version-feedback]").contains("successfully deleted");

        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=group-name-search]").type(`${project_shortname}{enter}`);
        cy.get("[data-test=pending-deleted-documents]").click();

        cy.get("[data-test=restore-version]").click();
        cy.on("window:confirm", () => {
            // do nothing, cypress validate alert by default
        });

        cy.projectMemberSession();
        cy.visitProjectService(project_shortname, "Documents");
        cy.log("Check version is restored");
        openQuickLook("versions");
        cy.get(`[data-test=document-drop-down-button]`).last().click();
        cy.get(`[data-test=document-versions]`).last().click();

        cy.get("[data-test=history-versions]").find("tr").should("have.length", 3);
    });
});

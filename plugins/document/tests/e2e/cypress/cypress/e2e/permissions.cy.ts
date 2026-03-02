/*
 * Copyright (c) Enalean, 2026 - Present. All Rights Reserved.
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

import type { ProjectServiceResponse } from "@tuleap/plugin-document-rest-api-types";
import { disableSpecificErrorThrownByCkeditor } from "../support/disable-specific-error-thrown-by-ckeditor";
import { getAntiCollisionNamePart } from "@tuleap/cypress-utilities-support";

describe("Writers", function () {
    let permission_project_name: string;
    beforeEach(() => {
        permission_project_name = "document-perm-" + getAntiCollisionNamePart();
        disableSpecificErrorThrownByCkeditor();
    });

    function createAProjectWithAnEmptyDocument(project_name: string, document_name: string): void {
        cy.createNewPublicProject(project_name, "issues").then((project_id) => {
            cy.getFromTuleapAPI<ProjectServiceResponse>(
                `api/projects/${project_id}/docman_service`,
            ).then((response) => {
                const root_folder_id = response.body.root_item.id;

                const payload = {
                    title: document_name,
                    description: "",
                    type: "empty",
                };
                cy.postFromTuleapApi(`api/docman_folders/${root_folder_id}/empties`, payload);
            });
        });
    }

    it("have specifics permissions", function () {
        cy.projectAdministratorSession();
        const project_name = "document-perm-" + getAntiCollisionNamePart();
        const document_name = "Document " + getAntiCollisionNamePart();
        createAProjectWithAnEmptyDocument(project_name, document_name);

        cy.visitProjectService(project_name, "Documents");

        cy.log("Project administrator can define specifics permissions for Writers");
        cy.get("[data-test=breadcrumb-project-documentation]").click();
        cy.get("[data-test=breadcrumb-administrator-link]").click();
        cy.get("[data-test=admin_permissions]").click();
        cy.get("[data-test=forbid-writers-to-update]").check();
        cy.get("[data-test=forbid-writers-to-delete]").check();
        cy.get("[data-test=save-permissions-button]").click();

        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                cy.get("[data-test=dropdown-button]").contains("Delete");
                cy.get("[data-test=dropdown-button]").contains("Permissions");
            });

        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Documents");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", document_name)
            .within(() => {
                cy.get("[data-test=dropdown-button]").should("not.contain", "Delete");
                cy.get("[data-test=dropdown-button]").should("not.contain", "Permissions");
            });
    });

    it("projectMember can ask permission to see a document he can not access", function () {
        cy.projectAdministratorSession();

        cy.createNewPublicProject(permission_project_name, "issues")
            .then((project_id) =>
                cy.getFromTuleapAPI<ProjectServiceResponse>(
                    `api/projects/${project_id}/docman_service`,
                ),
            )
            .then((response) => {
                const root_folder_id = response.body.root_item.id;
                const embedded_payload = {
                    title: "test",
                    description: "",
                    type: "embedded",
                    embedded_properties: {
                        content: "<p>embedded</p>\n",
                    },
                    should_lock_file: false,
                };
                return cy.postFromTuleapApi(
                    `api/docman_folders/${root_folder_id}/embedded_files`,
                    embedded_payload,
                );
            });

        cy.visitProjectService(permission_project_name, "Documents");
        cy.get("[data-test=document-drop-down-button]").first().click();
        cy.get("[data-test=document-permissions]").first().click();

        cy.get("[data-test=document-permission-Reader]").select("Project administrators");
        cy.get("[data-test=document-permission-Writer]").select("Project administrators");
        cy.get("[data-test=document-permission-Manager]").select("Project administrators");

        cy.get("[data-test=document-modal-submit-update-permissions]").click();
        cy.get("[data-test=document-folder-content-row]").click();

        cy.url().then((url) => {
            cy.projectMemberSession();
            //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
            cy.visit(url, { failOnStatusCode: false });
            const message = "private_document";
            cy.get("[data-test=message-request-access-private-document]").type(message);
            cy.get("[data-test=private-document-access-button]").click();

            cy.assertEmailWithContentReceived("ProjectAdministrator@example.com", message);
        });
    });

    it("should raise an error when user try to access to document admin page", function () {
        cy.projectMemberSession();
        cy.visit("/my/");
        cy.request({
            url: `/plugins/document/${permission_project_name}/admin-search`,
            failOnStatusCode: false,
        }).then((response) => {
            expect(response.status).to.eq(404);
        });
    });
});

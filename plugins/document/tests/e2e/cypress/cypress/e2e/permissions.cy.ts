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
import {
    createFolderWithContent,
    createSubfolderIntoFolderFromTreeViewRow,
} from "../support/create-document";

describe("Writers", function () {
    let permission_project_name: string;
    let folder_permissions_project_name: string;
    beforeEach(() => {
        permission_project_name = "document-perm-" + getAntiCollisionNamePart();
        folder_permissions_project_name = "folder-perm-" + getAntiCollisionNamePart();
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

        setPermissionsToGroup("Project administrators");

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
        cy.projectAdministratorSession();
        cy.visit("/my/");
        cy.request({
            url: `/plugins/document/${permission_project_name}/admin-search`,
            failOnStatusCode: false,
        }).then((response) => {
            expect(response.status).to.eq(404);
        });
    });

    it(`Folder permissions inheritance`, () => {
        cy.projectAdministratorSession();
        cy.createNewPublicProject(folder_permissions_project_name, "issues");
        cy.visitProjectService(folder_permissions_project_name, "Documents");

        cy.intercept("GET", "/api/projects/*/user_groups*").as("getPermissions");
        cy.intercept("PUT", "/api/docman_folders/*/permissions*").as("updatePermissions");

        cy.log("Create a folder with some content");
        createFolderWithContent("AA", "./_fixtures/aa.txt");
        createSubfolderIntoFolderFromTreeViewRow("AA", "sub folder");

        cy.log("Update permissions of root folder (Project Documentation)");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-drop-down-button]").click({ force: true });
            cy.get("[data-test=document-permissions]").click();
        });
        setPermissionsToGroup("Project administrators");

        cy.get("[data-test=document-modal-submit-update-permissions]").click();
        cy.wait("@updatePermissions");

        cy.log("Check that only Project Documentation has its permissions updated");
        cy.log("Update permissions of root folder (Project Documentation)");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-drop-down-button]").click({ force: true });
            cy.get("[data-test=document-permissions]").click();
        });

        cy.get("[data-test=document-permissions-item-modal]").within(() => {
            cy.wait("@getPermissions");
            assertSelectHasNoSelectedValue("Reader");
            assertSelectHasNoSelectedValue("Writer");
            assertPermissionSelect("Manager", "Project administrators");
            cy.get("[data-test=close-modal]").click();
        });

        cy.get("[data-test=document-tree-content]")
            .contains("tr", "AA")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-permissions]").click();
            });

        cy.get("[data-test=document-permissions-item-modal]").within(() => {
            assertPermissionSelect("Reader", "Registered users");
            assertPermissionSelect("Writer", "Project members");
            assertPermissionSelect("Manager", "Project administrators");
            cy.get("[data-test=close-modal]").click();
        });

        cy.log("Update permissions to whole subfolder content");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "AA")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-permissions]").click();
            });

        setPermissionsToGroup("Registered users");
        cy.get("[data-test= checkbox-apply-permissions-on-children]").click();

        cy.get("[data-test=document-modal-submit-update-permissions]").click();
        cy.wait("@updatePermissions");

        cy.log("Permission is properly applied on folder AA");
        assertPermissionOfElement("AA");

        cy.log("Open AA to check sub items permissions");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "AA")
            .within(() => {
                cy.get("[data-test=toggle]").click();
            });

        cy.log("Permission is properly applied on sub folder");
        assertPermissionOfElement("sub folder");

        cy.log("Permission is properly applied on aa.txt");
        assertPermissionOfElement("aa.txt");

        cy.log("Create a new item will automatically inherit permissions from parent folder");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "AA")
            .within(() => {
                cy.get("[data-test=document-new-empty-creation-button]").click({ force: true });
            });
        assertSelectHasNoSelectedValue("Reader");
        assertSelectHasNoSelectedValue("Writer");
        assertPermissionSelect("Manager", "Registered users");
    });
});

function assertPermissionSelect(permission_level: string, expected_text: string): void {
    cy.get(`[data-test=document-permission-${permission_level}]`)
        .find("option:selected")
        .should("have.text", expected_text);
}

function assertSelectHasNoSelectedValue(permission_level: string): void {
    cy.get(`[data-test=document-permission-${permission_level}]`)
        .invoke("val")
        .should("have.length", 0);
}

function assertPermissionOfElement(item_name: string): void {
    cy.get("[data-test=document-tree-content]")
        .contains("tr", item_name)
        .within(() => {
            cy.get("[data-test=document-drop-down-button]").click({ force: true });
            cy.get("[data-test=document-permissions]").click();
        });

    cy.get("[data-test=document-permissions-item-modal]").within(() => {
        assertSelectHasNoSelectedValue("Reader");
        assertSelectHasNoSelectedValue("Writer");
        assertPermissionSelect("Manager", "Registered users");
        cy.get("[data-test=close-modal]").click();
    });
}

function setPermissionsToGroup(group_name: string): void {
    cy.get("[data-test=document-permission-Reader]").select(group_name);
    cy.get("[data-test=document-permission-Writer]").select(group_name);
    cy.get("[data-test=document-permission-Manager]").select(group_name);
}

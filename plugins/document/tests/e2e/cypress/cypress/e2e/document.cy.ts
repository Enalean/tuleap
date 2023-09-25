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

import { disableSpecificErrorThrownByCkeditor } from "../support/disable-specific-error-thrown-by-ckeditor";
import { deleteDocumentDisplayedInQuickLook, openQuickLook } from "../support/helpers";

describe("Document new UI", () => {
    let now: number;
    context("Project Administrators", function () {
        context("Project administrators", function () {
            let project_unixname: string;
            before(() => {
                now = Date.now();
                project_unixname = `docman-${now}`;
                cy.projectAdministratorSession();
                cy.createNewPublicProject(project_unixname, "issues").as("project_id");
                cy.visit(`${"/plugins/document/" + project_unixname + "/admin-search"}`);
                cy.contains("Properties").should("have.attr", "href").as("manage_properties_url");
            });

            it("document properties", function () {
                cy.projectAdministratorSession();
                cy.visit(this.manage_properties_url);
                cy.log("Create a custom property");
                cy.get("[data-test=docman-admin-properties-create-button]").click();

                cy.get("[data-test=metadata_name]").type("my custom property");
                cy.get("[data-test=empty_allowed]").uncheck();
                cy.get("[data-test=use_it]").check();
                cy.get("[data-test=admin_create_metadata]").submit();

                cy.log("property is displayed in modal");
                cy.visitProjectService(project_unixname, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-folder-creation-button]").click();
                });
                cy.get("[data-test=document-new-folder-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My folder");

                    cy.get("[data-test=document-custom-property-text]").contains(
                        "my custom property",
                    );
                });

                cy.log("Remove the property");
                cy.visit(this.manage_properties_url);
                cy.get("[data-test=docman-admin-properties-delete-button]").click();
                cy.get("[data-test=docman-admin-properties-delete-confirm-button]").click();

                cy.get("[data-test=feedback]").contains(
                    '"my custom property" successfully deleted',
                );
            });

            function createProjectWithAVersionnedEmbededFile(): void {
                cy.createNewPublicProject(`doc-version-${now}`, "issues").then((project_id) =>
                    cy
                        .getFromTuleapAPI(`api/projects/${project_id}/docman_service`)
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
                            cy.visit(
                                `plugins/docman/?group_id=${project_id}&id=${item}&action=details&section=history`,
                            );
                        }),
                );
            }

            it("document versioning", function () {
                cy.projectAdministratorSession();
                createProjectWithAVersionnedEmbededFile();

                cy.log("delete a given version of a document");
                cy.get(`[data-test=delete-2]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains("successfully deleted");

                cy.log("throw an error when you try to delete the last version of a document");
                cy.get(`[data-test=delete-1]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains(
                    "Cannot delete last version of a file. If you want to continue, please delete the document itself.",
                );
            });
        });
    });

    context("Project members", function () {
        before(() => {
            now = Date.now();
            cy.projectAdministratorSession();
            cy.createNewPublicProject(`document-project-${now}`, "issues");
            cy.visit(`/projects/document-project-${now}`);
            cy.addProjectMember("projectMember");
        });

        context("docman permissions", function () {
            it("should raise an error when user try to access to document admin page", function () {
                cy.projectMemberSession();
                cy.visit("/my/");
                cy.request({
                    url: `/plugins/document/document-project-${now}/admin-search`,
                    failOnStatusCode: false,
                }).then((response) => {
                    expect(response.status).to.eq(404);
                });
            });
        });

        context("Item manipulation", () => {
            let permission_project_name: string;
            beforeEach(() => {
                permission_project_name = `perm-doc-${now}`;
                disableSpecificErrorThrownByCkeditor();
            });

            it("projectMember can ask permission to see a document he can not access", function () {
                cy.projectAdministratorSession();
                cy.createNewPublicProject(permission_project_name, "issues")
                    .then((project_id) =>
                        cy.getFromTuleapAPI(`api/projects/${project_id}/docman_service`),
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

            it("user can manipulate folders", () => {
                cy.projectAdministratorSession();
                cy.visitProjectService(`document-project-${now}`, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();

                    cy.get("[data-test=document-new-folder-creation-button]").click();
                });

                cy.get("[data-test=document-new-folder-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My new folder");
                    cy.get("[data-test=document-property-description]").type(
                        "With a description because I like to describe what I'm doing",
                    );

                    cy.get("[data-test=document-modal-submit-button-create-folder]").click();
                });
                openQuickLook("My new folder");
                deleteDocumentDisplayedInQuickLook();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            it("user can manipulate empty document", () => {
                cy.projectAdministratorSession();
                cy.visitProjectService(`document-project-${now}`, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-empty-creation-button]").click();
                });
                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My new empty document");
                    cy.get("[data-test=document-modal-submit-button-create-item]").click();
                });
                openQuickLook("My new empty document");
                deleteDocumentDisplayedInQuickLook();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            it("user can manipulate links", () => {
                cy.projectAdministratorSession();
                cy.visitProjectService(`document-project-${now}`, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-link-creation-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My new link document");
                    cy.get("[data-test=document-new-item-link-url]").type("https://example.com");
                    cy.get("[data-test=document-modal-submit-button-create-item]").click();
                });
                openQuickLook("My new link document");

                cy.get("[data-test=document-quicklook-action-button-new-version").click({
                    force: true,
                });

                cy.get("[data-test=document-new-version-modal]").within(() => {
                    cy.get("[data-test=document-new-item-link-url]").clear();
                    cy.get("[data-test=document-new-item-link-url]").type(
                        "https://example-bis.com",
                    );

                    cy.get("[data-test=document-modal-submit-button-create-link-version]").click();
                });
                deleteDocumentDisplayedInQuickLook();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            it("user should be able to create an embedded file", () => {
                cy.projectAdministratorSession();
                cy.visitProjectService(`document-project-${now}`, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-embedded-creation-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My new html content");

                    cy.window().then((win) => {
                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                        // @ts-ignore
                        win.CKEDITOR.instances["document-new-item-embedded"].setData(
                            `<strong>This is the story of my life </strong>`,
                        );
                    });
                    cy.get("[data-test=document-modal-submit-button-create-item]").click();
                });

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "My new html content")
                    .within(() => {
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                deleteDocumentDisplayedInQuickLook();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            function createProjectWithDownloadableDocuments(): void {
                cy.createNewPublicProject(`download-${now}`, "issues")
                    .then((document_project_id) =>
                        cy.getFromTuleapAPI(`api/projects/${document_project_id}/docman_service`),
                    )
                    .then((response) => {
                        const root_folder_id = response.body.root_item.id;

                        const folder_payload = {
                            title: "Folder download",
                            description: "",
                            type: "folder",
                        };
                        return cy.postFromTuleapApi(
                            `api/docman_folders/${root_folder_id}/folders`,
                            folder_payload,
                        );
                    })
                    .then((response) => {
                        const folder = response.body.id;
                        const item = {
                            title: "test",
                            description: "",
                            type: "embedded",
                            embedded_properties: {
                                content:
                                    "<strong>Our deeds determine us, as much as we determine our deeds.</strong>",
                            },
                        };
                        return cy.postFromTuleapApi(
                            `api/docman_folders/${folder}/embedded_files`,
                            item,
                        );
                    });
            }

            it(`user can download a folder as a zip archive`, () => {
                cy.projectAdministratorSession();
                createProjectWithDownloadableDocuments();

                cy.visitProjectService(`download-${now}`, "Documents");

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "Folder download")
                    .within(($row) => {
                        // We cannot click the download button, otherwise the browser will ask "Where to save this file ?"
                        // and will stop the test.
                        cy.get("[data-test=document-dropdown-download-folder-as-zip]").should(
                            "exist",
                        );
                        const folder_id = $row.data("itemId");
                        if (folder_id === undefined) {
                            throw new Error("Could not retrieve the folder id from its <tr>");
                        }
                        const download_uri = `/plugins/document/document-project-${now}/folders/${encodeURIComponent(
                            folder_id,
                        )}/download-folder-as-zip`;

                        // Verify the download URI returns code 200 and has the correct headers
                        cy.request({
                            url: download_uri,
                        }).then((response) => {
                            expect(response.status).to.equal(200);
                            expect(response.headers["content-type"]).to.equal("application/zip");
                            expect(response.headers["content-disposition"]).to.equal(
                                'attachment; filename="Folder download.zip"',
                            );
                        });
                    });
            });
        });
    });

    context("Writers", function () {
        beforeEach(() => {
            disableSpecificErrorThrownByCkeditor();
        });

        function createAProjectWithAnEmptyDocument(
            project_name: string,
            document_name: string,
        ): void {
            cy.createNewPublicProject(project_name, "issues").then((project_id) => {
                cy.getFromTuleapAPI(`api/projects/${project_id}/docman_service`).then(
                    (response) => {
                        const root_folder_id = response.body.root_item.id;

                        const payload = {
                            title: document_name,
                            description: "",
                            type: "empty",
                        };
                        cy.postFromTuleapApi(
                            `api/docman_folders/${root_folder_id}/empties`,
                            payload,
                        );
                    },
                );
            });
        }

        it("have specifics permissions", function () {
            cy.projectAdministratorSession();
            const project_name = `document-perm-${now}`;
            const document_name = `Document ${now}`;
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
    });
});

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

describe("Document new UI", () => {
    let now: number;
    context("Project Administrators", function () {
        context("Project administrators", function () {
            let project_unixname: string;
            before(() => {
                now = Date.now();
                project_unixname = `docman-${now}`;
                cy.projectAdministratorSession();
                cy.createNewPublicProject(project_unixname, "issues");
            });

            it("can access to admin section", function () {
                cy.projectAdministratorSession();
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
                        "my custom property"
                    );
                });

                cy.log("Remove the property");
                cy.visit(this.manage_properties_url);
                cy.get("[data-test=docman-admin-properties-delete-button]").click();
                cy.get("[data-test=docman-admin-properties-delete-confirm-button]").click();

                cy.get("[data-test=feedback]").contains(
                    '"my custom property" successfully deleted'
                );
            });

            it("document versioning", function () {
                cy.projectAdministratorSession();
                cy.log("create an embed document");
                cy.visitProjectService(project_unixname, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-embedded-creation-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("My embedded");

                    cy.window().then((win) => {
                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                        // @ts-ignore
                        win.CKEDITOR.instances["document-new-item-embedded"].setData(
                            `<strong>This is the story of my life </strong>`
                        );
                    });
                    cy.get("[data-test=document-modal-submit-button-create-item]").click();
                });

                cy.visitProjectService(project_unixname, "Documents");
                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "My embedded")
                    .within(() => {
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                cy.log("create a new version of a document");
                cy.get("[data-test=document-quick-look]").contains("My embedded");

                cy.get(`[data-test="document-quicklook-action-button-new-version"]`).click({
                    force: true,
                });

                cy.intercept("/api/docman_embedded_files/*/versions").as("postVersions");

                cy.get("[data-test=document-update-version-title]").type("new version");
                cy.get("[data-test=document-modal-submit-button-create-embedded-version]").click();

                cy.wait("@postVersions", { timeout: 60000 });

                cy.log("delete a given version of a document");
                // need to force due to drop down
                cy.get("[data-test=document-history]").last().click({ force: true });
                cy.get(`[data-test=delete-2]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains("successfully deleted");

                cy.log("throw an error when you try to delete the last version of a document");
                cy.get(`[data-test=delete-1]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains(
                    "Cannot delete last version of a file. If you want to continue, please delete the document itself."
                );
            });
        });
    });

    context("Project members", function () {
        before(() => {
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
            beforeEach(() => {
                disableSpecificErrorThrownByCkeditor();
            });

            function deleteFolder(): void {
                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();
            }

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
                        "With a description because I like to describe what I'm doing"
                    );

                    cy.get("[data-test=document-modal-submit-button-create-folder]").click();
                });

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "My new folder")
                    .within(() => {
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                cy.get("[data-test=document-quick-look]").contains("My new folder");

                deleteFolder();

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

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "My new empty document")
                    .within(() => {
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                cy.get("[data-test=document-quick-look]").contains("My new empty document");

                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();

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

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "My new link document")
                    .within(() => {
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                cy.get("[data-test=document-quick-look]").contains("My new link document");

                cy.get("[data-test=document-quicklook-action-button-new-version").click({
                    force: true,
                });

                cy.get("[data-test=document-new-version-modal]").within(() => {
                    cy.get("[data-test=document-new-item-link-url]").clear();
                    cy.get("[data-test=document-new-item-link-url]").type(
                        "https://example-bis.com"
                    );

                    cy.get("[data-test=document-modal-submit-button-create-link-version]").click();
                });

                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();

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
                            `<strong>This is the story of my life </strong>`
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

                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            it(`user can download a folder as a zip archive`, () => {
                cy.projectAdministratorSession();
                cy.visitProjectService(`document-project-${now}`, "Documents");
                // Create a folder
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    // need to force click because buttons can be out of viewport
                    cy.get("[data-test=document-new-folder-creation-button]").click({
                        force: true,
                    });
                });

                cy.get("[data-test=document-new-folder-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("Folder download");
                    cy.get("[data-test=document-modal-submit-button-create-folder]").click();
                });

                // Go to the folder
                cy.get("[data-test=document-tree-content]").contains("a", "Folder download").click({
                    force: true,
                });

                // Create an embedded file in this folder
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                    cy.get("[data-test=document-new-embedded-creation-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=document-new-item-title]").type("Embedded file");

                    cy.window().then((win) => {
                        // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                        // @ts-ignore
                        win.CKEDITOR.instances["document-new-item-embedded"].setData(
                            `<strong>Our deeds determine us, as much as we determine our deeds.</strong>`
                        );
                    });

                    cy.get("[data-test=document-modal-submit-button-create-item]").click();
                });

                cy.visitProjectService(`document-project-${now}`, "Documents");

                cy.get("[data-test=document-tree-content]")
                    .contains("tr", "Folder download")
                    .within(($row) => {
                        // We cannot click the download button, otherwise the browser will ask "Where to save this file ?"
                        // and will stop the test.
                        cy.get("[data-test=document-dropdown-download-folder-as-zip]").should(
                            "exist"
                        );
                        const folder_id = $row.data("itemId");
                        if (folder_id === undefined) {
                            throw new Error("Could not retrieve the folder id from its <tr>");
                        }
                        const download_uri = `/plugins/document/document-project-${now}/folders/${encodeURIComponent(
                            folder_id
                        )}/download-folder-as-zip`;

                        // Verify the download URI returns code 200 and has the correct headers
                        cy.request({
                            url: download_uri,
                        }).then((response) => {
                            expect(response.status).to.equal(200);
                            expect(response.headers["content-type"]).to.equal("application/zip");
                            expect(response.headers["content-disposition"]).to.equal(
                                'attachment; filename="Folder download.zip"'
                            );
                        });

                        // Open quick look so we can delete the folder
                        // button is displayed on tr::hover, so we need to force click
                        cy.get("[data-test=quick-look-button]").click({ force: true });
                    });

                deleteFolder();
            });
        });
    });

    context("Writers", function () {
        beforeEach(() => {
            disableSpecificErrorThrownByCkeditor();
        });

        it("have specifics permissions", function () {
            cy.projectAdministratorSession();
            const project_name = `document-perm-${now}`;
            cy.createNewPublicProject(project_name, "issues");
            cy.visitProjectService(project_name, "Documents");

            const document_name = `Document ${now}`;
            cy.log("Add a document");
            cy.get("[data-test=document-header-actions]").within(() => {
                cy.get("[data-test=document-item-action-new-button]").click();
                cy.get("[data-test=document-new-empty-creation-button]").click();
            });
            cy.get("[data-test=document-new-item-modal]").within(() => {
                cy.get("[data-test=document-new-item-title]").type(document_name);
                cy.get("[data-test=document-modal-submit-button-create-item]").click();
            });

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

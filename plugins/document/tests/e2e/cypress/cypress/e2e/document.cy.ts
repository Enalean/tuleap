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
    context("Project Administrators", function () {
        let project_unixname: string, public_name: string, now: number;

        before(() => {
            cy.clearSessionCookie();
            now = Date.now();

            project_unixname = "docman-" + now;
            public_name = "Docman " + now;

            cy.projectAdministratorLogin();
        });

        beforeEach(() => {
            cy.preserveSessionCookies();
        });

        it("Creates a project with docman service", () => {
            cy.visit("/project/new");
            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
            ).click();
            cy.get("[data-test=project-registration-next-button]").click();

            cy.get("[data-test=new-project-name]").type(public_name);
            cy.get("[data-test=project-shortname-slugified-section]").click();
            cy.get("[data-test=new-project-shortname]").type("{selectall}" + project_unixname);
            cy.get("[data-test=approve_tos]").click();
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=start-working]").click({
                timeout: 20000,
            });
        });

        context("Project administrators", function () {
            it("can access to admin section", function () {
                cy.visit(`${"/plugins/document/" + project_unixname + "/admin-search"}`);
                cy.contains("Properties").should("have.attr", "href").as("manage_properties_url");
            });

            it("document properties", function () {
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
                    cy.get("[data-test=document-drop-down-button]").click();

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
                cy.log("create an embed document");
                cy.visitProjectService(project_unixname, "Documents");
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=embedded]").click();

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

                cy.get(`[data-test="document-quicklook-action-button-new-version"]`).click();
                cy.get("[data-test=document-update-version-title]").type("new version");
                cy.get("[data-test=document-modal-submit-button-create-embedded-version]").click();

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
            cy.clearSessionCookie();
            cy.projectMemberLogin();
            cy.visitProjectService("document-project", "Documents");
        });

        beforeEach(() => {
            cy.preserveSessionCookies();
        });

        context("docman permissions", function () {
            it("should raise an error when user try to access to document admin page", function () {
                cy.request({
                    url: "/plugins/document/document-project/admin-search",
                    failOnStatusCode: false,
                }).then((response) => {
                    expect(response.status).to.eq(404);
                });
            });
        });

        context("Item manipulation", () => {
            before(() => {
                cy.visitProjectService("document-project", "Documents");
            });

            beforeEach(() => {
                disableSpecificErrorThrownByCkeditor();
            });

            it("user can manipulate folders", () => {
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-drop-down-button]").click();

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

                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();

                cy.get("[data-test=document-tree-content]").should("not.exist");
            });

            it("user can manipulate empty document", () => {
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                });
                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=empty]").click();

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
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=link]").click();
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
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=embedded]").click();

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
                // Create a folder
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-drop-down-button]").click();
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
                cy.get("[data-test=document-tree-content]")
                    .contains("a", "Folder download")
                    .click();

                // Create an embedded file in this folder
                cy.get("[data-test=document-header-actions]").within(() => {
                    cy.get("[data-test=document-item-action-new-button]").click();
                });

                cy.get("[data-test=document-new-item-modal]").within(() => {
                    cy.get("[data-test=embedded]").click();
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

                cy.visitProjectService("document-project", "Documents");

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
                        const download_uri = `/plugins/document/document-project/folders/${encodeURIComponent(
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

                // force: true is mandatory because on small screen button might be displayed with only an icon + ellipsis and cause following error:
                // This element '...' is not visible because it has an effective width and height of: '0 x 0' pixels.
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=document-confirm-deletion-button]").click();
            });

            it("user can navigate and manipulate items using keyboard shortcuts", () => {
                cy.get("[data-test=document-header-actions]").should("be.visible");

                testNewFolderShortcut();
                testNewItemShortcut();
                testNavigationShortcuts();
                deleteItems();
            });
        });
    });
});

function testNewFolderShortcut(): void {
    typeShortcut("b");
    cy.get("[data-test=document-new-folder-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type("First item");
            cy.get("[data-test=document-modal-submit-button-create-folder]").click();
        });
    cy.get("[data-test=document-new-folder-modal]").should("not.be.visible");
    cy.get("[data-test=folder-title]").contains("First item");
}

function testNewItemShortcut(): void {
    typeShortcut("n");
    cy.get("[data-test=document-new-item-modal]")
        .should("be.visible")
        .within(() => {
            cy.focused()
                .should("have.attr", "data-test", "document-new-item-title")
                .type("Last item");
            cy.get("[data-test=empty]").click();
            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });
    cy.get("[data-test=document-new-item-modal]").should("not.be.visible");
    cy.get("[data-test=empty-file-title]").contains("Last item");
}

function testNavigationShortcuts(): void {
    typeShortcut("{ctrl}{uparrow}");
    cy.focused().should("contain", "First item");

    typeShortcut("{downarrow}");
    cy.focused().should("contain", "Last item");
}

function deleteItems(): void {
    typeShortcut("{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");

    typeShortcut("{ctrl}{uparrow}", "{del}");
    cy.get("[data-test=document-confirm-deletion-button]").click();
    cy.get("[data-test=document-delete-item-modal]").should("not.exist");
}

function typeShortcut(...inputs: string[]): void {
    for (const input of inputs) {
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").type(input);
    }
}

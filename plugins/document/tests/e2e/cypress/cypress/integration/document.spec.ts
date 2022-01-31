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

function disableSpecificErrorThrownByCkeditor(): void {
    cy.on("uncaught:exception", (err) => {
        // the message bellow is only thrown by ckeditor, if any other js exception is thrown
        // the test will fail
        if (err.message.includes("Cannot read properties of undefined (reading 'compatMode')")) {
            return false;
        }
    });
}

function createAWikiDocument(document_title: string, page_name: string): void {
    cy.get("[data-test=document-header-actions]").within(() => {
        cy.get("[data-test=document-item-action-new-button]").click();
    });

    cy.get("[data-test=document-new-item-modal]").within(() => {
        cy.get("[data-test=wiki]").click();

        cy.get("[data-test=document-new-item-title]").type(document_title);
        cy.get("[data-test=document-new-item-wiki-page-name]").type(page_name);
        cy.get("[data-test=document-modal-submit-button]").click();
    });
}

function updateWikiPage(page_content: string): void {
    cy.get("[data-test=php-wiki-edit-page]").contains("Edit").click();
    cy.get("[data-test=textarea-wiki-content]").clear().type(page_content);
    cy.get("[data-test=edit-page-action-buttons]").contains("Save").click();
}

describe("Document new UI", () => {
    before(() => {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
        cy.visitProjectService("document-project", "Documents");
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    it("has an empty state", () => {
        cy.get("[data-test=document-empty-state]");
        cy.get("[data-test=docman-new-item-button]");
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
                cy.get("[data-test=document-metadata-description]").type(
                    "With a description because I like to describe what I'm doing"
                );

                cy.get("[data-test=document-modal-submit-button]").click();
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
                cy.get("[data-test=document-modal-submit-button]").click();
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
                cy.get("[data-test=document-modal-submit-button]").click();
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
                cy.get("[data-test=document-new-item-link-url]").type("https://example-bis.com");

                cy.get("[data-test=document-modal-submit-button]").click();
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
                cy.get("[data-test=document-modal-submit-button]").click();
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
                cy.get("[data-test=document-new-folder-creation-button]").click();
            });

            cy.get("[data-test=document-new-folder-modal]").within(() => {
                cy.get("[data-test=document-new-item-title]").type("Folder download");
                cy.get("[data-test=document-modal-submit-button]").click();
            });

            // Go to the folder
            cy.get("[data-test=document-tree-content]").contains("a", "Folder download").click();

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

                cy.get("[data-test=document-modal-submit-button]").click();
            });

            cy.visitProjectService("document-project", "Documents");

            cy.get("[data-test=document-tree-content]")
                .contains("tr", "Folder download")
                .within(($row) => {
                    // We cannot click the download button, otherwise the browser will ask "Where to save this file ?"
                    // and will stop the test.
                    cy.get("[data-test=document-dropdown-download-folder-as-zip]").should("exist");
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

            cy.get("[data-test=document-tree-content]").should("not.exist");
        });

        it("user can navigate and manipulate items using keyboard shortcuts", () => {
            cy.get("[data-test=document-header-actions]").should("be.visible");

            testNewFolderShortcut();
            testNewItemShortcut();
            testNavigationShortcuts();
            deleteItems();
        });

        context("phpwiki integration", function () {
            it("permissions", function () {
                cy.userLogout();
                cy.projectAdministratorLogin();

                cy.visitProjectService("document-project", "Wiki");
                cy.log("Create wiki service only when it's needed");
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").then((body) => {
                    if (body.find("[data-test=create-wiki]").length > 0) {
                        cy.get("[data-test=create-wiki]").click();
                    }
                });

                const now = Date.now();

                cy.log("wiki document have their permissions in document service");

                cy.visitProjectService("document-project", "Documents");
                createAWikiDocument(`private${now}`, "My Wiki & Page document");
                cy.get("[data-test=wiki-document-link]").last().click();

                // ignore rule for phpwiki generated content
                updateWikiPage("My wiki content");
                updateWikiPage("My wiki content updated");
                cy.get("[data-test=main-content]").contains("My Wiki & Page document");

                cy.visitProjectService("document-project", "Wiki");
                cy.get("[data-test=wiki-admin]").click();
                cy.get("[data-test=manage-wiki-page]").click();

                cy.log("Document delegated permissions");
                cy.get("[data-test=table-test]")
                    .first()
                    .contains("Permissions controlled by documents manager");

                cy.log("Wiki permissions");
                cy.get("[data-test=table-test]").last().contains("[Define Permissions]");

                cy.log("Document events");
                cy.visitProjectService("document-project", "Documents");
                cy.get("[data-test=document-tree-content]").contains("tr", `private${now}`).click();
                cy.get("[data-test=document-history]").last().click({ force: true });

                cy.get("[data-test=table-test]").contains("Wiki page content change");
                cy.get("[data-test=table-test]").contains("Create");

                cy.log("project member can not see document when lack of permissions");
                cy.visitProjectService("document-project", "Documents");
                cy.get("[data-test=document-tree-content]").contains("tr", `private${now}`).click();

                cy.get("[data-test=document-permissions]").last().click({ force: true });

                cy.get("[data-test=document-permission-Reader]").select("Project administrators");
                cy.get("[data-test=document-permission-Writer]").select("Project administrators");
                cy.get("[data-test=document-permission-Manager]").select("Project administrators");
                cy.get("[data-test=document-modal-submit-button]").last().click();

                cy.log("wiki page have their permissions in wiki service");

                cy.get("[data-test=quick-look-button]").last().click({ force: true });

                let current_url;
                cy.url().then((url) => {
                    current_url = url;

                    cy.userLogout();
                    cy.projectMemberLogin();

                    cy.visit(current_url);
                });

                cy.get("[data-test=document-user-can-not-read-document]").contains(
                    "granted read permission"
                );

                cy.userLogout();
                cy.projectAdministratorLogin();

                cy.log("Delete wiki page");
                cy.visitProjectService("document-project", "Documents");
                cy.get("[data-test=document-tree-content]").contains("tr", `private${now}`).click();
                cy.get("[data-test=quick-look-button]").last().click({ force: true });
                cy.get("[data-test=document-quick-look]");
                cy.get("[data-test=document-quick-look-delete-button]").click({ force: true });
                cy.get("[data-test=delete-associated-wiki-page-checkbox]").click();
                cy.get("[data-test=document-confirm-deletion-button]").click();
            });

            it("document", function () {
                cy.userLogout();
                cy.projectAdministratorLogin();

                cy.log("Create the wiki service");

                cy.userLogout();
                cy.projectMemberLogin();

                cy.visitProjectService("document-project", "Documents");

                cy.log("multiple document can references the same wiki page");

                const now = Date.now();

                createAWikiDocument(`A wiki document${now}`, "Wiki page");
                createAWikiDocument(`An other wiki document${now}`, "Wiki page");
                createAWikiDocument(`A third wiki document${now}`, "Wiki page");

                cy.get("[data-test=wiki-document-link]").first().click();

                cy.get("[data-test=wiki-document-location-toggle]").click();
                cy.get("[data-test=wiki-document-location]").contains(`A wiki document${now}`);
                cy.get("[data-test=wiki-document-location]").contains(
                    `An other wiki document${now}`
                );
                cy.get("[data-test=wiki-document-location]").contains(
                    `A third wiki document${now}`
                );
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
            cy.get("[data-test=document-modal-submit-button]").click();
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
            cy.get("[data-test=document-modal-submit-button]").click();
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

    cy.get("[data-test=document-tree-content]").should("not.exist");
}

function typeShortcut(...inputs: string[]): void {
    for (const input of inputs) {
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").type(input);
    }
}

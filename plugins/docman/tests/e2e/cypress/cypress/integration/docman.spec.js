/**
 * Copyright (c) Enalean, 2018-present. All Rights Reserved.
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

describe("Docman", function () {
    before(function () {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.ProjectAdministratorLogin();

        cy.getProjectId("docman-project").as("docman_project_id");
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    context("document properties", function () {
        it("switch back on legacy ui", function () {
            //document is available on new instance, so we must switch back to old UI
            //because, even if we call old UI, as the project has no custom metadata we'll be redirected on new UI
            cy.visit(`/plugins/docman/?group_id=${this.docman_project_id}`);
            cy.get("[data-test=document-switch-to-old-ui]").click();

            cy.get(".toolbar").contains("Admin").click();
            cy.contains("Manage Properties")
                .should("have.attr", "href")
                .as("manage_properties_url");
        });

        it("cannot create a document when a mandatory property is not filled", function () {
            cy.visit(this.manage_properties_url);

            cy.get("[data-test=metadata_name]").type("my custom property");
            cy.get("[data-test=empty_allowed]").uncheck();
            cy.get("[data-test=use_it]").check();
            cy.get("[data-test=admin_create_metadata]").submit();

            cy.contains("New document").click();
            cy.get("[data-test=create_document_next]").click();
            cy.get("#title").type("my document title");
            cy.get('[type="radio"]').check("4");
            cy.get(".cke_wysiwyg_frame").type("my content");
            cy.get("#docman_new_form").submit();

            cy.get("[data-test=feedback]").contains(
                '"my custom property" is required, please fill the field.'
            );
        });

        it("cannot create a property with an empty name", function () {
            cy.visit(this.manage_properties_url);

            cy.get("[data-test=metadata_name]").type("  ");
            cy.get("[data-test=empty_allowed]").uncheck();
            cy.get("[data-test=use_it]").check();
            cy.get("[data-test=admin_create_metadata]").submit();

            cy.get("[data-test=feedback]").contains(
                "Property name is required, please fill this field."
            );
        });

        it("create a folder with mandatory properties", function () {
            cy.contains("New document").click();
            cy.get("[data-test=document_type]").select("1");
            cy.get("[data-test=create_document_next]").click();

            cy.get("[data-test=docman_new_item]").contains("my custom property");
        });

        it("remove a property", function () {
            cy.visit(this.manage_properties_url);
            cy.get('[href*="action=admin_delete_metadata"]').click();

            cy.get("[data-test=feedback]").contains('"my custom property" successfully deleted');
        });
    });

    context("document versioning", function () {
        it("create an embed document", function () {
            cy.contains("New document").click();
            cy.get("[data-test=create_document_next]").click();
            cy.get("#title").type("my document title");

            cy.on("uncaught:exception", (err) => {
                // the message bellow is only thown by ckeditor, if any other js exception is thrown
                // the test will fail
                expect(err.message).to.include("Cannot read property 'compatMode' of undefined");
                return false;
            });

            cy.get('[type="radio"]').check("4");
            cy.window().then((win) => {
                win.CKEDITOR.instances.embedded_content.setData("<p>my content</p>");
            });
            cy.get("#docman_new_form").submit();

            cy.get("[data-test=feedback]").contains("Document successfully created.");
            cy.contains("my document title").click();

            cy.get("[data-test=document_item]").then(($new_document_id) => {
                cy.wrap($new_document_id.data("test-document-id")).as("embedded_document_id");
            });
        });

        it("create a new version of a document", function () {
            cy.get(
                `[data-test=document_item][data-test-document-id=${this.embedded_document_id}]`
            ).click();
            cy.get(".docman_item_menu").contains("New version").click();
            cy.get("[data-test=docman_changelog]").type("new version");

            cy.get("[data-test=docman_create_new_version]").click();

            cy.get("[data-test=feedback]").contains("New version successfully created.");
        });

        it("delete a given version of a document", function () {
            cy.get(
                `[data-test=document_item][data-test-document-id=${this.embedded_document_id}]`
            ).click();
            cy.get(".docman_item_menu").contains("History").click();
            cy.get('[href*="action=confirmDelete"]').first().click();
            cy.get('[name="confirm"]').click();

            cy.get("[data-test=feedback]").contains("successfully deleted");
        });

        it("throw an error when you try to delete the last version of a document", function () {
            cy.get(
                `[data-test=document_item][data-test-document-id=${this.embedded_document_id}]`
            ).click();
            cy.get(".docman_item_menu").contains("History").click();
            cy.get('[href*="action=confirmDelete"]').first().click();
            cy.get('[name="confirm"]').click();

            cy.get("[data-test=feedback]").contains(
                "Cannot delete last version of a file. If you want to continue, please delete the document itself."
            );
        });
    });

    context("folder creation", function () {
        it("create a folder", function () {
            cy.contains("New document").click();
            cy.get("[data-test=document_type]").select("1");
            cy.get("[data-test=create_document_next]").click();

            cy.get("#title").type("my folder name");

            cy.get("[data-test=docman_create]").click();

            cy.get("[data-test=feedback]").contains("Document successfully created.");
            cy.contains("my folder name");
        });
    });

    context("easy search", function () {
        it("should search items by name", function () {
            cy.get("[data-test=docman_search]").type("folder");
            cy.get("[data-test=docman_search_button]").click();

            cy.get("[data-test=docman_report_table]").contains("my folder name");
        });

        it("should expand result", function () {
            cy.get("[data-test=docman_report_search]").click();
            cy.get("[data-test=docman_search]").should("be.disabled");
            cy.get("[data-test=docman_form_table]").contains("Global text search");
        });
    });
});

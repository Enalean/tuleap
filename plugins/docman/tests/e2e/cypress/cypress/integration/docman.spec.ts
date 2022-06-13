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
        cy.getProjectId(project_unixname).as("project_id");
    });

    context("Project administrators", function () {
        it("can access to admin section", function () {
            cy.visit("/plugins/docman/?group_id=" + this.project_id + "&action=admin");
        });

        context("document properties", function () {
            it("switch back on legacy ui", function () {
                //document is available on new instance, so we must switch back to old UI
                //because, even if we call old UI, as the project has no custom metadata we'll be redirected on new UI
                cy.visitServiceInCurrentProject("Documents");
                cy.get("[data-test=document-switch-to-old-ui]").click();

                cy.get("[data-test=toolbar]").contains("Admin").click();
                cy.contains("Properties").should("have.attr", "href").as("manage_properties_url");
            });

            it("cannot create a document when a mandatory property is not filled", function () {
                cy.visit(this.manage_properties_url);
                cy.get("[data-test=docman-admin-properties-create-button]").click();

                cy.get("[data-test=metadata_name]").type("my custom property");
                cy.get("[data-test=empty_allowed]").uncheck();
                cy.get("[data-test=use_it]").check();
                cy.get("[data-test=admin_create_metadata]").submit();

                cy.get("[data-test=project-documentation]").click();
                cy.get("[data-test=new-document]").click();
                cy.get("[data-test=create_document_next]").click();
                cy.get("[data-test=title]").type("my document title");
                cy.get("[data-test=item_type_4]").check();

                // ignore rule for ckeditor
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get(".cke_wysiwyg_frame").type("my content");
                cy.get("[data-test=docman_new_form]").submit();

                cy.get("[data-test=feedback]").contains(
                    '"my custom property" is required, please fill the field.'
                );
            });

            it("cannot create a property with an empty name", function () {
                cy.visit(this.manage_properties_url);
                cy.get("[data-test=docman-admin-properties-create-button]").click();

                cy.get("[data-test=metadata_name]").type("  ");
                cy.get("[data-test=empty_allowed]").uncheck();
                cy.get("[data-test=use_it]").check();
                cy.get("[data-test=admin_create_metadata]").submit();

                cy.get("[data-test=feedback]").contains(
                    "Property name is required, please fill this field."
                );
            });

            it("create a folder with mandatory properties", function () {
                cy.get("[data-test=project-documentation]").click();
                cy.get("[data-test=new-document]").click();
                cy.get("[data-test=document_type]").select("1");
                cy.get("[data-test=create_document_next]").click();

                cy.get("[data-test=docman_new_item]").contains("my custom property");
            });

            it("remove a property", function () {
                cy.visit(this.manage_properties_url);
                cy.get("[data-test=docman-admin-properties-delete-button]").click();
                cy.get("[data-test=docman-admin-properties-delete-confirm-button]").click();

                cy.get("[data-test=feedback]").contains(
                    '"my custom property" successfully deleted'
                );
            });
        });

        context("document versioning", function () {
            it("create an embed document", function () {
                cy.get("[data-test=project-documentation]").click();
                cy.get("[data-test=new-document]").click();
                cy.get("[data-test=create_document_next]").click();
                cy.get("[data-test=title]").type("my document title");

                cy.on("uncaught:exception", (err) => {
                    // the message bellow is only thrown by ckeditor, if any other js exception is thrown
                    // the test will fail
                    expect(err.message).to.include(
                        "Cannot read properties of undefined (reading 'compatMode')"
                    );
                    return false;
                });

                cy.get("[data-test=item_type_4]").check();
                cy.window().then((win) => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    win.CKEDITOR.instances.embedded_content.setData("<p>my content</p>");
                });
                cy.get("[data-test=docman_new_form]").submit();

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
                cy.get("[data-test=new-version]").click();
                cy.get("[data-test=docman_changelog]").type("new version");

                cy.get("[data-test=docman_create_new_version]").click();

                cy.get("[data-test=feedback]").contains("New version successfully created.");
            });

            it("delete a given version of a document", function () {
                cy.get(
                    `[data-test=document_item][data-test-document-id=${this.embedded_document_id}]`
                ).click();
                cy.get("[data-test=history]").click();
                cy.get(`[data-test=delete-${this.embedded_document_id}-2]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains("successfully deleted");
            });

            it("throw an error when you try to delete the last version of a document", function () {
                cy.get(
                    `[data-test=document_item][data-test-document-id=${this.embedded_document_id}]`
                ).click();
                cy.get("[data-test=history]").click();
                cy.get(`[data-test=delete-${this.embedded_document_id}-1]`).click();
                cy.get("[data-test=confirm-deletion]").click();

                cy.get("[data-test=feedback]").contains(
                    "Cannot delete last version of a file. If you want to continue, please delete the document itself."
                );
            });
        });

        context("folder creation", function () {
            it("create a folder", function () {
                cy.get("[data-test=new-document]").click();
                cy.get("[data-test=document_type]").select("1");
                cy.get("[data-test=create_document_next]").click();

                cy.get("[data-test=title]").type("my folder name");

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

    context("Project members", function () {
        before(function () {
            cy.visitProjectAdministrationInCurrentProject();
            cy.get(
                "[data-test=project-admin-members-add-user-select] + .select2-container"
            ).click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type(`ProjectMember{enter}`);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.clearSessionCookie();
            cy.projectMemberLogin();
        });

        beforeEach(() => {
            cy.preserveSessionCookies();
        });

        it("switch back on legacy ui", function () {
            //document is available on new instance, so we must switch back to old UI
            //because, even if we call old UI, as the project has no custom metadata we'll be redirected on new UI
            cy.visitProjectService(project_unixname, "Documents");
            cy.get("[data-test=document-switch-to-old-ui]").click();
        });

        context("docman permissions", function () {
            it("should raise an error when user try to access to docman admin page", function () {
                cy.visit("/plugins/docman/?group_id=" + this.project_id + "&action=admin");

                cy.get("[data-test=feedback]").contains(
                    "You do not have sufficient access rights to administrate the document manager."
                );
            });
        });
        context("advanced search", function () {
            it("create an empty document", function () {
                cy.get("[data-test=new-document]").click();
                cy.get("[data-test=create_document_next]").click();
                cy.get("[data-test=title]").type("pattern");

                cy.get("[data-test=item_type_6]").check();
                cy.get("[data-test=docman_new_form]").submit();

                cy.get("[data-test=feedback]").contains("Document successfully created.");
                cy.contains("pattern");
            });
            it("adds the 'Title' criteria", function () {
                cy.get("[data-test=docman_report_search]").click();

                cy.get("[data-test=title-filter]").should("not.exist", "Title");
                cy.get("[data-test=plugin_docman_report_add_filter]").select("title");
                cy.get("[data-test=docman_form_table]").contains("Global text search");
                cy.get("[data-test=title-filter]").should("exist", "Title");
            });
            it("researches the file according to some patterns", function () {
                cy.get("[data-test=title-filter]").clear().type("pattern{enter}");
                cy.contains("pattern");

                cy.get("[data-test=title-filter]").clear().type("*ttern{enter}");
                cy.contains("pattern");

                cy.get("[data-test=title-filter]").clear().type("*tte*{enter}");
                cy.contains("pattern");

                cy.get("[data-test=title-filter]").clear().type("pattern*{enter}");
                cy.contains("pattern");

                cy.get("[data-test=title-filter]").clear().type("*pattern*{enter}");
                cy.contains("pattern");
            });
            it("removes the 'Title' search field", function () {
                cy.get("[data-test=html_trash_link]").eq(0).click();
                cy.on("window:confirm", (str) => {
                    expect(str).to.equal(
                        `Are you sure you want to remove this filter from the list?`
                    );
                });
                cy.get("[data-test=docman_form_table]").contains("Global text search");
                cy.get("[data-test=title-filter]").should("not.exist", "Title");
            });
        });
    });
});

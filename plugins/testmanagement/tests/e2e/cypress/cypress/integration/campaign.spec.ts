/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

describe("TTM campaign", () => {
    let ttm_project_name: string, ttm_project_public_name: string, now: number;

    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        now = Date.now();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");

        ttm_project_name = "test-ttm-" + now;
        ttm_project_public_name = "Test TTM " + now;
    });

    context("As project administrator", () => {
        before(() => {
            cy.ProjectAdministratorLogin();
        });

        after(() => {
            cy.userLogout();
        });

        it("Creates a project with TTM", () => {
            cy.visit("/project/new");
            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-agile_alm]"
            ).click();
            cy.get("[data-test=project-registration-next-button]").click();

            cy.get("[data-test=new-project-name]").type(ttm_project_public_name);
            cy.get("[data-test=project-shortname-slugified-section]").click();
            cy.get("[data-test=new-project-shortname]").type("{selectall}" + ttm_project_name);
            cy.get("[data-test=approve_tos]").click();
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=start-working]").click({
                timeout: 20000,
            });
        });

        it("Adds user to project members", () => {
            cy.visitServiceInCurrentProject("Admin");
            cy.get("[data-test=admin-nav-members]").click();

            cy.get(
                "[data-test=project-admin-members-add-user-select] + .select2-container"
            ).click();

            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("ProjectMember{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();

            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.get("[data-test=project-admin-submit-add-member]").click();
        });
    });

    context("As project member", () => {
        before(() => {
            cy.projectMemberLogin();
        });

        it("Creates a campaign", () => {
            cy.visitProjectService(ttm_project_name, "Test Management");
            cy.get("[data-test=new-campaign-button]").click();

            cy.get("[data-test=campaign-label]").type("My first campaign");
            cy.get("[data-test=choose-tests]").select("none");
            cy.get("[data-test=create-new-campaign-button]").click();

            cy.contains("My first campaign").click();
            cy.contains("There are no tests you can see.");
        });

        context("Within the campaign", () => {
            it("Adds a test", () => {
                cy.get("[data-test=edit-campaign-button]").click();

                cy.get("[data-test=campaign-label]").type(
                    "{selectall}My first campaign with tests"
                );
                cy.get("[data-test=add-test-button]").click();

                cy.get("[data-test=artifact-modal-field-summary]").type("My first test");
                cy.get("[data-test=artifact-modal-save-button]").click();
                cy.contains("1 test will be added");

                cy.get("[data-test=edit-campaign-save-button]").click();
            });

            context("On the test", () => {
                it("Displays the test as notrun", () => {
                    cy.get("[data-test=test-title").contains("My first test").click();
                    cy.get("[data-test=current-test").should("have.class", "notrun");
                });

                it("Marks a test as passed", () => {
                    cy.get("[data-test=mark-test-as-passed]").click();
                    cy.get("[data-test=current-test").should("have.class", "passed");
                });

                it("Marks a test as failed", () => {
                    cy.get("[data-test=mark-test-as-failed]").click();
                    cy.get("[data-test=current-test").should("have.class", "failed");
                });

                it("Marks a test as blocked", () => {
                    cy.get("[data-test=mark-test-as-blocked]").click();
                    cy.get("[data-test=current-test").should("have.class", "blocked");
                });

                it("Marks a test as notrun", () => {
                    cy.get("[data-test=mark-test-as-notrun]").click();
                    cy.get("[data-test=current-test").should("have.class", "notrun");
                });

                it("Registers a comment alongside the status", () => {
                    cy.get("[data-test=current-test-comment]").then(($container) => {
                        cy.window().then((win) => {
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            win.CKEDITOR.instances[$container.attr("id")].setData(
                                "<p>This does not work! Fix ASAP!</p>"
                            );
                        });
                    });

                    cy.get("[data-test=mark-test-as-failed]").click();
                    cy.get("[data-test=current-test").should("have.class", "failed");
                    cy.get("[data-test=view-details-button]").click();
                    cy.get("[data-test=view-details-modal]").within(() => {
                        cy.contains("This does not work! Fix ASAP!");
                        cy.get("[data-dismiss=modal]").first().click();
                    });
                });

                it("Edits the test", () => {
                    cy.get("[data-test=current-test-edit]").click();
                    cy.get("[data-test=artifact-modal-field-summary]").type(
                        "{selectall}My first test edited"
                    );
                    cy.get("[data-test=artifact-modal-save-button]").click();
                    cy.get("[data-test=current-test]").should("have.class", "notrun");
                    cy.get("[data-test=current-test-header-title]").contains(
                        "My first test edited"
                    );
                });

                it("Paste an image on comment box", () => {
                    cy.get("[data-test=current-test-comment]")
                        .trigger("focus")
                        .then(($element) => {
                            fetch(
                                "data:image/gif;base64,R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs="
                            )
                                .then(function (res) {
                                    return res.arrayBuffer();
                                })
                                .then(function (buf) {
                                    const file = new File([buf], "blank.gif", {
                                        type: "image/gif",
                                    });
                                    const data_transfer = new DataTransfer();
                                    data_transfer.items.add(file);

                                    const paste_event = Object.assign(
                                        new Event("paste", { bubbles: true, cancelable: true }),
                                        {
                                            clipboardData: data_transfer,
                                        }
                                    );

                                    $element[0].dispatchEvent(paste_event);
                                });
                        });
                    cy.contains("File successfully uploaded");

                    cy.get("[data-test=mark-test-as-failed]").click();
                    cy.get("[data-test=current-test").should("have.class", "failed");
                    cy.get("[data-test=current-test-preview-latest-result]").contains(
                        "A screenshot has been attached"
                    );
                    cy.get("[data-test=view-details-button]").click();
                    cy.get("[data-test=view-details-modal]").within(() => {
                        // ignore rule for image stored in ckeditor
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("img").should("have.attr", "src").should("include", "blank.gif");
                        cy.get("[data-dismiss=modal]").first().click();
                    });
                });
            });
        });
    });
});

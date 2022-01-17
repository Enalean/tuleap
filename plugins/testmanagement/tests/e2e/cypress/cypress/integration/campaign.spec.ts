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
        cy.clearSessionCookie();
        now = Date.now();
    });

    beforeEach(function () {
        cy.preserveSessionCookies();

        ttm_project_name = "test-ttm-" + now;
        ttm_project_public_name = "Test TTM " + now;
    });

    context("As project administrator", () => {
        before(() => {
            cy.projectAdministratorLogin();
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
            cy.visitProjectAdministrationInCurrentProject();
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

        it("Create a campaign", () => {
            cy.visitProjectService(ttm_project_name, "Test Management");
            cy.get("[data-test=new-campaign-button]").click();

            cy.get("[data-test=campaign-label]").type("Test comment when campaign is closed");
            cy.get("[data-test=choose-tests]").select("none");
            cy.get("[data-test=create-new-campaign-button]").click();
        });

        it("Create 2 tests", () => {
            cy.contains("Test comment when campaign is closed").click();

            cy.get("[data-test=edit-campaign-button]").click();
            cy.get("[data-test=add-test-button]").click();
            getStringFieldWithLabel("Summary").type("My test with a comment");
            cy.get("[data-test=artifact-modal-save-button]").click();
            cy.contains("1 test will be added");
            cy.get("[data-test=edit-campaign-save-button]").click();

            cy.get("[data-test=edit-campaign-button]").click();
            cy.get("[data-test=add-test-button]").click();
            getStringFieldWithLabel("Summary").type("My test without a comment");
            cy.get("[data-test=artifact-modal-save-button]").click();
            cy.contains("1 test will be added");
            cy.get("[data-test=edit-campaign-save-button]").click();
        });

        it("Add a comment in the test", () => {
            cy.get("[data-test=test-title]").contains("My test with a comment").click();
            // eslint-disable-next-line cypress/no-unnecessary-waiting
            cy.wait(200); // Need to wait until CKEditor is loaded
            cy.get("[data-test=current-test-comment]").then(($container) => {
                cy.window().then((win) => {
                    // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                    // @ts-ignore
                    win.CKEDITOR.instances[$container.attr("id")].setData(
                        "<p>My first comment</p>"
                    );
                });
            });
            cy.get("[data-test=save-comment-button]").click({ force: true });
        });

        it("Close the campaign and checks comments section are displayed as expected", () => {
            cy.get("[data-test=test-campaign-edit-menu-trigger]").click();
            cy.get(`[data-test=test-campaign-close-campaign]`).click();

            cy.get("[data-test=test-title]").contains("My test with a comment").click();
            cy.get("[data-test=current-test-comment-preview]").contains("My first comment");

            cy.get("[data-test=test-title]").contains("My test without a comment").click();
            cy.get("[data-test=comment-footer-section]").should("not.exist");
        });
    });

    context("As project member", () => {
        before(() => {
            cy.projectMemberLogin();
        });

        context("On existing campaign", () => {
            it("should display requirement", () => {
                cy.visitProjectService("test-management-project", "Test Management");
                cy.contains("A test campaign").click();
                cy.contains("First test case").click();
                cy.get("[data-test=current-test-requirement]").contains(
                    "Iterate interactive web-readiness"
                );
            });
        });

        context("For new campaign", () => {
            it("Creates a campaign", () => {
                cy.visitProjectService(ttm_project_name, "Test Management");
                cy.get("[data-test=new-campaign-button]").click();

                cy.get("[data-test=campaign-label]").type("My first campaign");
                cy.get("[data-test=choose-tests]").select("none");
                cy.get("[data-test=create-new-campaign-button]").click();

                cy.contains("My first campaign").click();
                cy.contains("There are no tests you can see.");
            });

            it("Updates campaign label", () => {
                cy.get("[data-test=test-campaign-edit-menu-trigger]").click();
                cy.get("[data-test=test-campaign-rename-campaign]").click();

                cy.get("[data-test=campaign-label]").clear().type("My first campaign with tests");

                cy.get("[data-test=edit-campaign-label-save-button]").click();
            });

            it("Adds a test", () => {
                cy.get("[data-test=edit-campaign-button]").click();

                cy.get("[data-test=add-test-button]").click();

                getStringFieldWithLabel("Summary").type("My first test");
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
                    cy.get("[data-test=expand-details-button]").click();
                });

                it("Add another comment when one is already set", () => {
                    cy.get("[data-test=edit-comment-button]").click({ force: true });
                    cy.get("[data-test=current-test-comment]").then(($container) => {
                        cy.window().then((win) => {
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            win.CKEDITOR.instances[$container.attr("id")].setData(
                                "<p>It is ok. Fix works!</p>"
                            );
                        });
                    });
                    cy.get("[data-test=mark-test-as-passed]").click();
                    cy.get("[data-test=current-test").should("have.class", "passed");
                    cy.get("[data-test=warning-status-changed]").should("not.exist");
                    cy.get("[data-test=expand-details-button]").click();
                });

                it("Update the comment without change the status", () => {
                    cy.get("[data-test=current-test").should("have.class", "passed");
                    cy.get("[data-test=edit-comment-button]").click({ force: true });
                    cy.get("[data-test=current-test-comment]").then(($container) => {
                        cy.window().then((win) => {
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            const instance = win.CKEDITOR.instances[$container.attr("id")];
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            expect(instance.getData()).to.contain("<p>It is ok. Fix works!</p>");
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            instance.setData("<p>I confirm that. It is ok. Fix works!</p>");
                        });
                    });
                    cy.get("[data-test=save-comment-button]").click({ force: true });
                    cy.get("[data-test=current-test").should("have.class", "passed");
                });

                it("Update the status with same comment", () => {
                    cy.get("[data-test=mark-test-as-blocked]").click();
                    cy.get("[data-test=current-test").should("have.class", "blocked");
                    cy.get("[data-test=warning-status-changed]").should("exist");
                    cy.get("[data-test=current-test-comment-preview]").contains(
                        "I confirm that. It is ok. Fix works!"
                    );
                });

                it("Cancel the edition of the comment", function () {
                    cy.get("[data-test=edit-comment-button]").click({ force: true });
                    cy.get("[data-test=current-test-comment]").then(($container) => {
                        cy.window().then((win) => {
                            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                            // @ts-ignore
                            win.CKEDITOR.instances[$container.attr("id")].setData(
                                "<p>A new comment</p>"
                            );
                        });
                    });
                    cy.get("[data-test=cancel-edit-comment-button]").click({ force: true });
                    cy.get("[data-test=current-test-comment-preview]").contains(
                        "I confirm that. It is ok. Fix works!"
                    );
                });

                it("Edits the test", () => {
                    cy.get("[data-test=current-test-edit]").click();
                    getStringFieldWithLabel("Summary").type("{selectall}My first test edited");
                    cy.get("[data-test=artifact-modal-save-button]").click();
                    cy.get("[data-test=current-test]").should("have.class", "notrun");
                    cy.get("[data-test=current-test-header-title]").contains(
                        "My first test edited"
                    );
                    cy.get("[data-test=current-test-comment]").should("be.visible");
                });

                it("Paste an image on comment box", () => {
                    // Expand the comment area so that cypress can see the field on its small viewport
                    cy.get("[data-test=expand-details-button]").click();

                    cy.get("[data-test=current-test-comment]")
                        .trigger("focus", { force: true })
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
                    cy.get("[data-test=current-test-comment-preview]").within(() => {
                        // ignore rule for image stored in ckeditor
                        // eslint-disable-next-line cypress/require-data-selectors
                        cy.get("img").should("have.attr", "src").should("include", "blank.gif");
                    });
                });

                it("Remove an image from comment box", () => {
                    cy.get("[data-test=comment-file-attachment]").should("exist");
                    cy.get("[data-test=edit-comment-button]").click();
                    cy.get("[data-test=remove-attachment-file-button]").click();
                    cy.get("[data-test=save-comment-button]").click();
                    cy.get("[data-test=comment-file-attachment]").should("not.exist");
                });

                it("should allow the user to log a bug for the test", () => {
                    cy.get("[data-shortcut-new-bug]").click({ force: true });
                    getStringFieldWithLabel("Summary").type("A bug for the test");
                    cy.get("[data-test=artifact-modal-save-button]").click();
                    cy.get("[data-test=current-test-bug]").contains("A bug for the test");
                });
            });
        });
    });
});

type CypressWrapper = Cypress.Chainable<JQuery<HTMLElement>>;

function getStringFieldWithLabel(label: string): CypressWrapper {
    return cy
        .get("[data-test=string-field]")
        .contains(label)
        .parents("[data-test=string-field]")
        .within(() => {
            return cy.get("[data-test=string-field-input]");
        });
}

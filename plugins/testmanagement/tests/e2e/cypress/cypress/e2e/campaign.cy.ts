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

function createCampaign(label: string): void {
    cy.get("[data-test=new-campaign-button]").click();

    cy.get("[data-test=campaign-label]").type(label);
    cy.get("[data-test=choose-tests]").select("none");
    cy.get("[data-test=create-new-campaign-button]").click();
}

describe("TTM campaign", () => {
    let ttm_project_name: string,
        comment_project_name: string,
        file_project_name: string,
        status_project_name: string,
        now: number;

    before(() => {
        now = Date.now();
        ttm_project_name = "test-ttm-" + now;
        comment_project_name = "com-ttm-" + now;
        status_project_name = "status-ttm-" + now;
        file_project_name = "file-ttm-" + now;
    });

    it("As project administrator", () => {
        cy.log("Creates a project with TTM with users");
        cy.projectAdministratorSession();
        cy.createNewPublicProject(ttm_project_name, "agile_alm");

        cy.log("Create a campaign");
        cy.visitProjectService(ttm_project_name, "Test Management");
        cy.get("[data-test=new-campaign-button]").click();

        cy.get("[data-test=campaign-label]").type("Test comment when campaign is closed");
        cy.get("[data-test=choose-tests]").select("none");
        cy.get("[data-test=create-new-campaign-button]").click();

        cy.log("Create 2 tests");
        cy.contains("Test comment when campaign is closed").click();

        addTestInCampaign("My test with a comment");
        addTestInCampaign("My test without a comment");

        cy.log("Add a comment in the test");
        cy.get("[data-test=test-title]").contains("My test with a comment").click();
        addComment("<p>My first comment</p>");
        cy.get("[data-test=save-comment-button]").click({ force: true });

        cy.log("Close the campaign and checks comments section are displayed as expected");
        cy.get("[data-test=test-campaign-edit-menu-trigger]").click();
        cy.get(`[data-test=test-campaign-close-campaign]`).click();

        cy.get("[data-test=test-title]").contains("My test with a comment").click();
        cy.get("[data-test=current-test-comment-preview]").contains("My first comment");

        cy.get("[data-test=test-title]").contains("My test without a comment").click();
        cy.get("[data-test=comment-footer-section]").should("not.exist");
    });

    context("As project member", () => {
        it("should display requirement on existing campaign", () => {
            cy.projectMemberSession();
            cy.visitProjectService("test-management-project", "Test Management");
            cy.contains("A test campaign").click();
            cy.contains("First test case").click();
            cy.get("[data-test=current-test-requirement]").contains(
                "Iterate interactive web-readiness",
            );
        });

        it("TTM tests status", () => {
            cy.log("Creates a campaign");
            cy.projectMemberSession();
            cy.createNewPublicProject(status_project_name, "agile_alm");
            cy.visitProjectService(status_project_name, "Test Management");
            createCampaign("My first campaign");
            cy.contains("My first campaign").click();
            cy.contains("There are no tests you can see.");

            cy.log("Updates campaign label");
            cy.get("[data-test=test-campaign-edit-menu-trigger]").click();
            cy.get("[data-test=test-campaign-rename-campaign]").click();

            cy.get("[data-test=campaign-label]").clear().type("My first campaign with tests");

            cy.get("[data-test=edit-campaign-label-save-button]").click();

            addTestInCampaign("My first test");

            cy.log("On the test");
            cy.log("Displays the test as notrun");
            cy.get("[data-test=test-title").contains("My first test").click();
            cy.get("[data-test=current-test").should("have.class", "notrun");

            changeTestStatus("passed");
            changeTestStatus("failed");
            changeTestStatus("blocked");
            changeTestStatus("notrun");
        });
        it("TTM tests comments", () => {
            cy.projectMemberSession();
            cy.createNewPublicProject(comment_project_name, "agile_alm");
            cy.visitProjectService(comment_project_name, "Test Management");

            createCampaign("My first campaign");
            cy.contains("My first campaign").click();
            addTestInCampaign("My comment test");
            cy.get("[data-test=test-title]").click();

            cy.log("Registers a comment alongside the status");
            addComment("<p>This does not work! Fix ASAP!</p>");

            changeTestStatus("failed");
            cy.get("[data-test=expand-details-button]").click();

            cy.log("Add another comment when one is already set");
            cy.get("[data-test=edit-comment-button]").click({ force: true });
            addComment("<p>It is ok. Fix works!</p>");
            changeTestStatus("passed");
            cy.get("[data-test=warning-status-changed]").should("not.exist");
            cy.get("[data-test=expand-details-button]").click();

            cy.log("Update the comment without change the status");
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

            cy.log("Update the status with same comment");
            changeTestStatus("blocked");
            cy.get("[data-test=warning-status-changed]").should("exist");
            cy.get("[data-test=current-test-comment-preview]").contains(
                "I confirm that. It is ok. Fix works!",
            );

            cy.log("Cancel the edition of the comment");
            cy.get("[data-test=edit-comment-button]").click({ force: true });
            addComment("<p>A new comment</p>");
            cy.get("[data-test=cancel-edit-comment-button]").click({ force: true });
            cy.get("[data-test=current-test-comment-preview]").contains(
                "I confirm that. It is ok. Fix works!",
            );

            cy.log("Edits the test");
            cy.get("[data-test=current-test-edit]").click();
            cy.intercept("PUT", "/api/v1/testmanagement_executions/*").as("updateTest");
            getStringFieldWithLabel("Summary").type("{selectall}My first test edited");
            cy.get("[data-test=artifact-modal-save-button]").click();
            cy.get("[data-test=current-test]").should("have.class", "notrun");
            cy.wait("@updateTest", { timeout: 5000 });
            cy.get("[data-test=current-test-header-title]").contains("My first test edited");
            cy.get("[data-test=current-test-comment]").should("be.visible");
        });

        it("TTM file upload", () => {
            cy.projectMemberSession();
            cy.createNewPublicProject(file_project_name, "agile_alm");
            cy.visitProjectService(file_project_name, "Test Management");

            createCampaign("My first campaign");
            cy.contains("My first campaign").click();
            addTestInCampaign("My file test");
            cy.get("[data-test=test-title]").click();

            cy.log("Paste an image on comment box");
            // Expand the comment area so that cypress can see the field on its small viewport
            cy.get("[data-test=expand-details-button]").click();

            // eslint-disable-next-line cypress/no-unnecessary-waiting
            cy.wait(1000); // Need to wait until CKEditor is loaded
            cy.get("[data-test=current-test-comment]")
                .trigger("focus", { force: true })
                .then(($element) => {
                    fetch("data:image/gif;base64,R0lGODdhAQABAIAAAP///////ywAAAAAAQABAAACAkQBADs=")
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
                                },
                            );

                            $element[0].dispatchEvent(paste_event);
                        });
                });
            cy.contains("File successfully uploaded");

            changeTestStatus("failed");
            cy.get("[data-test=current-test-comment-preview]").within(() => {
                // ignore rule for image stored in ckeditor
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("img").should("have.attr", "src").should("include", "blank.gif");
            });

            cy.log("Remove an image from comment box");
            cy.get("[data-test=comment-file-attachment]").should("exist");
            cy.get("[data-test=edit-comment-button]").click();
            cy.get("[data-test=remove-attachment-file-button]").click();
            cy.get("[data-test=save-comment-button]").click();
            cy.get("[data-test=comment-file-attachment]").should("not.exist");

            cy.log("should allow the user to log a bug for the test");
            cy.get("[data-shortcut-new-bug]").click({ force: true });
            getStringFieldWithLabel("Summary").type("A bug for the test");
            cy.get("[data-test=artifact-modal-save-button]").click();
            cy.get("[data-test=current-test-bug]").contains("A bug for the test");
        });
    });
});

type CypressWrapper = Cypress.Chainable<JQuery<HTMLElement>>;

function getStringFieldWithLabel(label: string): CypressWrapper {
    return cy.getContains("[data-test=string-field]", label).within(() => {
        return cy.get("[data-test=string-field-input]");
    });
}

function changeTestStatus(new_status: "passed" | "failed" | "notrun" | "blocked"): void {
    cy.log(`Marks a test as ${new_status}`);
    cy.get(`[data-test=mark-test-as-${new_status}]`).click();
    cy.get("[data-test=current-test").should("have.class", new_status);
}

function addTestInCampaign(test_label: string): void {
    cy.get("[data-test=edit-campaign-button]").click();
    cy.get("[data-test=add-test-button]").click();
    getStringFieldWithLabel("Summary").type(test_label);
    cy.get("[data-test=artifact-modal-save-button]").click();
    cy.contains("1 test will be added");
    cy.get("[data-test=edit-campaign-save-button]").click();
}

function addComment(comment: string): void {
    // eslint-disable-next-line cypress/no-unnecessary-waiting
    cy.wait(1000); // Need to wait until CKEditor is loaded
    cy.get("[data-test=current-test-comment]").then(($container) => {
        cy.window().then((win) => {
            // eslint-disable-next-line @typescript-eslint/ban-ts-comment
            // @ts-ignore
            win.CKEDITOR.instances[$container.attr("id")].setData(comment);
        });
    });
}

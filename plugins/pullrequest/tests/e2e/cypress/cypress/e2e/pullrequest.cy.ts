/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

function createOrNavigateToPullRequest(): void {
    cy.log("Create a pull request");
    cy.visit("/plugins/git/pullrequests/Awesomness");
    cy.get("[data-test=count-to-display]").then(($open_pullrequest) => {
        cy.log("Create pull request if needed");
        if (parseInt($open_pullrequest.html(), 10) === 0) {
            cy.get("[data-test=create-pull-request]").click();
            cy.get("[data-test=pull-request-source-branch]").select("main");
            cy.get("[data-test=pull-request-destination-branch]").select("stable");
            cy.get("[data-test=pull-request-create-button]").click();
        } else if (parseInt($open_pullrequest.html(), 10) >= 1) {
            cy.get("[data-test=tabs-pullrequest]").click();
            cy.get("[data-test=pull-request-card]").click();
        }
    });
}

describe("Pull request", function () {
    context("Project members", function () {
        it("can manage labels on pull requests", function () {
            cy.projectMemberSession();
            createOrNavigateToPullRequest();
            cy.log("Create label Emergency");

            cy.get("[data-test=manage-labels-button]").click();
            cy.get("[data-test=manage-labels-modal]").within(() => {
                cy.addItemInLazyboxDropdown("Emergency");
            });
            cy.get("[data-test=save-labels-button]").click({ force: true });
            cy.get("[data-test=pull-request-label]").contains("Emergency");

            cy.log("Create label Easy fix");
            cy.get("[data-test=manage-labels-button]").click();
            cy.get("[data-test=manage-labels-modal]").within(() => {
                cy.addItemInLazyboxDropdown("Easy fix");
            });
            cy.get("[data-test=save-labels-button]").click({ force: true });
            cy.get("[data-test=pull-request-label]").contains("Emergency");
            cy.get("[data-test=pull-request-label]").contains("Easy fix");

            cy.log("Check pull request has the two labels in overview");
            cy.get("[data-test=tabs-pullrequest]").click();
            cy.get("[data-test=pull-request-card-labels]").contains("Emergency");
            cy.get("[data-test=pull-request-card-labels]").contains("Easy fix");

            cy.log("Remove label Emergency from pull request");
            cy.get("[data-test=pull-request-card]").click();
            cy.get("[data-test=manage-labels-button]").click();
            cy.get("[data-test=lazybox]")
                .find("[data-test=remove-selection]", { includeShadowDom: true })
                .first()
                .click();
            cy.get("[data-test=save-labels-button]").click({ force: true });

            cy.get("[data-test=tabs-pullrequest]").click();
            cy.get("[data-test=pull-request-card-labels]").contains("Easy fix");

            cy.projectAdministratorSession();
            cy.visitProjectAdministration("pullrequests");
            cy.get("[data-test=labels]").click({ force: true });
            cy.getContains("[data-test=label-row]", "Emergency").within(() => {
                cy.get("[data-test=label_is_used]").should("not.exist");
            });

            cy.getContains("[data-test=label-row]", "Easy fix").within(() => {
                cy.get("[data-test=label_is_used]");
                cy.get("[data-test=delete-project-label]").click();
            });
            cy.get("[data-test=confirm-delete-label]").first().click();

            cy.projectMemberSession();
            cy.visit("/plugins/git/pullrequests/Awesomness");
            cy.get("[data-test=tabs-pullrequest]").click();
            cy.get("[data-test=pull-request-card-labels]").should("be.empty");
        });

        it("can manage pull requests", function () {
            cy.projectMemberSession();
            createOrNavigateToPullRequest();

            cy.log("Edit title of a pullrequest");
            cy.get("[data-test=pull-request-open-title-modal-button]").click();
            cy.get("[data-test=pull-request-edit-title-input]").clear().type("My updated title");
            cy.get("[data-test=pull-request-save-changes-button]").click();
            cy.get("[data-test=pullrequest-title]").contains("My updated title");

            cy.log("Edit description of a pullrequest");
            cy.get("[data-test=button-edit-description-comment]").click();
            cy.get("[data-test=writing-zone-textarea]").first().clear().type("My description");
            cy.get("[data-test=button-save-edition]").click();
            cy.get("[data-test=description-content]").contains("My description");

            cy.log("Add reviewers pullrequest");
            cy.get("[data-test=edit-reviewers-button]").click();
            cy.get("[data-test=manage-reviewers-modal]").within(() => {
                cy.searchItemInLazyboxDropdown(
                    "ARegularUser",
                    "ARegularUser (ARegularUser)",
                ).click();
            });
            cy.get("[data-test=save-reviewers-button]").click({ force: true });
            cy.get("[data-test=pull-request-reviewers-empty-state]").should("not.exist");

            cy.log("can browse the commits of the pull request");
            cy.get("[data-test=pullrequest-navigation-tabs] [data-test=tab-commits]").click();

            cy.get("[data-test=pullrequest-commits-list-commit]").contains("4858682a8f");
        });
    });
});

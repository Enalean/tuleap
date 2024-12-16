/**
 * Copyright (c) Enalean 2024 - Present. All Rights Reserved.
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

describe("Artifact Mass Change", function () {
    beforeEach(function () {
        cy.projectAdministratorSession();
        const now = Date.now();
        const project_name = `masschange-${now}`;
        cy.createNewPublicProject(project_name, "issues").then((project_id) => {
            cy.addProjectMember(project_name, "ProjectMember");
            cy.projectAdministratorSession();
            cy.getTrackerIdFromREST(project_id, "issue")
                .as("issue_tracker_id")
                .then((issue_tracker_id) => {
                    cy.visit(
                        `/plugins/tracker/?tracker=${issue_tracker_id}&func=admin-perms-tracker`,
                    );
                    cy.get("[data-test=permissions_3]").select("are admin of the tracker");
                    cy.get("[data-test=tracker-permission-submit]").click();

                    cy.projectMemberSession();
                    for (const n of [1, 2, 3, 4]) {
                        cy.createArtifactWithFields({
                            tracker_id: issue_tracker_id,
                            fields: [
                                {
                                    shortname: "title",
                                    value: `Issue ${n}`,
                                },
                            ],
                        });
                    }
                });
        });
    });

    it("Updates selected artifacts", function () {
        cy.projectMemberSession();
        cy.visit(`/plugins/tracker/?tracker=${this.issue_tracker_id}`);
        cy.log("Update 2 artifacts");
        cy.get("[data-test=masschange-button]").click();
        cy.get("[data-test=masschange-checkbox]").eq(0).click();
        cy.get("[data-test=masschange-checkbox]").eq(1).click();
        cy.get("[data-test=masschange-button-checked]").click();
        cy.getContains("[data-test=field-masschange]", "Status").within(() => {
            cy.searchItemInListPickerDropdown("Canceled").click();
        });
        cy.get("[data-test=masschange-submit]").click();

        cy.log("Check 2 artifacts were modified");
        cy.get("[data-test=expert-mode]").click();
        cy.get("[data-test=expert-report-form]")
            .find("[role=textbox]")
            .invoke("text", "status='Canceled'");
        cy.get("[data-test=expert-query-submit-button]").click();
        cy.get("[data-test=tracker-report-table-results-artifact]").should("have.length", 2);
    });

    it("Sends mail to mentioned user", function () {
        cy.projectMemberSession();
        cy.visit(`/plugins/tracker/?tracker=${this.issue_tracker_id}`);
        cy.get("[data-test=masschange-button]").click();
        cy.get("[data-test=masschange-button-all").click();
        cy.get("[data-test=masschange-new-comment").clear().type("Hello @ProjectAdministrator");
        cy.get("[data-test=masschange-submit]").click();
        cy.assertEmailWithContentReceived(
            "ProjectAdministrator@example.com",
            "Hello @ProjectAdministrator",
        );
    });
});

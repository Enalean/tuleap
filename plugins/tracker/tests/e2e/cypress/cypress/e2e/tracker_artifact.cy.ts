/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

function submitAndStay(): void {
    cy.get("[data-test=artifact-submit-options]").click();
    cy.get("[data-test=artifact-submit-and-stay]").click();
}

function createNewBug(bug_title: string): void {
    cy.visitProjectService("tracker-artifact", "Trackers");
    cy.get("[data-test=tracker-link-bug]").click();
    cy.get("[data-test=create-new]").click();
    cy.get("[data-test=create-new-item]").first().click();
    cy.get("[data-test=summary]").type(bug_title);
}

describe("Tracker artifacts", function () {
    const TITLE_FIELD_NAME = "summary";

    describe("Site admin specific settings for move/deletion", function () {
        it("must be able to set the artifact deletion setting", function () {
            cy.siteAdministratorSession();
            cy.visit("/");

            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=admin-tracker]").click();
            cy.get("[data-test=artifact-deletion]").click();
            cy.get("[data-test=input-artifacts-limit]").clear().type("50");
            cy.get("[data-test=artifact-deletion-button]").click();
            cy.get("[data-test=feedback]").contains("Limit successfully updated.");
        });
    });

    describe("Tracker administration", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("tracker-artifact").as("project_id");
        });

        it("can access to admin section", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visit("/plugins/tracker/global-admin/" + this.project_id);
        });

        it("must be able to create tracker from Tuleap template Bug", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-default-bug]").click({ force: true });

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type(Date.now() + " from cypress");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });

        it("must be able to create tracker from empty", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-tracker_empty]").click({ force: true });

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type(Date.now() + " From empty");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });

        it("must be able to create tracker from an other project", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-tracker_another_project]").click({ force: true });

            cy.get("[data-test=project-select]").select("Empty Followup");
            cy.get("[data-test=project-tracker-select]").select("Bugs");

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type(Date.now() + " From an other project");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });

        it("can add a report on the project home page", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=tracker-link-artifact_link]").click();
            cy.get("[data-test=add-to-project-dashboard]").click();

            cy.get("[data-test=artifact-report-table]").contains("test A");
            cy.get("[data-test=artifact-report-table]").contains("test B");
        });
    });

    describe("Tracker regular users", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("tracker-artifact").as("project_id");
        });

        beforeEach(function () {
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").as("body");
        });

        describe("Artifact manipulation", function () {
            it("must be able to create new artifact", function () {
                cy.projectMemberSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                createNewBug("My new bug");
                submitAndStay();

                cy.get("[data-test=feedback]").contains("Artifact Successfully Created");
                cy.get("[data-test=tracker-artifact-value-summary]").contains("My new bug");

                cy.log("Created artifact must be in recent elements");
                cy.get("@body").type("{s}");
                cy.get("[data-test=switch-to-modal]").should("be.visible");

                cy.get("[data-test=switch-to-filter]").type("My new bug");
                cy.get("[data-test=switch-to-recent-items]").should("contain", "My new bug");
            });

            it("must be able to copy new artifact", function () {
                cy.projectMemberSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.getTrackerIdFromREST(parseInt(this.project_id, 10), "bug").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "copy artifact",
                        artifact_status: "New",
                        title_field_name: TITLE_FIELD_NAME,
                    }).then((artifact_id) => {
                        cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                    });
                });

                cy.get("[data-test=tracker-artifact-actions]").click();
                cy.get("[data-test=artifact-copy-button]").click();
                cy.get("[data-test=edit-field-summary]").click();
                cy.get("[data-test=summary]").clear().type("My updated summary");

                cy.get("[data-test=artifact-copy]").click();

                cy.get("[data-test=artifact-followups]").contains("Copy of bug");
                cy.get("[data-test=tracker-artifact-value-summary]").contains("My updated summary");
            });

            it("can be displayed in printer version", function () {
                cy.projectMemberSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.getTrackerIdFromREST(parseInt(this.project_id, 10), "bug").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "printer version",
                        artifact_status: "New",
                        title_field_name: TITLE_FIELD_NAME,
                    }).then((artifact_id) => {
                        cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                    });
                });

                let current_url;
                cy.url().then((url) => {
                    current_url = url;
                    cy.visit(current_url + "&pv=1");

                    // check that followup block is displayed
                    cy.get("[data-test=artifact-followups]");
                });
            });

            it("can switch from autocomputed mode to calculated mode and so on", function () {
                cy.projectMemberSession();
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.getTrackerIdFromREST(parseInt(this.project_id, 10), "bug").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "autocompute",
                        artifact_status: "New",
                        title_field_name: TITLE_FIELD_NAME,
                    }).then((artifact_id) => {
                        cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                    });
                });

                //edit field and set 20 as values
                cy.get("[data-test=edit-field-remaining_effort]").click();
                cy.get("[data-test=remaining_effort]").clear().type("20");

                // submit and check
                submitAndStay();
                cy.get("[data-test=computed-value]").contains(20);

                //edit field and go back in autocomputed mode
                cy.get("[data-test=edit-field-remaining_effort]").click();
                cy.get("[data-test=switch-to-autocompute]").click();

                //submit and check
                submitAndStay();
                cy.get("[data-test=computed-value]").contains("Empty");
            });
        });

        it("can add a report on his dashboard", function () {
            cy.projectMemberSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=tracker-link-artifact_link]").click();
            cy.get("[data-test=add-to-my-dashboard]").first().click({ force: true });

            cy.get("[data-test=artifact-report-table]").contains("test A");
            cy.get("[data-test=artifact-report-table]").contains("test B");
        });
    });

    describe("Tracker dedicated permissions", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("tracker-artifact").as("project_id");
        });

        it("should raise an error when user try to access to plugin Tracker admin page", function () {
            cy.projectMemberSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.request({
                url: "/plugins/tracker/global-admin/" + this.project_id,
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.eq(403);
            });
        });

        it("tracker admin must be able to delegate tracker administration privilege", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-bug]").click();

            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });

            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=tracker-permissions]").click();

            cy.get("[data-test=permissions_3]").select("are admin of the tracker");

            cy.get("[data-test=tracker-permission-submit]").click();

            cy.get("[data-test=feedback]").contains("Permissions Updated");

            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-story]").click();
            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });

            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=tracker-permissions]").click();

            cy.get("[data-test=permissions_3]").select("are admin of the tracker");

            cy.get("[data-test=tracker-permission-submit]").click();

            cy.get("[data-test=feedback]").contains("Permissions Updated");
        });

        it("regular user must be able to move artifact", function () {
            cy.projectAdministratorSession();
            cy.getTrackerIdFromREST(parseInt(this.project_id, 10), "bug").then((tracker_id) => {
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "move artifact",
                    artifact_status: "New",
                    title_field_name: TITLE_FIELD_NAME,
                }).then((artifact_id) => {
                    cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);
                });
            });

            cy.get("[data-test=tracker-artifact-actions]").click();
            cy.get("[data-test=tracker-action-button-move]").click();

            cy.get("[data-test=move-artifact-project-selector]").select("tracker artifact");
            cy.get("[data-test=move-artifact-tracker-selector]").select("Bugs for Move");

            cy.get("[data-test=move-artifact]").click();

            cy.get("[data-test=feedback]").contains("has been successfully");
            cy.get('[data-test="tracker-artifact-value-summary"]').contains("move artifact");
            cy.get('[data-test="tracker-artifact-value-status"]').contains("New");
        });

        it("user with tracker admin permissions are tracker admin", function () {
            cy.projectMemberSession();
            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-bug]").click();
            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        });
    });

    describe("Concurrent artifact edition", () => {
        it("A popup is shown to warn the user that someone has edited the artifact while he was editing it.", () => {
            cy.projectMemberSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            createNewBug("Concurrent edition test");
            submitAndStay();

            cy.get("[data-test=current-artifact-id]")
                .should("have.attr", "data-artifact-id")
                .then((artifact_id) => {
                    // Add a follow-up comment to the artifact via the REST API
                    cy.putFromTuleapApi(`https://tuleap/api/artifacts/${artifact_id}`, {
                        values: [],
                        comment: {
                            body: "I have commented this artifact while you were editing it. You mad bro?",
                            post_processed_body: "string",
                            format: "string",
                        },
                    });
                });

            cy.get("[data-test=artifact_followup_comment]").type(
                "This my freshly created artifact. Hope nobody has edited it in the meantime!",
            );

            submitAndStay();

            // Check popup is shown and submit buttons disabled
            cy.get("[data-test=concurrent-edition-popup-shown]");
            cy.get("[data-test=artifact-submit]").should("be.disabled");
            cy.get("[data-test=artifact-submit-options]").should("be.disabled");
            cy.get("[data-test=artifact-submit-and-stay]").should("be.disabled");

            // Acknowledge changes
            cy.get("[data-test=acknowledge-concurrent-edition-button]").click();

            // Check popup is hidden and submit buttons enabled
            cy.get("[data-test=concurrent-edition-popup-shown]").should("not.exist");
            cy.get("[data-test=artifact-submit]").should("not.be.disabled");
            cy.get("[data-test=artifact-submit-options]").should("not.be.disabled");
            cy.get("[data-test=artifact-submit-and-stay]").should("not.be.disabled");

            submitAndStay();

            cy.get("[data-test=artifact-follow-up]").should("have.length", 2);
        });
    });
});

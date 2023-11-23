/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

const PROJECT_NAME = "conditional-notifications";
const TRACKER_SHORTNAME = "cond_notif";

describe("Tracker notifications", () => {
    let now: number;
    it("Sends calendar events", function () {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `calendar-${now}`;
        cy.createNewPublicProject(project_name, "scrum").then((project_id) => {
            cy.addProjectMember(project_name, "projectMember");
            cy.projectAdministratorSession();

            cy.log("Add project and configure calendar option");
            cy.visitProjectService(project_name, "Trackers");
            cy.get("[data-test=tracker-link-rel]").click();
            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
            cy.get("[data-test=notifications]").click();
            cy.get("[data-test=enable-calendar-events]").click();
            cy.get("[data-test=submit-changes]").click();

            cy.get("[data-test=feedback]").contains("Calendar events configuration updated");

            cy.log("Enable tracker notifications");
            cy.get("[data-test=enable-notifications]").click();

            cy.log("Add artifact");
            cy.projectMemberSession();
            cy.getTrackerIdFromREST(project_id, "rel").then((tracker_id) => {
                const date = new Date();
                cy.createArtifactWithFields({
                    tracker_id,
                    fields: [
                        {
                            shortname: "release_number",
                            value: "My created release",
                        },
                        {
                            shortname: "start_date",
                            value: date.toDateString(),
                        },
                        {
                            shortname: "end_date",
                            value: new Date(date.setMonth(date.getMonth() + 8)).toDateString(),
                        },
                    ],
                });
            });
            cy.assertEmailReceivedWithAttachment("forge__artifacts@tuleap", "text/calendar");
        });
    });
    it("Conditional notifications", function () {
        cy.projectAdministratorSession();

        cy.visitProjectService(PROJECT_NAME, "Trackers");
        cy.get(`[data-test=tracker-link-${TRACKER_SHORTNAME}]`).click();
        cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        cy.get("[data-test=workflow]").click();
        cy.get("[data-test=field-dependencies]").click();

        cy.get("[data-test=tracker-administration-content]").then(($field_dependency) => {
            if (
                $field_dependency.find("[data-test=existing-field-dependency-label]").length !== 0
            ) {
                return;
            }
            cy.log("Define field dependencies");
            cy.get("[data-test=source_field]").select("Category");
            cy.get("[data-test=target_field]").select("Coordinators");
            cy.get("[data-test=choose_source_button]").click();
            disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype();
            cy.get("[data-test=tracker-field-dependencies-matrix]").within(() => {
                cy.getContains("[data-test=matrix-row]", "Database").within(() => {
                    cy.get("[data-test=create-dependency]").eq(1).click({ force: true });
                });
                cy.getContains("[data-test=matrix-row]", "User interface").within(() => {
                    cy.get("[data-test=create-dependency]").eq(2).click({ force: true });
                });
                cy.getContains("[data-test=matrix-row]", "SOAP API").within(() => {
                    cy.get("[data-test=create-dependency]").eq(1).click({ force: true });
                });
            });
            cy.get("[data-test=create-field-dependencies-button]").click();
        });

        cy.log(
            "When project admin creates an artifact and chooses Database category, then coordinator1 will receive notification",
        );
        cy.projectAdministratorSession();
        cy.visitProjectService(PROJECT_NAME, "Trackers");
        cy.get(`[data-test=tracker-link-${TRACKER_SHORTNAME}]`).click();
        cy.get("[data-test=create-new]").click();
        cy.get("[data-test=create-new-item]").first().click();
        cy.get("[data-test=summary_1]").type("My conditional notification");
        cy.get("[data-test=field-label]")
            .contains("Category")
            .parent()
            .within(() => {
                cy.searchItemInListPickerDropdown("Database").click();
            });

        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        cy.assertEmailWithContentReceived(
            "Coordinator1@example.com",
            `My conditional notification`,
        );
        cy.assertNotEmailWithContentReceived(
            "Coordinator2@example.com",
            `My conditional notification`,
        );
        cy.assertNotEmailWithContentReceived(
            "ProjectMember@example.com",
            `My conditional notification`,
        );

        cy.log(
            "When category is updated to User interface, then coordinator2 will receive notification",
        );
        cy.get("[data-test=edit-field-category]").click();
        cy.get("[data-test=edit-field-summary_1]").click();
        cy.get("[data-test=summary_1]").clear().type("My updated conditional notification");
        cy.get("[data-test=tracker-artifact-value-category]").within(() => {
            cy.searchItemInListPickerDropdown("User interface").click();
        });

        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        cy.assertEmailWithContentReceived(
            "Coordinator2@example.com",
            `My updated conditional notification`,
        );
        cy.assertNotEmailWithContentReceived(
            "Coordinator1@example.com",
            `My updated conditional notification`,
        );
        cy.assertNotEmailWithContentReceived(
            "ProjectMember@example.com",
            `My updated conditional notification`,
        );

        cy.log(
            "When artifact is assigned to project member, then project member and coordinator2 will receive a notification",
        );

        cy.get("[data-test=edit-field-assigned_to_1]").click();
        cy.get("[data-test=edit-field-summary_1]").click();
        cy.get("[data-test=summary_1]").clear().type("My third conditional notification");
        cy.get("[data-test=tracker-artifact-value-assigned_to_1]").within(() => {
            cy.searchItemInListPickerDropdown("ProjectMember").click();
        });

        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        cy.assertEmailWithContentReceived(
            "ProjectMember@example.com",
            `My third conditional notification`,
        );
        cy.assertEmailWithContentReceived(
            "Coordinator2@example.com",
            `My third conditional notification`,
        );
        cy.assertNotEmailWithContentReceived(
            "Coordinator1@example.com",
            `My third conditional notification`,
        );
    });

    describe("Notification subscription", () => {
        before(function () {
            cy.projectMemberSession();

            cy.getProjectId(PROJECT_NAME).then((project_id) => {
                cy.getTrackerIdFromREST(project_id, "notif_subscription").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: "my_label",
                        artifact_title: "My artifact notifications",
                    }).as("notifications_artifact_id");
                });
            });
        });
        it("Unsubscribe from artifact notifications", function () {
            cy.projectMemberSession();
            cy.visit(`/plugins/tracker/?aid=${this.notifications_artifact_id}`);
            cy.get("[data-test=tracker-artifact-actions]").click();
            cy.get("[data-test=notifications-button]").click();
            cy.get("[data-test=feedback]").contains(
                "You will no-longer receive notifications for this artifact",
            );

            cy.get("[data-test=artifact_followup_comment]").type("A response");
            cy.get("[data-test=artifact-submit]").click();

            cy.assertNotEmailWithContentReceived("ProjectMember@example.com", `A response`);

            cy.visit(`/plugins/tracker/?aid=${this.notifications_artifact_id}`);
            cy.get("[data-test=tracker-artifact-actions]").click();
            cy.get("[data-test=notifications-button]").click();
            cy.get("[data-test=feedback]").contains(
                "You are now receiving notifications for this artifact",
            );

            cy.get("[data-test=artifact_followup_comment]").type("An other response");
            cy.get("[data-test=artifact-submit]").click();

            cy.assertEmailWithContentReceived("ProjectMember@example.com", `An other response`);
        });
    });
});

export function disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype(): void {
    cy.on("uncaught:exception", (err) => {
        // the message below is only thrown by Prototypejs, if any other js exception is thrown
        // the test will fail
        return !err.message.includes("ev.element is not a function");
    });
}

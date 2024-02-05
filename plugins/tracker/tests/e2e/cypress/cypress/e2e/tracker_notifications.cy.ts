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

function addToNotifications(label: string): void {
    // ignore rule for select2
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-container").first().click();
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-input").first().type(`${label}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-label").last().click();
}

function createAndUpdateArtifact(title: string, edited_title: string): void {
    cy.get("[data-test=project-sidebar]")
        .shadow()
        .find("[data-test=artifact-quick-link-add]")
        .click();

    cy.get('[data-test="title"]').type(title);
    cy.intercept(`*func=submit-artifact*`).as("createArtifact");

    cy.get("[data-test=artifact-submit-options]").click();
    cy.get("[data-test=artifact-submit-and-stay]").click();
    cy.wait("@createArtifact", { timeout: 60000 });

    cy.get("[data-test=edit-field-title]").click();
    cy.get("[data-test=title]").clear().type(edited_title);
    cy.get("[data-test=artifact-submit]").click();
}

function goToNotificationAdministration(): void {
    cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
    cy.get("[data-test=notifications]").click();
}

function addUserToUnsusbscribeNotifications(user: string): void {
    cy.get("[data-test=unsuscribe-notifification]").click();

    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-container").last().click();
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-input").last().type(`${user}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-label").last().click();
    cy.get("[data-test=unsuscribe-notifification-button]").click();
}

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
            goToNotificationAdministration();
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

    it("Status change notifications", function () {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `status-${now}`;
        cy.createNewPublicProject(project_name, "issues");
        cy.visitProjectAdministration(project_name);
        cy.addProjectMember(project_name, "ProjectMember");
        cy.addProjectMember(project_name, "ARegularUser");

        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project_name);

        cy.log("Add user group");
        cy.get("[data-test=admin-nav-groups]").click();
        cy.addUserGroupWithUsers("my_custom_group", ["ARegularUser"]);

        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link-issue]").click();

        cy.log("Configure tracker notifications");
        goToNotificationAdministration();
        cy.get("[data-test=status-change-level]").check();
        cy.get("[data-test=submit-notifications-level]").click();

        cy.log("Add user group and a random user to all_updates notifications");
        cy.get("[data-test=add-notification]").click();
        addToNotifications("my_custom_group");
        addToNotifications("ProjectMember");
        cy.get("[data-test=all-updates]").check();
        cy.get("[data-test=save-notification-button]").click();

        cy.log("When artifact is updated, all users must receive an email");
        createAndUpdateArtifact("An artifact", "Edited artifact");

        cy.assertEmailWithContentReceived("ProjectMember@example.com", "Edited artifact");
        cy.assertEmailWithContentReceived("RegularUser@example.com", "Edited artifact");

        cy.log("Add ProjectMember in unsubscribe member list");

        goToNotificationAdministration();
        addUserToUnsusbscribeNotifications("ProjectMember");

        createAndUpdateArtifact("Artifact A", "Other artifact");

        cy.log("When artifact is updated, project member no longer receive an email");
        cy.assertNotEmailWithContentReceived("ProjectMember@example.com", "Other artifact");
        cy.assertEmailWithContentReceived("RegularUser@example.com", "Other artifact");

        goToNotificationAdministration();
        cy.log("Uncheck all_updates");
        cy.get("[data-test=edit-notification]").click();
        cy.get("[data-test=global-notification-all-update-checkbox]").uncheck();
        cy.get("[data-test=edit-notification-button]").click();

        createAndUpdateArtifact("Artifact B", "AnOther artifact");
        cy.log("When artifact is updated, nobody receive an email");
        cy.assertNotEmailWithContentReceived("ProjectMember@example.com", "AnOther artifact");
        cy.assertNotEmailWithContentReceived("RegularUser@example.com", "AnOther artifact");

        cy.log("Add again all_updates otherwise users won't receive any notifications");
        goToNotificationAdministration();
        cy.get("[data-test=edit-notification]").click();
        cy.get("[data-test=global-notification-all-update-checkbox]").check();
        cy.get("[data-test=edit-notification-button]").click();

        cy.get("[data-test=project-sidebar]")
            .shadow()
            .find("[data-test=artifact-quick-link-add]")
            .click();

        cy.get("[data-test=title]").type("Artifact");
        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();
        cy.wait("@createArtifact", { timeout: 60000 });

        cy.get("[data-test=edit-field-title]").click();
        cy.get("[data-test=title]").clear().type("Last artifact");
        cy.get("[data-test=edit-field-status]").click();
        selectLabelInListPickerDropdown("Under review", 0);
        cy.get("[data-test=edit-field-assigned_to]").click();
        selectLabelInListPickerDropdown("ProjectMember", 1);
        cy.get("[data-test=artifact-submit]").click();

        cy.assertNotEmailWithContentReceived("ProjectMember@example.com", "Last artifact");
        cy.assertEmailWithContentReceived("RegularUser@example.com", "Last artifact");
    });

    it("Tracker notifications - can not add invalid users", function () {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `invalid-notif-${now}`;
        cy.createNewPublicProject(project_name, "issues");
        cy.visitProjectAdministration(project_name);
        cy.addProjectMember(project_name, "ProjectMember");

        cy.projectAdministratorSession();

        cy.visitProjectAdministration(project_name);
        cy.log("Add user group");
        cy.get("[data-test=admin-nav-groups]").click();
        cy.addUserGroupWithUsers("empty", []);

        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link-issue]").click();

        cy.log("Project member must be tracker administrator");
        cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        cy.get("[data-test=admin-permissions]").click();
        cy.get("[data-test=tracker-permissions]").click();

        cy.get("[data-test=permissions_3]").select("are admin of the tracker");
        cy.get("[data-test=tracker-permission-submit]").click();

        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link-issue]").click();

        cy.log("Configure tracker notifications");
        goToNotificationAdministration();

        cy.log("Add an invalid user can not be notified");
        cy.get("[data-test=add-notification]").click();
        addToNotifications("NonExistingTuleapUser");
        cy.get("[data-test=save-notification-button]").click();

        cy.get("[data-test=feedback]").contains(
            "The entered value 'NonExistingTuleapUser' is invalid.",
        );

        cy.log("Add an empty user group can not be notified");
        cy.get("[data-test=add-notification]").click();
        addToNotifications("empty");
        cy.get("[data-test=save-notification-button]").click();

        cy.get("[data-test=feedback]").contains("The entered value 'empty' is invalid.");
    });

    function selectLabelInListPickerDropdown(
        label: string,
        position: number,
    ): Cypress.Chainable<JQuery<HTMLHtmlElement>> {
        cy.get("[data-test=list-picker-selection]").eq(position).click();
        return cy.root().within(() => {
            cy.get("[data-test-list-picker-dropdown-open]").within(() => {
                cy.get("[data-test=list-picker-item]").contains(label).click();
            });
        });
    }

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

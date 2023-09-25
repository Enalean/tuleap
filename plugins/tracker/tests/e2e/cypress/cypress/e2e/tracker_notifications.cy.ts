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

describe("Tracker notifications", () => {
    it("Conditional notifications", function () {
        cy.projectAdministratorSession();

        cy.visitProjectService("conditionnal-notifications", "Trackers");
        cy.get("[data-test=tracker-link-cond_notif]").click();
        cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        cy.get("[data-test=workflow]").click();
        cy.get("[data-test=field-dependencies]").click();

        cy.get("[data-test=tracker-administration-content]").then(($field_dependency) => {
            if (
                $field_dependency.find("[data-test=existing-field-dependency-label]").length === 0
            ) {
                cy.log("Define field dependencies");
                cy.get("[data-test=source_field]").select("Category");
                cy.get("[data-test=target_field]").select("Coordinators");
                cy.get("[data-test=choose_source_button]").click();

                disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype();

                cy.get("[data-test=tracker-field-dependencies-matrix]").within(() => {
                    cy.get("[data-test=matrix-row]")
                        .contains("Database")
                        .parent("[data-test=matrix-row]")
                        .within(() => {
                            cy.get("[data-test=create-dependency]").eq(1).click({ force: true });
                        });
                    cy.get("[data-test=matrix-row]")
                        .contains("User interface")
                        .parent("[data-test=matrix-row]")
                        .within(() => {
                            cy.get("[data-test=create-dependency]").eq(2).click({ force: true });
                        });
                    cy.get("[data-test=matrix-row]")
                        .contains("SOAP API")
                        .parent("[data-test=matrix-row]")
                        .within(() => {
                            cy.get("[data-test=create-dependency]").eq(1).click({ force: true });
                        });
                });
                cy.get("[data-test=create-field-dependencies-button]").click();
            }
        });

        cy.log(
            "When project admin create an artifact and choose database category, then coordinator1 will receieve notification",
        );
        cy.projectAdministratorSession();
        cy.visitProjectService("conditionnal-notifications", "Trackers");
        cy.get("[data-test=tracker-link-cond_notif]").click();
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

        cy.log("Coordinator1 receive a mail");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "Coordinator1@example.com",
            `My conditional notification`,
        );
        cy.log("Coordinator2 does not receive a mail");
        cy.assertNotEmailWithContentReceived(
            "Coordinator2@example.com",
            `My conditional notification`,
        );

        cy.log("ProjectMember does not receive notification");
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
        cy.get("[data-test=field-label]")
            .contains("Category")
            .parent()
            .within(() => {
                cy.searchItemInListPickerDropdown("User interface").click();
            });

        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        cy.log("Coordinator2 receive notification");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "Coordinator2@example.com",
            `My updated conditional notification`,
        );
        cy.log("Coordinator1 does not receive notification");
        cy.assertNotEmailWithContentReceived(
            "Coordinator1@example.com",
            `My updated conditional notification`,
        );

        cy.log("ProjectMember does not receive notification");
        cy.assertNotEmailWithContentReceived(
            "ProjectMember@example.com",
            `My updated conditional notification`,
        );

        cy.log(
            "When artifact is updated to project member, then project member and coordinator2 will receive a notification",
        );

        cy.get("[data-test=edit-field-assigned_to_1]").click();
        cy.get("[data-test=edit-field-summary_1]").click();
        cy.get("[data-test=summary_1]").clear().type("My third conditional notification");
        cy.get("[data-test=field-label]")
            .contains("Assigned to")
            .parent()
            .within(() => {
                cy.searchItemInListPickerDropdown("ProjectMember").click();
            });

        cy.get("[data-test=artifact-submit-options]").click();
        cy.get("[data-test=artifact-submit-and-stay]").click();

        cy.log("ProjectMember receive notification");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectMember@example.com",
            `My third conditional notification`,
        );

        cy.log("Coordinator2 receive notification");
        cy.assertUserMessagesReceivedByWithSpecificContent(
            "Coordinator2@example.com",
            `My third conditional notification`,
        );
        cy.log("Coordinator1 does not receive notification");
        cy.assertNotEmailWithContentReceived(
            "Coordinator1@example.com",
            `My third conditional notification`,
        );
    });
});

export function disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype(): void {
    cy.on("uncaught:exception", (err) => {
        // the message below is only thrown by Prototypejs, if any other js exception is thrown
        // the test will fail
        if (err.message.includes("ev.element is not a function")) {
            return false;
        }
    });
}

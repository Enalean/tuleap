/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */
describe("Empty Followup", () => {
    it("When user is Project Administrator, Then he can see private comment and hidden field", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService("empty-followup", "Trackers");

        cy.get("[data-test=tracker-link-bugs]").click();
        cy.get("[data-test=direct-link-to-artifact]").first().click();

        cy.get("[data-test=artifact-follow-up]").should("have.length", 4);

        cy.get("[data-test=artifact-follow-up]")
            .first()
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("not.exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should(
                    "contain",
                    "This is hidden",
                );
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(1)
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should(
                    "contain",
                    "This comment is only seen by admin",
                );
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(2)
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should(
                    "contain",
                    "Changes are shown but comment is hidden",
                );
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(3)
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should(
                    "contain",
                    "This comment is shown",
                );
            });
    });

    it("When user is Project Member, Then he can not see private comment and hidden field and there are not empty follow-up", () => {
        cy.projectMemberSession();
        cy.visitProjectService("empty-followup", "Trackers");
        cy.get("[data-test=tracker-link-bugs]").click();
        cy.get("[data-test=direct-link-to-artifact]").first().click();
        cy.get("[data-test=artifact-follow-up]").should("have.length", 2);

        cy.get("[data-test=artifact-follow-up]")
            .first()
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should("contain", "");
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(1)
            .should("have.attr", "data-changeset-id")
            .then((id) => {
                cy.get(`[data-test=tracker_artifact_followup_changes_${id}]`).should("exist");
                cy.get(`[data-test=tracker_artifact_followup_comment_${id}]`).should(
                    "contain",
                    "This comment is shown",
                );
            });
    });
});

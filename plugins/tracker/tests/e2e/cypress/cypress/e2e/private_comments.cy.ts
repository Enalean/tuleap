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
describe("Private comments", function () {
    it("When user is Project Administrator, Then they can see private comment and hidden field", function () {
        cy.projectAdministratorSession();
        cy.visitProjectService("empty-followup", "Trackers");

        cy.getContains("[data-test=tracker-link]", "Bugs").click();
        cy.get("[data-test=direct-link-to-artifact]").first().click();

        cy.get("[data-test=artifact-follow-up]").should("have.length", 4);

        cy.get("[data-test=artifact-follow-up]")
            .first()
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("not.exist");
                cy.wrap(follow_up)
                    .find("[data-test=follow-up-comment]")
                    .should("contain.text", "This is hidden");
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(1)
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("exist");
                cy.wrap(follow_up)
                    .find("[data-test=follow-up-comment]")
                    .should("contain.text", "This comment is only seen by admin");
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(2)
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("exist");
                cy.wrap(follow_up)
                    .find("[data-test=follow-up-comment]")
                    .should("contain.text", "Changes are shown but comment is hidden");
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(3)
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("exist");
                cy.wrap(follow_up)
                    .find("[data-test=follow-up-comment]")
                    .should("contain.text", "This comment is shown");
            });
    });

    it("When user is Project Member, Then they cannot see private comment and hidden field and there are no empty follow-up", function () {
        cy.projectMemberSession();
        cy.visitProjectService("empty-followup", "Trackers");
        cy.getContains("[data-test=tracker-link]", "Bugs").click();
        cy.get("[data-test=direct-link-to-artifact]").first().click();
        cy.get("[data-test=artifact-follow-up]").should("have.length", 2);

        cy.get("[data-test=artifact-follow-up]")
            .first()
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("exist");
                cy.wrap(follow_up).find("[data-test=follow-up-comment]").should("be.empty");
            });

        cy.get("[data-test=artifact-follow-up]")
            .eq(1)
            .then((follow_up) => {
                cy.wrap(follow_up).find("[data-test=follow-up-changes]").should("exist");
                cy.wrap(follow_up)
                    .find("[data-test=follow-up-comment]")
                    .should("contain.text", "This comment is shown");
            });
    });
});

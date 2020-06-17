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

describe("Test plan", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.server();
    });

    context("As project member", () => {
        let now: number;
        before(() => {
            cy.projectMemberLogin();
            now = Date.now();
        });

        it("Displays no campaign if release does not have one", () => {
            goToTestPlanOfMilestone("Release without campaigns");

            cy.contains("There is no test campaign yet.");
        });

        context("In a release with campaigns", () => {
            before(() => {
                goToTestPlanOfMilestone("Release with campaigns");
            });

            it("Displays the campaigns", () => {
                cy.contains("Campaign 1");
                cy.contains("9 tests");
            });

            it("Adds new campaign", () => {
                cy.get("[data-test=new-campaign]").click();
                cy.get("[data-test=new-campaign-label]").type("Campaign " + now);
                cy.get("[data-test=new-campaign-tests]").select("Test Suite Complete");
                cy.get("[data-test=new-campaign-submit-button]").click();
                cy.contains("Campaign " + now);
                cy.contains("8 tests");
            });
        });
    });
});

function goToTestPlanOfMilestone(milestone_label: string): void {
    cy.visitProjectService("test-testplan-project", "Agile Dashboard");
    cy.contains(milestone_label)
        .parent()
        .within(() => {
            cy.get("[data-test=go-to-planning]").click();
        });

    cy.get("[data-test=tab-testplan]").click();
}

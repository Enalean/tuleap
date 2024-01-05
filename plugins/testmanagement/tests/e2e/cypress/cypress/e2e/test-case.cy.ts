/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

describe(`TestManagement test case`, function () {
    let project_name: string;

    before(function () {
        const now = Date.now();
        project_name = "ttm-case-" + now;
    });

    context(`As project administrator`, function () {
        it(`Can create a test case`, function () {
            cy.log("Create a project with TTM");
            cy.projectAdministratorSession();
            cy.createNewPublicProject(project_name, "agile_alm");

            cy.visitProjectService(project_name, "Trackers");
            cy.get("[data-test=tracker-link-test_case]").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=summary]").type("Modal should open");
            cy.get("[data-test=step-definition-field]").get("[data-test=add-step]").click();
            cy.get("[data-test=editable-step]")
                .first()
                .within(() => {
                    cy.get("[data-test=description-textarea]").type("Click on the button");
                    cy.get("[data-test=expected-results-textarea]").type("The modal appears");
                })
                .parent()
                .within(() => {
                    // Button is visible only on hover
                    cy.get("[data-test=add-step]").click({ force: true });
                });
            cy.get("[data-test=editable-step]")
                .eq(1)
                .within(() => {
                    cy.get("[data-test=description-textarea]").type("Click on Cancel");
                    cy.get("[data-test=expected-results-textarea]").type("The modal disappears");
                });

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("Assert the steps are saved correctly");
            cy.get("[data-test=tracker-artifact-value-steps]").within(() => {
                cy.get("[data-test=readonly-step]").should("have.length", 2);
                cy.get("[data-test=readonly-step]")
                    .eq(0)
                    .within((step) => {
                        cy.wrap(step).should("contain", "Click on the button");
                        cy.get("[data-test=expected-results]").should(
                            "contain",
                            "The modal appears",
                        );
                    });
                cy.get("[data-test=readonly-step]")
                    .eq(1)
                    .within((step) => {
                        cy.wrap(step).should("contain", "Click on Cancel");
                        cy.get("[data-test=expected-results]").should(
                            "contain",
                            "The modal disappears",
                        );
                    });
            });
        });
    });
});

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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

describe("Navigation", function () {
    context("As a project member", function () {
        it("User can access to its dashboard with mouse", function () {
            cy.projectMemberSession();
            cy.visit("/");

            cy.get("[data-test=my-dashboard]").click();
            cy.get("[data-test=my-dashboard-option]").contains("My Dashboard");
            cy.get("[data-test=my-dashboard-option]").click();

            cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");
        });

        it("User can access to its dashboard with keyboard", function () {
            cy.projectMemberSession();
            cy.visit("/");

            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("d");

            //user is directly redirected to its personal dashboard
            cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");
        });

        it("User can create a project with keyboard navigation", function () {
            cy.projectMemberSession();
            cy.visit("/");

            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("c");
            cy.get("[data-test=create-new-item]").contains("Start a new project");
        });
        context(`switch-to`, function () {
            it(`can use the legacy filter`, function () {
                cy.projectMemberSession();
                cy.visit("/");
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").type("{s}");
                cy.get("[data-test=switch-to-modal]").should("be.visible");

                cy.get("[data-test=switch-to-filter]").type("Backlog");
                cy.get("[data-test=switch-to-projects-project]").should("have.length", 2);
                cy.get("[data-test=legacy-search-button]").click();

                cy.get("[data-test=words]").should("have.value", "Backlog");
                cy.get("[data-test=switch-to-modal]").should("not.be.visible");
                cy.get("[data-test=search-form]").within(() => {
                    cy.get("[data-test=words]").clear().type("Explicit{enter}");
                });
                cy.get("[data-test=result-title]").contains("Explicit Backlog");
            });
        });
    });
    context("As project admin", function () {
        context("switch-to", function () {
            it(`can access to the admin menu`, function () {
                cy.projectAdministratorSession();
                cy.visit("/");
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get("body").type("{s}");

                cy.get("[data-test=switch-to-filter]").type("Explicit Backlog");
                cy.get("[data-test=project-link]").should("exist");
                cy.get("[data-test=switch-to-projects-project-admin-icon]").first().click();

                cy.get("[data-test=project-administration-title]").contains(
                    "Project administration",
                );

                cy.get("[data-test=switch-to-modal]").should("not.be.visible");
            });
        });
    });
});

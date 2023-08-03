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

describe("Project Milestones Widget", function () {
    context("Project Dashboard", function () {
        it("Add Project Milestones on a dashboard project", () => {
            cy.projectAdministratorSession();
            cy.visit("/projects/projectmilestones-dashboard");

            cy.get("[data-test=dashboard-configuration-button]").click();
            cy.get("[data-test=dashboard-add-widget-button]").click();
            cy.get("[data-test=dashboardprojectmilestone]").click();
            cy.get("[data-test=dashboard-add-widget-button-submit]").click();

            cy.get("[data-test=dashboard-widget-dashboardprojectmilestone]").should(
                "contain",
                "ProjectMilestones Widget Milestones",
            );

            cy.get("[data-test=start-planning-button]").contains("Start planning");
        });
    });

    context("User Dashboard", function () {
        it("Add Project Milestones on user dashboard", function () {
            cy.projectMemberSession();
            cy.visit("/my/");

            cy.get("[data-test=dashboard-configuration-button]").click();
            cy.get("[data-test=dashboard-add-widget-button]").click();
            cy.get("[data-test=myprojectmilestone]").click();

            cy.get("[data-test=select-project-milestones-widget] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("ProjectMilestones{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(
                "#select2-select-project-milestones-widget-results .select2-results__option--highlighted",
            ).click();
            cy.get("[data-test=dashboard-add-widget-button-submit]").click();

            cy.get("[data-test=dashboard-widget-myprojectmilestone]").should(
                "contain",
                "ProjectMilestones Widget Milestones",
            );

            cy.get("[data-test=start-planning-button]").contains("Start planning");
        });
    });
});

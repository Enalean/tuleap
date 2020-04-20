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
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    context("Project Dashboard", function () {
        it("Add Project Milestones on a dashboard project", () => {
            cy.ProjectAdministratorLogin();
            cy.server();
            cy.visit("/projects/projectmilestones-dashboard");

            cy.get("[data-test=dashboard-configuration-button]").click();
            cy.get("[data-test=dashboard-add-widget-button]").click();
            cy.get("[data-test=dashboardprojectmilestone]").click();
            cy.get("[data-test=dashboard-add-widget-button-submit]").click();

            cy.get("[data-test=dashboard-widget-dashboardprojectmilestone]");
            cy.contains("ProjectMilestones Widget Project Milestones");

            cy.getProjectId("projectmilestones-dashboard").then((project_id) => {
                cy.route(`/api/v1/projects/${project_id}/milestones?limit=*&offset=*&query=*`).as(
                    "loadReleases"
                );
            });
            cy.wait("@loadReleases", { timeout: 1000 });

            cy.get("[data-test=widget-content-project-milestones]");
        });
    });

    context("User Dashboard", function () {
        it("Add Project Milestones on user dashboard", function () {
            cy.projectMemberLogin();
            cy.server();
            cy.visit("/my/");

            cy.get("[data-test=dashboard-configuration-button]").click();
            cy.get("[data-test=dashboard-add-widget-button]").click();
            cy.get("[data-test=myprojectmilestone]").click();

            cy.get("[data-test=select-project-milestones-widget] + .select2-container").click();
            cy.get(".select2-search__field").type("ProjectMilestones{enter}");

            cy.get(
                "#select2-select-project-milestones-widget-results .select2-results__option--highlighted"
            ).click();
            cy.get("[data-test=dashboard-add-widget-button-submit]").click();

            cy.get("[data-test=dashboard-widget-myprojectmilestone]");
            cy.contains("ProjectMilestones Widget Project Milestones");

            cy.getProjectId("projectmilestones-dashboard").then((project_id) => {
                cy.route(`/api/v1/projects/${project_id}/milestones?limit=*&offset=*&query=*`).as(
                    "loadReleases"
                );
            });
            cy.wait("@loadReleases", { timeout: 1000 });

            cy.get("[data-test=widget-content-project-milestones]");
        });
    });
});

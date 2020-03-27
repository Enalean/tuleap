/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

function updateSearchQuery(search_query) {
    cy.get("[data-test=cross-tracker-reading-mode]").click();
    cy.get("[data-test=expert-query-textarea]")
        .clear({ force: true })
        .type(search_query, { force: true });
    cy.get("[data-test=search-report-button]").click();
}

function assertOpenArtifacts() {
    cy.get("[data-test=cross-tracker-results-artifact]").then((artifact) => {
        cy.wrap(artifact).should("contain", "nananana");
        cy.wrap(artifact).should("contain", "kanban 2");
        cy.wrap(artifact).should("contain", "bug");
        cy.wrap(artifact).should("contain", "bug 1");
        cy.wrap(artifact).should("contain", "bug 2");
    });
}

function assertAllArtifacts() {
    cy.get("[data-test=cross-tracker-results-artifact]").then((artifact) => {
        cy.wrap(artifact).should("contain", "nananana");
        cy.wrap(artifact).should("contain", "kanban 2");
        cy.wrap(artifact).should("contain", "kanban 1");
        cy.wrap(artifact).should("contain", "bug");
        cy.wrap(artifact).should("contain", "bug 1");
        cy.wrap(artifact).should("contain", "bug 2");
    });
}

describe("User dashboards", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("User should be able to manipulate widgets", function () {
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-delete-tab-button]").click();
        cy.get("[data-test=dashboard-confirm-delete-button]").click();

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type("My basic dashboard");
        cy.get("[data-test=dashboard-add-button-submit]").click();

        // widget image
        cy.get("[data-test=dashboard-add-widget-empty-state-button]").click();
        cy.get("[data-test=myimageviewer]").click();
        cy.get("[data-test=dashboard-widget-image-input-url]").type(
            "https://tuleap/themes/BurningParrot/images/organization_logo.png"
        );
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-widget-myimageviewer]")
            .find("img")
            .should(
                "have.attr",
                "src",
                "https://tuleap/themes/BurningParrot/images/organization_logo.png"
            );

        // widget my artifacts
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();

        cy.get("[data-test=plugin_tracker_myartifacts]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-my-artifacts-content]");

        // widget my projects
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=myprojects]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();
        cy.get("[data-test=dashboard-my-projects]").find("td").contains("User dashboard");
    });

    describe("Cross tracker search", function () {
        it("User should be able to set trackers from widgets", function () {
            cy.server();
            cy.visit("/my/");

            cy.get("[data-test=dashboard-configuration-button]").click();
            cy.get("[data-test=dashboard-add-widget-button]").click();
            cy.get("[data-test=crosstrackersearch]").click();
            cy.get("[data-test=dashboard-add-widget-button-submit]").click();

            // select some trackers
            cy.getProjectId("dashboard").then((project_id) => {
                cy.visit("/my/");

                cy.route(
                    `/api/v1/projects/${project_id}/trackers?limit=*&representation=minimal&offset=*`
                ).as("loadTrackers");
            });

            cy.get("[data-test=cross-tracker-reading-mode]").click();

            //select project
            cy.get("[data-test=cross-tracker-selector-project]").select("User dashboard");

            cy.wait("@loadTrackers", { timeout: 60000 });
            cy.get("[data-test=cross-tracker-selector-tracker]").select("Bugs");
            cy.get("[data-test=cross-tracker-selector-tracker-button]").click();
            cy.get("[data-test=cross-tracker-selector-tracker]").select("Kanban Tasks");
            cy.get("[data-test=cross-tracker-selector-tracker-button]").click();
            cy.get("[data-test=search-report-button]").click();

            // Bugs has some artifacts
            cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 5);
            assertOpenArtifacts();
        });

        it("Regular user should be able to execute queries", function () {
            updateSearchQuery("@title != 'foo'");
            cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
            assertAllArtifacts();

            updateSearchQuery("@status = OPEN()");
            cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 5);
            assertOpenArtifacts();

            updateSearchQuery("@submitted_on BETWEEN(NOW() - 2m, NOW() - 1m)");
            cy.get("[data-test=cross-tracker-no-results]");

            updateSearchQuery('@last_update_date > "2018-01-01"');
            cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
            assertAllArtifacts();

            // save report
            cy.get("[data-test=cross-tracker-save-report]").click();
            cy.get("[data-test=cross-tracker-report-success]");

            // reload page and check report still has results
            cy.reload();
            cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
            assertAllArtifacts();
        });
    });
});

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

describe("Cross tracker search", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("User should be able to set trackers from widgets", function () {
        cy.server();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=crosstrackersearch]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        // select some trackers
        cy.getProjectId("cross-tracker-search").then((project_id) => {
            cy.visit("/my/");

            cy.route(
                `/api/v1/projects/${project_id}/trackers?limit=*&representation=minimal&offset=*`
            ).as("loadTrackers");
        });

        cy.get("[data-test=cross-tracker-reading-mode]").click();

        //select project
        cy.get("[data-test=cross-tracker-selector-project]").select("Cross tracker search");

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

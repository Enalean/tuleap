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

describe("Cross tracker search", function () {
    let now: number;

    before(function () {
        now = Date.now();
    });

    it("User should be able to set trackers from widgets", function () {
        const project_name = `x-tracker-${now}`;
        cy.projectMemberSession();

        cy.createNewPublicProject(project_name, "agile_alm").then((project_id) => {
            cy.getTrackerIdFromREST(project_id, "bug").then((tracker_id) => {
                const TITLE_FIELD_NAME = "summary";
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "bug",
                    artifact_status: "New",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "bug 1",
                    artifact_status: "New",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "bug 2",
                    artifact_status: "In Progress",
                    title_field_name: TITLE_FIELD_NAME,
                });
            });
            cy.getTrackerIdFromREST(project_id, "task").then((tracker_id) => {
                const TITLE_FIELD_NAME = "title";
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "nananana",
                    artifact_status: "Todo",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "kanban 1",
                    artifact_status: "Done",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id,
                    artifact_title: "kanban 2",
                    artifact_status: "Todo",
                    title_field_name: TITLE_FIELD_NAME,
                });
            });
        });

        cy.visit("/my/");

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=crosstrackersearch]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.intercept("/api/v1/cross_tracker_reports/*/content*").as("getReportContent");

        cy.log("Regular user should be able to run queries");
        editWidget();
        updateSearchQuery(
            `SELECT @title FROM @project.name = "${project_name}" AND @tracker.name IN ("bug", "task") WHERE @last_update_date > "2018-01-01"`,
        );
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-search-widget] [data-test=cell]").then((cell) => {
            cy.wrap(cell).should("contain", "bug");
            cy.wrap(cell).should("contain", "bug 1");
            cy.wrap(cell).should("contain", "bug 2");
            cy.wrap(cell).should("contain", "nananana");
            cy.wrap(cell).should("contain", "kanban 1");
            cy.wrap(cell).should("contain", "kanban 2");
        });

        cy.log("Switch to Default mode and select some trackers");
        editWidget();
        toggleWidgetMode();

        cy.log("Select project");
        cy.get("[data-test=cross-tracker-selector-project]").select(project_name);

        cy.get("[data-test=cross-tracker-selector-tracker]").select("Bugs");
        cy.get("[data-test=cross-tracker-selector-tracker-button]").click();
        cy.get("[data-test=cross-tracker-selector-tracker]").select("Tasks");
        cy.get("[data-test=cross-tracker-selector-tracker-button]").click();

        cy.log("The default query should return open artifacts");
        clearCodeMirror();
        cy.get("[data-test=search-report-button]").click();
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 5);
        assertOpenArtifacts();

        editWidget();
        updateSearchQuery("@title != 'foo'");
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
        assertAllArtifacts();

        editWidget();
        updateSearchQuery("@status = OPEN()");
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 5);
        assertOpenArtifacts();

        editWidget();
        updateSearchQuery("@submitted_on BETWEEN(NOW() - 2m, NOW() - 1m)");
        cy.get("[data-test=cross-tracker-no-results]");

        editWidget();
        updateSearchQuery('@last_update_date > "2018-01-01"');
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
        assertAllArtifacts();

        cy.log("Save results");
        cy.get("[data-test=cross-tracker-save-report]").click();
        cy.get("[data-test=cross-tracker-report-success]");

        cy.log("reload page and check report still has results");
        cy.reload();
        cy.wait("@getReportContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-results]").find("tr").should("have.length", 6);
        assertAllArtifacts();
    });
});

function editWidget(): void {
    cy.get("[data-test=cross-tracker-reading-mode]").click();
}

function updateSearchQuery(search_query: string): void {
    clearCodeMirror();
    // eslint-disable-next-line cypress/require-data-selectors -- ignore for CodeMirror
    cy.get(".cm-editor").type(search_query);
    cy.get("[data-test=search-report-button]").click();
    cy.get("[data-test=tql-reading-mode-query]").contains(search_query);
}

function toggleWidgetMode(): void {
    cy.get("[data-test=cross-tracker-search-widget] [data-test=switch-mode]").click();
}

function assertOpenArtifacts(): void {
    cy.get("[data-test=cross-tracker-results-artifact]").then((artifact) => {
        cy.wrap(artifact).should("contain", "nananana");
        cy.wrap(artifact).should("contain", "kanban 2");
        cy.wrap(artifact).should("contain", "bug");
        cy.wrap(artifact).should("contain", "bug 1");
        cy.wrap(artifact).should("contain", "bug 2");
    });
}

function assertAllArtifacts(): void {
    cy.get("[data-test=cross-tracker-results-artifact]").then((artifact) => {
        cy.wrap(artifact).should("contain", "nananana");
        cy.wrap(artifact).should("contain", "kanban 2");
        cy.wrap(artifact).should("contain", "kanban 1");
        cy.wrap(artifact).should("contain", "bug");
        cy.wrap(artifact).should("contain", "bug 1");
        cy.wrap(artifact).should("contain", "bug 2");
    });
}

function clearCodeMirror(): void {
    // eslint-disable-next-line cypress/require-data-selectors -- ignore for CodeMirror
    cy.get(".cm-editor").type("{ctrl}a{del}");
}

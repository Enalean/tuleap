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

describe("CrossTracker search", function () {
    let now: number;
    let project_name: string;

    before(function () {
        now = Date.now();
        project_name = `x-tracker-${now}`;
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
    });

    beforeEach(function () {
        now = Date.now();
    });

    it("User should be able to use CrossTracker search widget", function () {
        cy.projectMemberSession();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=crosstrackersearch]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.intercept("/api/v1/crosstracker_query/content*").as("getQueryContent");

        cy.log("Regular user should be able to run queries");
        cy.get("[data-test=create-query-title]").type("My query");
        updateSearchQuery(
            `SELECT @title FROM @project.name = "${project_name}" AND @tracker.name IN ("bug", "task") WHERE @last_update_date > "2018-01-01"`,
        );
        cy.wait("@getQueryContent", { timeout: 5000 });
        cy.get("[data-test=column-header]").should("contain", "Title");
        cy.get("[data-test=cross-tracker-search-widget] [data-test=cell]").then((cell) => {
            cy.wrap(cell).should("contain", "bug");
            cy.wrap(cell).should("contain", "bug 1");
            cy.wrap(cell).should("contain", "bug 2");
            cy.wrap(cell).should("contain", "nananana");
            cy.wrap(cell).should("contain", "kanban 1");
            cy.wrap(cell).should("contain", "kanban 2");
        });

        cy.log("Save results");
        cy.get("[data-test=query-creation-save-button]").click();
        cy.get("[data-test=cross-tracker-report-success]");

        cy.intercept("/api/v1/crosstracker_query/*/content*").as("getSpecificQueryContent");
        cy.log("reload page and check report still has results");
        cy.reload();
        cy.wait("@getSpecificQueryContent", { timeout: 5000 });
        cy.get("[data-test=cross-tracker-search-widget] [data-test=cell]").then((cell) => {
            cy.wrap(cell).should("contain", "bug");
            cy.wrap(cell).should("contain", "bug 1");
            cy.wrap(cell).should("contain", "bug 2");
            cy.wrap(cell).should("contain", "nananana");
            cy.wrap(cell).should("contain", "kanban 1");
            cy.wrap(cell).should("contain", "kanban 2");
        });
    });

    it("User can create query from a suggested one", function () {
        cy.projectMemberSession();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=crosstrackersearch]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.intercept("/api/v1/crosstracker_query/content*").as("getQueryContent");

        cy.log("Write a first query");
        cy.get("[data-test=create-query-title]").type("My query");
        updateSearchQuery(
            `SELECT @pretty_title FROM @project.name = '${project_name}' WHERE @id >= 1`,
        );

        cy.log("Use a suggested query");
        cy.get("[data-test=query-suggested-button]").should("have.length", 1);
        cy.get("[data-test=query-suggested-button]").click();
        cy.get("[data-test=modal-action-button]").click();
        cy.get("[data-test=create-query-title]").should(
            "have.value",
            "Open artifacts assigned to me in my projects",
        );
        cy.get("[data-test=create-query-description]").should(
            "have.value",
            "Open artifacts assigned to me in my projects",
        );
        cy.get("[data-test=cross-tracker-search-widget] [data-test=expert-query]").should(
            "contain",
            "SELECT @id, @tracker.name, @project.name, @last_update_date, @submitted_by",
        );
        cy.get("[data-test=query-creation-search-button]").click();
        cy.wait("@getQueryContent", { timeout: 5000 });
        cy.get("[data-test=query-creation-save-button]").click();
        cy.get("[data-test=cross-tracker-report-success]");
    });

    it("User can display query details", function () {
        cy.projectMemberSession();
        cy.visit("/my/");

        cy.get("[data-test=dashboard-add-button]").click();
        cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
        cy.get("[data-test=dashboard-add-button-submit]").click();

        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        cy.get("[data-test=crosstrackersearch]").click();
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.intercept("/api/v1/crosstracker_query/content*").as("getQueryContent");

        cy.log("Create a query with a description");
        cy.get("[data-test=create-query-title]").type("A first query");
        cy.get("[data-test=create-query-description]").type("A great description for my query");
        updateSearchQuery(
            `SELECT @pretty_title FROM @project.name = '${project_name}' WHERE @id >= 1`,
        );

        cy.wait("@getQueryContent", { timeout: 5000 });

        cy.log("Display query details");
        cy.get("[data-test=query-creation-save-button]").click();
        cy.get("[data-test=toggle-query-details-button]").click();

        cy.get("[data-test=query-description]").should(
            "have.text",
            "A great description for my query",
        );
        cy.get("[data-test=export-xlsx-button]");
    });
});

function updateSearchQuery(search_query: string): void {
    cy.get("[data-test=cross-tracker-search-widget] [data-test=expert-query]")
        .find("[role=textbox][contenteditable=true]")
        .invoke("text", search_query);
    cy.get("[data-test=query-creation-search-button]").click();
}

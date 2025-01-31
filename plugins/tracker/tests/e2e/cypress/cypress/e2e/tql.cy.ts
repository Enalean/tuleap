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

function getTrackerIdFromTrackerListPage(): Cypress.Chainable<JQuery<HTMLElement>> {
    cy.visitProjectService("tql", "Trackers");
    return cy
        .getContains("[data-test=tracker-link]", "TQL")
        .should("have.attr", "data-test-tracker-id");
}

interface TrackerField {
    name: string;
    field_id: string;
}

type Tracker = {
    readonly fields: ReadonlyArray<TrackerField>;
};

function getSummaryFieldId(tracker_id: string): Cypress.Chainable<string> {
    return cy.getFromTuleapAPI<Tracker>(`/api/trackers/${tracker_id}`).then((response) => {
        const tracker = response.body;
        const summary_field = tracker.fields.find(
            (field: TrackerField) => field.name === "summary",
        );

        if (!summary_field) {
            throw Error(`Expected to find field named "summary" but did not find it`);
        }
        return String(summary_field.field_id);
    });
}

function findArtifactsWithExpertQuery(query: string): void {
    cy.get("[data-test=expert-report-form]")
        .find("[role=textbox][contenteditable=true]")
        .invoke("text", query);
    cy.get("[data-test=expert-query-submit-button]").click();
}

function checkOnlyExpectedArtifactsAreListed(
    summary_column_id: string,
    expected_artifacts: Array<string>,
): void {
    cy.get(
        `[data-test=tracker-report-table-results-artifact] > [data-column-id=${summary_column_id}]`,
    ).then((artifact) => {
        expected_artifacts.forEach((artifact_name) => {
            cy.wrap(artifact).should("contain", artifact_name);
        });
    });
}

function checkNoArtifactsAreListed(): void {
    cy.get(`[data-test=tracker-report-table-empty-state]`).should("exist");
}

describe("Report expert queries", () => {
    before(() => {
        cy.projectMemberSession();
        cy.getProjectId("tracker-project").as("project_id");

        getTrackerIdFromTrackerListPage()
            .as("tql_tracker_id")
            .then(function () {
                cy.visit(`/plugins/tracker/?tracker=${this.tql_tracker_id}`);

                return getSummaryFieldId(this.tql_tracker_id).as("summary_field_id");
            });
    });

    it("TQL queries", function () {
        cy.log("bug1 for summary='bug1'");
        cy.projectMemberSession();
        cy.visit(`/plugins/tracker/?tracker=${this.tql_tracker_id}`);
        findArtifactsWithExpertQuery("summary='bug1'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1"]);

        findArtifactsWithExpertQuery("summary='bug'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2", "bug3"]);

        findArtifactsWithExpertQuery("summary='bug' and details='original2'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug2"]);

        findArtifactsWithExpertQuery("remaining_effort between(1, 42)");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1"]);

        findArtifactsWithExpertQuery("remaining_effort > 3.14");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug2"]);

        findArtifactsWithExpertQuery("story_points <= 21");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2"]);

        findArtifactsWithExpertQuery("story_points = ''");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug3"]);

        findArtifactsWithExpertQuery("story_points != ''");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2"]);

        findArtifactsWithExpertQuery("due_date = '2017-01-10'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug2"]);

        findArtifactsWithExpertQuery("timesheeting < '2017-01-18 14:36'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1"]);

        findArtifactsWithExpertQuery("last_update_date between(now() - 1w, now())");
        checkNoArtifactsAreListed();

        findArtifactsWithExpertQuery("submitted_by = MYSELF()");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2", "bug3"]);

        findArtifactsWithExpertQuery("submitted_by != MYSELF()");
        checkNoArtifactsAreListed();

        findArtifactsWithExpertQuery("submitted_by IN(MYSELF())");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2", "bug3"]);

        findArtifactsWithExpertQuery("submitted_by NOT IN(MYSELF())");
        checkNoArtifactsAreListed();

        findArtifactsWithExpertQuery(
            "status IN ('todo', 'doing') OR ugroups = 'Membres du projet'",
        );
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1", "bug2"]);

        findArtifactsWithExpertQuery("status = ''");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug2", "bug3"]);

        findArtifactsWithExpertQuery("ugroups = 'FRS_Admin'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1"]);

        findArtifactsWithExpertQuery("@comments = 'Lorem ipsum'");
        checkOnlyExpectedArtifactsAreListed(this.summary_field_id, ["bug1"]);
    });

    it("Shows error", function () {
        cy.projectMemberSession();
        cy.visit(`/plugins/tracker/?tracker=${this.tql_tracker_id}`);
        findArtifactsWithExpertQuery('summary="bug1');

        cy.get("[data-test=feedback]").contains("Error during parsing expert query");

        findArtifactsWithExpertQuery('submitted_by="username"');

        cy.get("[data-test=feedback]").contains(
            "Error with the field 'submitted_by'. The user 'username' does not exist.",
        );

        findArtifactsWithExpertQuery('test="bug1"');

        cy.get("[data-test=feedback]").contains(
            "We cannot search on 'test', we don't know what it refers to. Please refer to the documentation for the allowed comparisons.",
        );

        findArtifactsWithExpertQuery('due_date = "2017-01-10 12:12"');

        cy.get("[data-test=feedback]").contains(
            "The date field 'due_date' cannot be compared to the string value '2017-01-10 12:12'",
        );

        findArtifactsWithExpertQuery('ugroups = "unknown"');

        cy.get("[data-test=feedback]").contains(
            "The value 'unknown' doesn't exist for the list field 'ugroups'.",
        );
    });
});

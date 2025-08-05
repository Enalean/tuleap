/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

describe("CrossTracker artifact links", function () {
    let now: number;
    let project_name: string;

    before(function () {
        now = Date.now();
        project_name = `xts-link-${now}`;
        cy.projectMemberSession();

        cy.createNewPublicProject(project_name, "agile_alm").as("project_id");
    });

    beforeEach(function () {
        now = Date.now();
    });

    /**
     * Parent_1
     *    │
     *    └───► Child
     *            ▲│
     *            │└──► Grandchild
     *            │
     *            └─────Parent_2
     */

    it("Test filter out parent from reverse links", function () {
        cy.projectMemberSession();

        cy.log("Create needed artifacts: Parent_1, Child, Grandchild and Parent_2 and add links");

        cy.getTrackerIdFromREST(this.project_id, "task").then((tracker_id) => {
            const TITLE_FIELD_NAME = "title";
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `kanban Grandchild`,
                    },
                ],
            }).as("artifact_grandchild_id");
        });
        cy.getTrackerIdFromREST(this.project_id, "story").then((tracker_id) => {
            const TITLE_FIELD_NAME = "i_want_to";
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `User Story Parent_1`,
                    },
                ],
            }).as("artifact_parent_1_id");
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `User Story Parent_2`,
                    },
                ],
            }).as("artifact_parent_2_id");
        });

        cy.getTrackerIdFromREST(this.project_id, "story").then((tracker_id) => {
            const TITLE_FIELD_NAME = "i_want_to";
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `User Story Child`,
                    },
                    {
                        shortname: "links",
                        all_links: [
                            {
                                id: this.artifact_grandchild_id,
                                type: "_is_child",
                                direction: "forward",
                            },
                            {
                                id: this.artifact_parent_1_id,
                                type: "_is_child",
                                direction: "reverse",
                            },
                            {
                                id: this.artifact_parent_2_id,
                                type: "_is_child",
                                direction: "reverse",
                            },
                        ],
                    },
                ],
            });
        });

        cy.visit("/my/");

        cy.createNewXTSWidget(now);

        cy.get("[data-test=cross-tracker-search-widget]")
            .invoke("attr", "data-widget-json-data")
            .then((widget_data) => {
                if (widget_data === undefined) {
                    throw new Error("The widget has not been created");
                }
                const widget_id = JSON.parse(widget_data).widget_id;
                cy.postFromTuleapApi<void>("/api/crosstracker_query", {
                    widget_id: widget_id,
                    tql_query: `SELECT @id, @pretty_title
                                FROM @project.name = '${project_name}' AND @tracker.name = 'story'
                                WHERE @title = 'User Story Parent_1'`,
                    title: "query A",
                    description: "",
                    is_default: true,
                });
            });
        cy.reload();

        cy.get("[data-test=artifact-row]").should("have.length", 1);

        cy.intercept("GET", "/api/v1/crosstracker_widget/*/forward_links*").as("getForwardLinks");
        cy.intercept("GET", "/api/v1/crosstracker_widget/*/reverse_links*").as("getReverseLinks");

        cy.get("[data-test=pretty-title-links-button]").click();
        cy.wait(["@getForwardLinks", "@getReverseLinks"]);

        cy.get("[data-test=artifact-row]").should("have.length", 2);

        cy.getContains("[data-test=artifact-row]", "Parent_1").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("have.class", "fa-caret-down");
        });

        cy.getContains("[data-test=artifact-row]", "Child").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("have.class", "fa-caret-right");
            cy.get("[data-test=pretty-title-links-button]").click();
            cy.wait(["@getForwardLinks", "@getReverseLinks"]);
        });

        cy.get("[data-test=artifact-row]").should("have.length", 4);

        cy.get("[data-test=artifact-row]")
            .eq(2)
            .within(() => {
                cy.contains("Grandchild");
            });

        cy.get("[data-test=artifact-row]")
            .eq(3)
            .within(() => {
                cy.contains("Parent_2");
            });
        cy.get("[data-test=link-arrow]").should("be.visible");
    });

    /**
     * Parent 2
     *   ▲
     *   └──Child 1
     *         │
     *         └►Parent 1
     *            ▲
     *            └───Child 2
     */
    it("Test filter out parent from forward links", function () {
        cy.projectMemberSession();

        cy.log("Create needed artifacts: Parent 1,Child 1 and Parent 2 and add links");

        cy.getTrackerIdFromREST(this.project_id, "bug").then((tracker_id) => {
            const TITLE_FIELD_NAME = "summary";
            cy.createArtifact({
                tracker_id,
                artifact_title: "bug Child 1",
                title_field_name: TITLE_FIELD_NAME,
            }).as("artifact_chile_1_id");
        });

        cy.getTrackerIdFromREST(this.project_id, "story").then((tracker_id) => {
            const TITLE_FIELD_NAME = "i_want_to";
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `User Story Parent 1`,
                    },
                    {
                        shortname: "links",
                        all_links: [
                            {
                                id: this.artifact_chile_1_id,
                                type: "_is_child",
                                direction: "reverse",
                            },
                        ],
                    },
                ],
            }).as("artifact_parent_1_id");
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `User Story Parent 2`,
                    },
                    {
                        shortname: "links",
                        all_links: [
                            {
                                id: this.artifact_chile_1_id,
                                type: "_is_child",
                                direction: "reverse",
                            },
                        ],
                    },
                ],
            });
        });

        cy.visit("/my/");

        cy.createNewXTSWidget(now);

        cy.get("[data-test=cross-tracker-search-widget]")
            .invoke("attr", "data-widget-json-data")
            .then((widget_data) => {
                if (widget_data === undefined) {
                    throw new Error("The widget has not been created");
                }
                const widget_id = JSON.parse(widget_data).widget_id;
                cy.postFromTuleapApi<void>("/api/crosstracker_query", {
                    widget_id: widget_id,
                    tql_query: `SELECT @id, @pretty_title
                                FROM @project.name = '${project_name}' AND @tracker.name = 'story'
                                WHERE @title = 'User Story Parent 2'`,
                    title: "query A",
                    description: "",
                    is_default: true,
                });
            });
        cy.reload();
        cy.get("[data-test=artifact-row]").should("have.length", 1);

        cy.intercept("GET", "/api/v1/crosstracker_widget/*/forward_links*").as("getForwardLinks");
        cy.intercept("GET", "/api/v1/crosstracker_widget/*/reverse_links*").as("getReverseLinks");

        cy.get("[data-test=pretty-title-links-button]").click();
        cy.wait(["@getForwardLinks", "@getReverseLinks"]);

        cy.get("[data-test=artifact-row]").should("have.length", 2);

        cy.getContains("[data-test=artifact-row]", "Child 1").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("have.class", "fa-caret-right");
            cy.get("[data-test=pretty-title-links-button]").click();
            cy.wait(["@getForwardLinks", "@getReverseLinks"]);
        });

        cy.getContains("[data-test=artifact-row]", "Parent 1").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("not.be.visible");
        });

        cy.log("Add new Child 2 artifact and link it to Parent 1 as a child");

        cy.getTrackerIdFromREST(this.project_id, "task").then((tracker_id) => {
            const TITLE_FIELD_NAME = "title";
            cy.createArtifactWithFields({
                tracker_id,
                fields: [
                    {
                        shortname: TITLE_FIELD_NAME,
                        value: `kanban Child 2`,
                    },
                    {
                        shortname: "links",
                        all_links: [
                            {
                                id: this.artifact_parent_1_id,
                                type: "_is_child",
                                direction: "forward",
                            },
                        ],
                    },
                ],
            }).then(() => {
                cy.log("Force widget reload to be sure artifact has correct links");
                cy.reload();
            });
        });

        cy.get("[data-test=pretty-title-links-button]").click();

        cy.getContains("[data-test=artifact-row]", "Child 1").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("have.class", "fa-caret-right");
            cy.get("[data-test=pretty-title-links-button]").click();
            cy.wait(["@getForwardLinks", "@getReverseLinks"]);
        });
        cy.get("[data-test=artifact-row]").should("have.length", 3);

        cy.getContains("[data-test=artifact-row]", "Parent 1").within(() => {
            cy.get("[data-test=pretty-title-links-button]").click();
            cy.wait(["@getForwardLinks", "@getReverseLinks"]);
        });
        cy.get("[data-test=artifact-row]").should("have.length", 4);

        cy.getContains("[data-test=artifact-row]", "Child 2").within(() => {
            cy.get("[data-test=pretty-title-caret]").should("not.be.visible");
        });
        cy.get("[data-test=link-arrow]").should("be.visible");
    });
});

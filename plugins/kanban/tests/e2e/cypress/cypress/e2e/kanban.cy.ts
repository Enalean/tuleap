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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

const now = Date.now();

function createCardInColumn(column_name: string, drag_to_collapsed_column_label: string): void {
    cy.intercept("POST", "/api/v1/kanban_items").as("createCard");
    cy.get(`[data-test=${column_name}]`).within(() => {
        cy.get("[data-test=add-in-place]").invoke("css", "pointer-events", "all");

        cy.get("[data-test=add-in-place-button]").click();
        cy.get("[data-test=add-in-place-label-input]").clear().type(drag_to_collapsed_column_label);
        cy.get("[data-test=add-in-place-submit]").first().click();
        cy.wait("@createCard", { timeout: 1000 });
    });
}

describe("Kanban service", () => {
    before(function () {
        cy.projectAdministratorSession();
        const project_name = `kanban-${now}`;
        cy.createNewPublicProject(project_name, "kanban").then((project_id) => {
            const TITLE_FIELD_NAME = "title";
            cy.getTrackerIdFromREST(project_id, "activity").then((tracker_id) => {
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "first title",
                    artifact_status: "To be done",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "second title",
                    artifact_status: "To be done",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "third title",
                    artifact_status: "To be done",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "in progress",
                    artifact_status: "In progress",
                    title_field_name: TITLE_FIELD_NAME,
                });
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "also progress",
                    artifact_status: "In progress",
                    title_field_name: TITLE_FIELD_NAME,
                });
            });
            cy.addProjectMember(project_name, "projectMember");
        });
    });
    context("As Project Admin", function () {
        it(`kanban administration modal still works`, function () {
            cy.projectAdministratorSession();

            cy.log("administrator can reorder column");
            cy.visitProjectService(`kanban-${now}`, "Kanban");
            cy.get('[data-test="go-to-kanban"]').click();
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.dragAndDrop(
                "[data-test=edit-kanban-column-review]",
                "[data-test=edit-kanban-column-in_progress]",
                "top",
            );
            cy.get("[data-test=edit-kanban-column]").should("have.length", 3);
            cy.get("[data-test=edit-kanban-column-label]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                },
            );

            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");
            cy.get("[data-test=kanban-column-header]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                },
            );
            cy.get("[data-test=kanban-warning-modal]").should("not.exist");
            // The order of the columns should not change after reload
            cy.reload();
            cy.get("[data-test=kanban-column-header]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                },
            );
        });

        it("changes promotion of kanban", () => {
            cy.projectAdministratorSession();

            cy.visitProjectService(`kanban-${now}`, "Kanban");
            cy.get("[data-test=project-sidebar]").shadow().contains("Activities").click();
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.get("[data-test=is-promoted]").click();
            cy.visitProjectService(`kanban-${now}`, "Kanban");
            cy.get("[data-test=project-sidebar]")
                .shadow()
                .contains("Activities")
                .should("not.exist");
        });

        it(`kanban in project dashboard`, function () {
            cy.projectAdministratorSession();
            const project_name = `filtered-${now}`;
            cy.createNewPublicProject(project_name, "kanban").then((project_id) => {
                const TITLE_FIELD_NAME = "title";
                cy.getTrackerIdFromREST(project_id, "activity").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "an artifact with title",
                        artifact_status: "To be done",
                        title_field_name: TITLE_FIELD_NAME,
                    });

                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "bla bla",
                        artifact_status: "To be done",
                        title_field_name: TITLE_FIELD_NAME,
                    });
                });
            });
            cy.projectAdministratorSession();

            cy.visit(`/projects/${project_name}`);
            cy.log("create some empty dashboard in order to add multiple widgets");
            cy.get("[data-test=dashboard-add-button]").click();
            cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
            cy.get("[data-test=dashboard-add-button-submit]").click();

            cy.get("[data-test=dashboard-add-button]").click();
            cy.get("[data-test=dashboard-add-input-name]").type(`other-${now}`);
            cy.get("[data-test=dashboard-add-button-submit]").click();

            cy.log("kanban can be added into dashboard");
            cy.get("[data-test=project-sidebar]").shadow().contains("Activities").click();

            cy.get("[data-test=add-kanban-to-dashboard]").click();
            cy.get("[data-test=project-dashboard]").eq(1).click();
            cy.get("[data-test=feedback]").contains("The widget has been added successfully");

            cy.log("create some filter to have filtered kanban");
            cy.visitProjectService(project_name, "Tracker");
            cy.get("[data-test=tracker-link-activity]").click();
            cy.get("[data-test=expert-mode]").click();

            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".CodeMirror-code").type("title='title'");
            cy.get("[data-test=expert-query-submit-button]").click();
            cy.get("[data-test=tracker_report_save_dropdown]").click();
            cy.get("[data-test=tracker_report_options]").first().click();
            cy.get("[data-test=tracker_report_updater_duplicate]").first().click();
            cy.get("[data-test=report-name-input]").type("My custom report");
            cy.get("[data-test=save-new-report]").click();
            cy.get("[data-test=tracker_report_options]").first().click();
            cy.get("[data-test=tracker_report_updater_scope]").click();

            cy.log("Kanban can be filtered based on tracker report");
            cy.visitProjectService(project_name, "Kanban");
            cy.get("[data-test=go-to-kanban]").click();
            cy.get("[data-test=kanban-header-edit-button]").click();

            cy.get("[data-test=edit-filter-reports] + .select2-container").click();
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type(`My custom report`, { force: true });
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-results__option").click();
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type(`Activities`, { force: true });
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-results__option").click();
            cy.get("[data-test=save-reports]").click();
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.get("[data-test=kanban-item]").should("have.length", 2);
            cy.get("[data-test=show-report-modal]").click();

            cy.log("asserting that chart legend is rendered");
            cy.get("[data-test=cumulative-flow-chart]").contains("To be done");
            cy.log("force the popup to load by hovering elements in graph");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".tick").last().contains(2);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.get("[data-test=kanban-filter]").select(`My custom report`);

            cy.get("[data-test=show-report-modal]").click();
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".tick").last().contains(1);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.log("Filtered kanban can be added to dashboard");
            cy.get('[data-test="add-kanban-to-dashboard"]').click();
            cy.get("[data-test=project-dashboard]").last().click();
            cy.get("[data-test=feedback]").contains("The widget has been added successfully");

            cy.get("[data-test=kanban-item]").should("have.length", 1);

            cy.log("Filtered kanban can be edited");
            cy.get("[data-test=edit-widget]").click({ force: true });
            cy.get("[data-test=kanban-reports-list]").select("Activities");
            cy.get("[data-test=save-edit-widget]").click();
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.get("[data-test=dashboard-title]").contains("Activities - Activities");

            cy.get("[data-test=edit-widget]").click({ force: true });
            cy.get("[data-test=kanban-reports-list]").select("My custom report");
            cy.get("[data-test=save-edit-widget]").click();
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.get("[data-test=dashboard-title]").contains("Activities - My custom report");

            cy.log("When report is deleted kanban is no longer filtered");
            cy.visitProjectService(project_name, "Tracker");
            cy.get("[data-test=tracker-link-activity]").click();

            cy.get("[data-test=tracker_report_options]").first().click();
            cy.get("[data-test=tracker_report_updater_delete]").click();

            cy.visit(`/projects/${project_name}`);
            cy.get("[data-test=dashboard-tab]").last().click();
            cy.get("[data-test=kanban-item]").should("have.length", 2);
        });
    });

    context("As Project member", function () {
        before(function () {
            cy.getProjectId(`kanban-${now}`).as("project_id");
        });

        it(`I can use the kanban`, function () {
            cy.projectMemberSession();
            cy.visitProjectService(`kanban-${now}`, "Kanban");

            cy.get('[data-test="go-to-kanban"]').click();

            cy.log("I can move cards");
            cy.get("[data-test=kanban-column-to_be_done]").within(() => {
                cy.get("[data-test=tuleap-simple-field-name]").spread(
                    (first_card, second_card, third_card) => {
                        cy.wrap(first_card.innerText).as("first_title");
                        cy.wrap(second_card.innerText).as("second_title");
                        cy.wrap(third_card.innerText).as("third_title");

                        cy.get("[data-test=kanban-item]")
                            .eq(1)
                            .within(() => {
                                cy.get("[data-test=kanban-item-content-move-to-top]").click();
                            });

                        cy.get("[data-test=tuleap-simple-field-name]").spread(
                            (first_card, second_card, third_card) => {
                                expect(first_card.innerText).to.equal(this.second_title);
                                expect(second_card.innerText).to.equal(this.first_title);
                                expect(third_card.innerText).to.equal(this.third_title);
                            },
                        );
                    },
                );
            });

            cy.log(`I can expand cards`);
            // To avoid force click = true
            cy.get("[data-test=kanban-item-content-expand-collapse]").invoke("css", "height", 10);
            cy.get("[data-test=kanban-item-content-expand-collapse]").first().click();
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-up",
            );

            cy.log(`I can filter cards`);
            cy.get("[data-test=kanban-item]").its("length").should("be.gte", 4);
            cy.get("[data-test=kanban-header-search]").type("in progress");
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.contains("[data-test=tuleap-simple-field-name]", "in progress");

            cy.get("[data-test=kanban-header-search]").clear();
            cy.get("[data-test=kanban-item]").its("length").should("be.gte", 4);

            cy.log(`I can check that WIP limit is reached`);
            cy.get("[data-test=kanban-column-in_progress]").within(() => {
                cy.get("[data-test=kanban-column-header-wip-count]").contains("2");
                cy.get("[data-test=kanban-column-header-wip-limit]").should(
                    "have.class",
                    "tlp-badge-warning",
                );
            });

            cy.get("[data-test=kanban-column-to_be_done]").within(() => {
                cy.get("[data-test=kanban-column-header-wip-count]").contains("3");
                cy.get("[data-test=kanban-column-header-wip-limit]").should(
                    "not.have.class",
                    "tlp-badge-warning",
                );
            });

            cy.log(`I can drag and drop cards`);

            const drag_label = `drag${now}`;
            const drop_label = `drop${now}`;

            createCardInColumn("kanban-column-backlog", drag_label);
            createCardInColumn("kanban-column-review", drop_label);

            cy.dragAndDrop(
                `[data-test=kanban-item-content-${drag_label}]`,
                `[data-test=kanban-item-content-${drop_label}]`,
                "top",
            );

            // need to escape for drag and drop only works on body and global body seems erased by angular
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.get("[data-test=kanban-warning-modal]").should("not.exist");
            cy.get("[data-test=kanban-column-review]").within(() => {
                cy.get("[data-test=kanban-item]").its("length").should("be.gte", 1);
            });

            cy.log(`Cumulative flow diagram is displayed`);
            cy.get("[data-test=show-report-modal]").click();
            cy.log("asserting that chart legend is rendered");
            cy.get("[data-test=cumulative-flow-chart]").contains("To be done");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.log(`I can add kanban into my personal dashboard`);
            cy.get("[data-test=add-kanban-to-dashboard]").click();
            cy.get("[data-test=user-dashboard]").last().click();
            cy.get("[data-test=feedback]").contains("The widget has been added successfully");

            cy.get("[data-test=dashboard-widget-plugin_agiledashboard_my_kanban]").click();
            cy.get("[data-test=delete-widget]").first().click({ force: true });
            cy.get("[data-test=delete-widget-button]").first().click();
        });

        it("can collapse column", function () {
            cy.projectAdministratorSession();
            const project_name = `collapse-${now}`;
            cy.createNewPublicProject(project_name, "kanban").then((project_id) => {
                const TITLE_FIELD_NAME = "title";
                cy.getTrackerIdFromREST(project_id, "activity").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "first title",
                        artifact_status: "To be done",
                        title_field_name: TITLE_FIELD_NAME,
                    });
                    cy.createArtifact({
                        tracker_id: tracker_id,
                        artifact_title: "second title",
                        artifact_status: "To be done",
                        title_field_name: TITLE_FIELD_NAME,
                    });
                });
            });
            cy.addProjectMember(project_name, "ProjectMember");

            cy.projectMemberSession();
            cy.visitProjectService(`kanban-${now}`, "Kanban");

            cy.get('[data-test="go-to-kanban"]').click();

            cy.get('[data-test="kanban-column-header-toggle-in_progress"]').within(() => {
                cy.log("The `in progress` column is opened");
                cy.get('[data-test="kanban-column-header-toggle-icon-in_progress"]').should(
                    "have.class",
                    "fa-minus-square",
                );
                cy.root().click();
                cy.log("The `in progress` column is now closed");
                cy.get('[data-test="kanban-column-header-toggle-icon-in_progress"]').should(
                    "have.class",
                    "fa-plus-square",
                );
            });

            cy.reload();
            cy.log("The `in progress` column is still closed");
            cy.get('[data-test="kanban-column-header-toggle-icon-in_progress"]').should(
                "have.class",
                "fa-plus-square",
            );

            cy.log("I can drop card in collapsed column");

            cy.get('[data-test="kanban-column-label-count-at-init-in_progress"]').should(
                "have.text",
                "2",
            );
            const drag_to_collapsed_column_label = `drag_to_collapsed_column${now}`;
            createCardInColumn("kanban-column-backlog", drag_to_collapsed_column_label);

            cy.intercept("PATCH", "/api/v1/kanban/*/items?column_id=*").as("dropCard");
            cy.dragAndDrop(
                `[data-test=kanban-item-content-${drag_to_collapsed_column_label}]`,
                `[data-test=kanban-items-in_progress]`,
                "center",
            );
            cy.wait("@dropCard");
            cy.get('[data-test="kanban-column-label-count-at-init-in_progress"]').should(
                "have.text",
                "3",
            );

            cy.get("[data-test=kanban-column-in_progress]").within(() => {
                cy.log("The `in progress` column is closed");
                cy.get('[data-test="kanban-column-header-toggle-icon-in_progress"]').should(
                    "have.class",
                    "fa-plus-square",
                );
                cy.root().click();
                cy.log("The `in progress` column is opened");
                cy.get('[data-test="kanban-column-header-toggle-icon-in_progress"]').should(
                    "have.class",
                    "fa-minus-square",
                );
                cy.get("[data-test=kanban-item]").its("length").should("be.gte", 3);
            });
        });
    });
});

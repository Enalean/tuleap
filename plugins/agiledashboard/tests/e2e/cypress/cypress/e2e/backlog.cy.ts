/*
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

function getCurrentTimestampInSeconds(): string {
    return String(Date.now()).slice(0, -4);
}

function visitTopBacklog(project_id: number): void {
    cy.visit(`/plugins/agiledashboard/?group_id=${project_id}&action=show-top&pane=topplanning-v2`);
}

function addTask(task_label: string): void {
    cy.getContains("[data-test=backlog-item]", "story #")
        .first()
        .find("[data-test=backlog-item-details-link]")
        .click();
    cy.getContains("[data-test=backlog-add-child]", "Tasks").click();
    cy.get("[data-test=string-field-input]").type(task_label);
    cy.get("[data-test=artifact-modal-save-button]").click();
}

function addUserStory(user_story_label: string): void {
    cy.get("[data-test=add-item]").click();
    cy.getContains("[data-test-static=add-item-in-backlog]", "User Stories").click();
    cy.get("[data-test=string-field-input]").type(user_story_label);
    cy.get("[data-test=artifact-modal-save-button]").click();
}

function createARelease(title: string, start_date: string, end_date: string): void {
    cy.get("[data-test=add-milestone]").click();
    cy.get("[data-test=artifact-modal-form]").within(() => {
        cy.get("[data-test=string-field-input]").type(title);
        // eslint-disable-next-line cypress/no-force -- flatpickr lib sets "readonly" attribute on the input
        cy.getContains("[data-test=date-field]", "Start Date")
            .find("[data-test=date-field-input]")
            .type(start_date, { force: true });
        // eslint-disable-next-line cypress/no-force -- flatpickr lib sets "readonly" attribute on the input
        cy.getContains("[data-test=date-field]", "End Date")
            .find("[data-test=date-field-input]")
            .type(end_date, { force: true });
        cy.get("[data-test=artifact-modal-save-button]").click();
    });
}

function hideEmptyStateSVGToNotConfuseDragAndDrop(): void {
    cy.get("[data-test=empty-milestone]").invoke("hide");
}

describe(`Backlog`, function () {
    let now: string, today_date: Date, today: string, next_month_date: Date, next_month: string;

    beforeEach(function () {
        now = getCurrentTimestampInSeconds();
        today_date = new Date();
        const month_starting_one = today_date.getMonth() + 1;
        today = `${today_date.getFullYear()}-${month_starting_one}-${today_date.getDate()}`;

        next_month_date = new Date(
            today_date.getFullYear(),
            month_starting_one + 1,
            today_date.getDate(),
        );
        next_month = `${next_month_date.getFullYear()}-${next_month_date.getMonth()}-${next_month_date.getDate()}`;
    });

    it(`can be used`, function () {
        cy.projectMemberSession();
        cy.createNewPublicProject(`backlog-${now}`, "scrum").then((project_id) => {
            visitTopBacklog(project_id);
        });

        cy.log("User can create new release");
        createARelease("R1", today, next_month);

        cy.log("Release is created empty");
        cy.get("[data-test=expand-collapse-milestone]").click();
        cy.get("[data-test=empty-milestone]").should("exist");
        cy.get("[data-test=milestone-info-capacity-none]").should("exist");
        cy.get("[data-test=milestone-info-initial-effort-none]").should("exist");

        cy.get("[data-test=milestone-header-dates]")
            .should("contain", today_date.getFullYear())
            .should("contain", today_date.getDate())
            .should("contain", next_month_date.getDate());

        cy.log("Release data can be edited");
        cy.get("[data-test=edit-milestone]").click();
        cy.getContains("[data-test=computed-field]", "Capacity").within(() => {
            cy.get("[data-test=switch-to-manual]").click();
            cy.get("[data-test=computed-field-input]").type("8");
        });

        cy.getContains("[data-test=selectbox-field]", "Status").within(() => {
            cy.searchItemInListPickerDropdown("In development").click();
        });
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("Release card display updated data");
        cy.get("[data-test=milestone-info-capacity]").should("contain", "8");
        cy.get("[data-test=milestone-header-status]").should("contain", "In development");

        cy.log("Bugs can be added to the top backlog");
        cy.get("[data-test=add-item]").click();
        cy.getContains("[data-test-static=add-item-in-backlog]", "Bugs").click();
        cy.get("[data-test=string-field-input]").type("My bug");
        cy.get("[data-test=artifact-modal-save-button]").click();
        cy.get("[data-test=backlog-item]").should("contain", "My bug");

        cy.log("User stories can be added to a release");
        cy.get("[data-test=add-item-milestone-dropdown]").click();
        cy.getContains("[data-test=add-item-in-milestone]", "User Stories").click();
        cy.get("[data-test=string-field-input]").type("My User Story");
        cy.getContains("[data-test=float-field]", "Initial Effort")
            .find("[data-test=float-field-input]")
            .type("5");

        cy.getContains("[data-test=float-field]", "Remaining Effort")
            .find("[data-test=float-field-input]")
            .type("2");
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("User story badge display its progress");
        cy.getContains("[data-test=milestone-content]", "My User Story")
            .parent()
            .within(() => {
                cy.get("[data-test=badge-initial-effort]").should("contain", "5");
                cy.get("[data-test=item-progress]")
                    .should("have.attr", "style")
                    .and("include", "width: 60%");
            });
    });

    it(`Drag and drop at top backlog level`, function () {
        cy.projectMemberSession();
        cy.createNewPublicProject(`backlog-dnd-${now}`, "scrum").then((project_id) => {
            visitTopBacklog(project_id);
        });

        cy.intercept({ method: "GET", url: "/api/v1/backlog_items/*" }).as("getItem");
        cy.intercept({ method: "PATCH", url: "/api/v1/milestones/*/content" }).as("dropElements");
        cy.intercept({ method: "PATCH", url: "/api/v1/backlog_items/*/children" }).as(
            "saveChildren",
        );

        cy.log("User can create new release");
        createARelease("R1", today, next_month);

        cy.log("create some story with some tasks");
        addUserStory("User story 1");
        cy.wait("@getItem");
        addTask("task1");
        cy.wait("@saveChildren");
        cy.wait("@getItem");
        addTask("task2");
        cy.wait("@saveChildren");
        cy.wait("@getItem");
        addTask("task3");
        cy.wait("@saveChildren");
        cy.wait("@getItem");
        addUserStory("User story 2");
        cy.wait("@getItem");
        addUserStory("User story 3");
        cy.wait("@getItem");

        cy.getContains("[data-test=backlog-item]", "User story 1")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();
        cy.getContains("[data-test=backlog-item]", "User story 1")
            .should("contain", "task1")
            .should("contain", "task2")
            .should("contain", "task3");

        cy.log("story can be planned");
        cy.getContains("[data-test=milestone]", "R1").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 1",
            "[data-test=milestone]",
            "R1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.log("User story 1 is now in release R1");
        cy.getContains("[data-test=milestone]", "R1")
            .find("[data-test=milestone-content]")
            .should("contain", "User story 1");

        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 3",
            "[data-test=milestone]",
            "R1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.log("Stories can be reordered");
        cy.get("[data-test=milestone-content]").within(() => {
            cy.get("[data-test=backlog-item-handle]").eq(0).should("contain", "User story 3");
            cy.get("[data-test=backlog-item-handle]").eq(1).should("contain", "User story 1");
        });

        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 1",
            "[data-test=milestone]",
            "R1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.get("[data-test=milestone-content]").within(() => {
            cy.get("[data-test=backlog-item-handle]").eq(0).should("contain", "User story 1");
            cy.get("[data-test=backlog-item-handle]").eq(1).should("contain", "User story 3");
        });

        cy.log("tasks can be moved from one story to another");
        cy.wait("@getItem");
        cy.dragAndDrop(
            "[data-test=backlog-item-child]",
            "task1",
            "[data-test=backlog-item]",
            "User story 2",
        );
        cy.root().click(); // Force AngularJS to react
        cy.wait("@saveChildren");
        /* There is a digest issue in AngularJS code, the User story 2 card should be refreshed,
           but we cannot make cypress trigger it and wait for the request reliably.
        */
        cy.reload();

        cy.getContains("[data-test=backlog-item]", "User story 2")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();
        cy.getContains("[data-test=backlog-item]", "User story 2").should("contain", "task1");

        cy.log("story can be moved to another release");
        cy.getContains("[data-test=milestone]", "R1").click();
        createARelease("R2", today, next_month);
        cy.getContains("[data-test=milestone]", "R2").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 1",
            "[data-test=milestone]",
            "R2",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.getContains("[data-test=milestone]", "R2")
            .find("[data-test=milestone-content]")
            .should("contain", "User story 1");

        cy.log("Reordering task");
        cy.getContains("[data-test=milestone-content]", "User story 1")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();

        cy.dragAndDrop(
            "[data-test=backlog-item-child]",
            "task3",
            "[data-test=backlog-item-child]",
            "task2",
        );

        /* There is a digest issue in AngularJS code, the User story 1 card should be refreshed,
           but we cannot make cypress trigger it and wait for the request reliably.
        */
        cy.reload();
        cy.getContains("[data-test=milestone]", "R2").click();
        cy.wait("@getItem");
        cy.getContains("[data-test=milestone-content]", "User story 1")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();
        cy.wait("@getItem");

        cy.getContains("[data-test=milestone-content]", "User story 1").within(() => {
            cy.get("[data-test=backlog-item-child]").eq(0).should("contain", "task3");
            cy.get("[data-test=backlog-item-child]").eq(1).should("contain", "task2");
        });
    });

    it(`Multi drag and drop at top backlog level`, function () {
        cy.projectMemberSession();
        cy.createNewPublicProject(`backlog-multi-dnd-${now}`, "scrum").then((project_id) => {
            visitTopBacklog(project_id);
        });

        cy.intercept({ method: "GET", url: "/api/v1/backlog_items/*" }).as("getItem");
        cy.intercept({ method: "PATCH", url: "/api/v1/milestones/*/content" }).as("dropElements");

        createARelease("R1", today, next_month);
        addUserStory("User story 1");
        cy.wait("@getItem");
        addUserStory("User story 2");
        cy.wait("@getItem");

        cy.log("story can be planned");
        cy.get("[data-test=milestone]").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body")
            .type("{ctrl}", { release: false })
            .get("[data-test=backlog-item]")
            .click({ multiple: true });

        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 2",
            "[data-test=milestone]",
            "R1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");
        cy.get("[data-test=milestone-backlog-items]")
            .should("contain", "User story 1")
            .should("contain", "User story 2");
    });

    it(`Drag and drop at sprint level`, function () {
        cy.projectMemberSession();
        cy.createNewPublicProject(`sprint-dnd-${now}`, "scrum").then((project_id) => {
            visitTopBacklog(project_id);
        });

        cy.intercept({ method: "GET", url: "/api/v1/backlog_items/*" }).as("getItem");
        cy.intercept({ method: "PATCH", url: "/api/v1/milestones/*/content" }).as("dropElements");
        cy.intercept({ method: "PATCH", url: "/api/v1/backlog_items/*/children" }).as(
            "saveChildren",
        );

        createARelease("R1", today, next_month);
        cy.get("[data-test=milestone]").click();
        cy.get("[data-test=go-to-submilestone-planning]").click();
        createARelease("Sprint 1", today, next_month);

        cy.log("create some story with some tasks");
        addUserStory("User story 1");
        cy.wait("@getItem");
        addTask("task1");
        cy.wait("@saveChildren");
        cy.wait("@getItem");
        addTask("task2");
        cy.wait("@saveChildren");
        cy.wait("@getItem");
        addUserStory("User story 2");
        cy.wait("@getItem");

        cy.getContains("[data-test=backlog-item]", "User story 1")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();
        cy.getContains("[data-test=backlog-item]", "User story 1")
            .should("contain", "task1")
            .should("contain", "task2");

        cy.log("story can be planned");
        cy.getContains("[data-test=milestone]", "Sprint 1").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 1",
            "[data-test=milestone]",
            "Sprint 1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.log("User story 1 is now in Sprint 1");
        cy.getContains("[data-test=milestone]", "Sprint 1")
            .find("[data-test=milestone-content]")
            .should("contain", "User story 1");

        cy.log("tasks can be moved from one story to another");
        cy.wait("@getItem");
        cy.dragAndDrop(
            "[data-test=backlog-item-child]",
            "task1",
            "[data-test=backlog-item]",
            "User story 2",
        );
        cy.root().click(); // Force AngularJS to react
        cy.wait("@saveChildren");
        /* There is a digest issue in AngularJS code, the User story 2 card should be refreshed,
           but we cannot make cypress trigger it and wait for the request reliably.
        */
        cy.reload();

        cy.getContains("[data-test=backlog-item]", "User story 2")
            .find("[data-test=backlog-item-show-children-handle]")
            .should("be.visible")
            .click();
        cy.getContains("[data-test=backlog-item]", "User story 2").should("contain", "task1");

        cy.log("story can be moved to another sprint");
        cy.getContains("[data-test=milestone]", "Sprint 1").click();
        createARelease("Sprint 2", today, next_month);
        cy.getContains("[data-test=milestone]", "Sprint 2").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 1",
            "[data-test=milestone]",
            "Sprint 2",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");

        cy.getContains("[data-test=milestone]", "Sprint 2")
            .find("[data-test=milestone-content]")
            .should("contain", "User story 1");
    });

    it(`Multi drag and drop at sprint level`, function () {
        cy.projectMemberSession();
        cy.createNewPublicProject(`sprint-multi-dnd-${now}`, "scrum").then((project_id) => {
            visitTopBacklog(project_id);
        });

        cy.intercept({ method: "GET", url: "/api/v1/backlog_items/*" }).as("getItem");
        cy.intercept({ method: "PATCH", url: "/api/v1/milestones/*/content" }).as("dropElements");

        createARelease("R1", today, next_month);
        cy.get("[data-test=milestone]").click();
        cy.get("[data-test=go-to-submilestone-planning]").click();

        createARelease("Sprint 1", today, next_month);
        addUserStory("User story 1");
        cy.wait("@getItem");
        addUserStory("User story 2");
        cy.wait("@getItem");

        cy.log("story can be planned");
        cy.get("[data-test=milestone]").click();

        hideEmptyStateSVGToNotConfuseDragAndDrop();
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body")
            .type("{ctrl}", { release: false })
            .get("[data-test=backlog-item]")
            .click({ multiple: true });

        cy.dragAndDrop(
            "[data-test=backlog-item-handle]",
            "User story 2",
            "[data-test=milestone]",
            "Sprint 1",
            "[data-test=milestone-backlog-items]",
        );
        cy.wait("@dropElements");
        cy.get("[data-test=milestone-backlog-items]")
            .should("contain", "User story 1")
            .should("contain", "User story 2");
    });
});

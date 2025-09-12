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

describe(`Backlog`, function () {
    let now: number;

    beforeEach(function () {
        now = Date.now();
        cy.projectMemberSession();
        cy.createNewPublicProject(`backlog-${now}`, "scrum");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").as("body");
    });

    it(`can be used`, function () {
        const today_date = new Date();
        const today = `${today_date.getFullYear()}-${today_date.getMonth()}-${today_date.getDate()}`;

        const next_month_date = new Date(
            today_date.getFullYear(),
            today_date.getMonth() + 1,
            today_date.getDate(),
        );
        const next_month = `${next_month_date.getFullYear()}-${next_month_date.getMonth() + 1}-${next_month_date.getDate()}`;
        cy.projectMemberSession();
        cy.visitProjectService(`backlog-${now}`, "Backlog");

        cy.log("User can create new release");
        cy.get("[data-test=add-milestone]").click();
        cy.get("[data-test=artifact-modal-form]").within(() => {
            cy.get("[data-test=string-field-input]").type("R1");
            cy.getContains("[data-test=date-field]", "Start Date").within(() => {
                cy.get("[data-test=date-field-input]").type(today, { force: true });
            });
            cy.getContains("[data-test=date-field]", "End Date").within(() => {
                cy.get("[data-test=date-field-input]").type(next_month, { force: true });
            });
            cy.get("[data-test=artifact-modal-save-button]").click();
        });

        cy.log("Release is created empty");
        cy.get("[data-test=expand-collapse-milestone]").click();
        cy.get("[data-test=empty-milestone]");
        cy.get("[data-test=milestone-info-capacity-none]");
        cy.get("[data-test=milestone-info-initial-effort-none]");

        cy.get("[data-test=milestone-header-dates]").contains(today_date.getFullYear());
        cy.get("[data-test=milestone-header-dates]").contains(today_date.getDate());
        cy.get("[data-test=milestone-header-dates]").contains(next_month_date.getDate());

        cy.log("Release data can be edited");
        cy.get("[data-test=edit-milestone]").click();
        cy.getContains("[data-test=computed-field]", "Capacity").within(() => {
            cy.get("[data-test=switch-to-manual]").click();
            cy.get("[data-test=computed-field-input]").type("8");
        });

        cy.get("[data-test=list-picker-selection]").first().click();
        cy.root().within(() => {
            cy.get("[data-test-list-picker-dropdown-open]").within(() => {
                cy.get("[data-test=list-picker-item]").contains("In development").click();
            });
        });
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("Release card display updated data");
        cy.get("[data-test=milestone-info-capacity]").contains("8");
        cy.get("[data-test=milestone-header-status]").contains("In development");

        cy.log("Bugs can be added to the top backlog");
        cy.get("[data-test=add-Bugs]").click({ force: true });
        cy.get("[data-test=string-field-input]").type("My bug");
        cy.get("[data-test=artifact-modal-save-button]").click();
        cy.get("[data-test=backlog-item]").contains("My bug");

        cy.log("User stories can be added to a release");
        cy.get("[data-test=add-item-to-submilestone]")
            .contains("User Stories")
            .click({ force: true });
        cy.get("[data-test=string-field-input]").type("My User Story");
        cy.getContains("[data-test=float-field]", "Initial Effort").within(() => {
            cy.get("[data-test=float-field-input]").type("5");
        });

        cy.getContains("[data-test=float-field]", "Remaining Effort").within(() => {
            cy.get("[data-test=float-field-input]").type("2");
        });
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("User story badge display its progress");
        cy.get("[data-test=milestone-content]")
            .contains("My User Story")
            .parent()
            .parent()
            .within(() => {
                cy.get("[data-test=badge-initial-effort]").contains("5");
                cy.get("[data-test=item-progress]")
                    .should("have.attr", "style")
                    .and("include", "width: 60%");
            });
    });
});

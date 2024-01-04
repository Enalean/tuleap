/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

function getToday(): string {
    const date = new Date();

    return `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
}

function getNextMonth(): string {
    const date = new Date(new Date().setMonth(new Date().getMonth() + 1));

    return `${date.getFullYear()}-${date.getMonth() + 1}-${date.getDate()}`;
}

describe("Sidebar", () => {
    let now: number;
    it("Show milestones below backlog", () => {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `sidebar-${now}`;
        cy.createNewPublicProject(project_name, "scrum").then(() => {
            cy.addProjectMember(project_name, "projectMember");
        });

        cy.projectMemberSession();

        cy.log("Add release to project");
        cy.visitProjectService(project_name, "Backlog");
        cy.get("[data-test=add-milestone]").click();
        cy.get("[data-test=string-field-input]").type("My release");
        cy.get("[data-test=date-field-input]").first().type(getToday(), { force: true });
        cy.get("[data-test=date-field-input]").eq(1).type(getNextMonth(), { force: true });
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("Add sprint to project");
        cy.get('[data-test="project-sidebar"]')
            .shadow()
            .find("[data-test=project-sidebar-promoted-item]")
            .contains("My release")
            .click();
        cy.get("[data-test=tab-planning-v2]").click();
        cy.get("[data-test=add-milestone]").click();
        cy.get("[data-test=string-field-input]").type("My sprint");
        cy.get("[data-test=date-field-input]").eq(1).type(getNextMonth(), { force: true });
        cy.get("[data-test=artifact-modal-save-button]").click();
        cy.intercept("GET", "/api/v1/milestones/*").as("getSprint");
        cy.wait("@getSprint");

        cy.log("Check milestone presence");
        cy.get("[data-test=project-sidebar]")
            .shadow()
            .find("[data-test=project-sidebar-promoted-item]")
            .contains("My release");
        cy.get("[data-test=project-sidebar]")
            .shadow()
            .find("[data-test=project-sidebar-promoted-sub-item]")
            .contains("My sprint");
    });
});

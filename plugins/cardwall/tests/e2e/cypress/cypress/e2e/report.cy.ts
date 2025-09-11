/**
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

describe(`Cardwall Report`, () => {
    let now: number;
    let project_name: string;

    beforeEach(() => {
        now = Date.now();
        project_name = `cardwall-${now}`;
        cy.projectMemberSession();
        cy.createNewPublicProject(project_name, "issues");
    });

    it(`Cardwall in user dashboard`, function () {
        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link]").click();
        cy.get("[data-test=add-to-my-dashboard]").click();

        cy.log("create an artifact from dashboard will redirect user on dashboard");
        cy.get("[data-test=add-a-new-artifact]").first().click();
        cy.get("[data-test=title]").type("My first artifact");
        cy.get("[data-test=artifact-submit-button]").click();

        cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");

        cy.log("update an artifact from dashboard will redirect user on dashboard");
        cy.get("[data-test=direct-link-to-artifact]").first().click();
        cy.get("[data-test=edit-field-title]").click();
        cy.get("[data-test=title]").clear().type(`My first artifact edited ${now}`);
        cy.get("[data-test=artifact-submit]").click();

        cy.get("[data-test=my-dashboard-title]").contains("My Dashboard");
    });

    it(`cardwall in project dashboard`, function () {
        cy.projectMemberSession();
        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link]").click();
        cy.get("[data-test=add-to-project-dashboard]").click();

        cy.log("create an artifact from dashboard will redirect user on dashboard");
        cy.get("[data-test=add-a-new-artifact]").click();
        cy.get("[data-test=title]").type("My first artifact");
        cy.get("[data-test=artifact-submit-button]").click();

        cy.get("[data-test=dashboard-project-title-name]").contains(project_name);

        cy.log("update an artifact from dashboard will redirect user on dashboard");
        cy.get("[data-test=direct-link-to-artifact]").click();
        cy.get("[data-test=edit-field-title]").click();
        cy.get("[data-test=title]").clear().type("My first artifact edited");
        cy.get("[data-test=artifact-submit]").click();

        cy.get("[data-test=dashboard-project-title-name]").contains(project_name);
    });
});

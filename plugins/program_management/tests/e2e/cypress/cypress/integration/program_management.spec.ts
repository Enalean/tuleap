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

describe("Program management", () => {
    let program_project_name: string, team_project_name: string, now: number;

    before(() => {
        cy.clearSessionCookie();
        now = Date.now();
    });

    beforeEach(function () {
        cy.preserveSessionCookies();

        program_project_name = "program-" + now;
        team_project_name = "team-" + now;
    });

    it("SAFe usage", () => {
        cy.projectAdministratorLogin();

        cy.log("Create team project");
        cy.visit("/project/new");
        cy.get("[data-test=project-registration-SAFe-templates-tab]").click();
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-program_management_team]"
        ).click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(team_project_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + team_project_name);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });

        cy.log("Create program project");
        cy.visit("/project/new");
        cy.get("[data-test=project-registration-SAFe-templates-tab]").click();
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-program_management_program]"
        ).click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(program_project_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + program_project_name);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });

        cy.log("Add team inside project");
        cy.visitProjectService(program_project_name, "Program");
        cy.get("[data-test=program-go-to-administration]").click({ force: true });
        selectLabelInListPickerDropdown(team_project_name);
        cy.get("[data-test=program-management-add-team-button]").click({ force: true });

        cy.log("Check navbar for program");
        cy.get("[data-test=nav-bar-linked-projects").contains(team_project_name);

        cy.log("Check navbar for team");
        cy.visitProjectService(team_project_name, "Agile Dashboard");
        cy.get("[data-test=nav-bar-linked-projects").contains(program_project_name);
    });
});

type CypressWrapper = Cypress.Chainable<JQuery<HTMLElement>>;

function selectLabelInListPickerDropdown(label: string): CypressWrapper {
    cy.get("[data-test=list-picker-selection]").click();
    return cy.root().within(() => {
        cy.get("[data-test-list-picker-dropdown-open]").within(() => {
            cy.get("[data-test=list-picker-item]").contains(label).click();
        });
    });
}

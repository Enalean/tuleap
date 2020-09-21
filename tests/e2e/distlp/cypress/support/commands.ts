/*
 * Copyright (c) Enalean, 2017- present. All Rights Reserved.
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
 *
 */

declare global {
    // Be consistent with Cypress declaration
    // eslint-disable-next-line @typescript-eslint/no-namespace
    namespace Cypress {
        // Be consistent with Cypress declaration
        // eslint-disable-next-line @typescript-eslint/no-unused-vars
        interface Chainable<Subject> {
            login(): void;

            getProjectId(project_shortname: string): Chainable<JQuery<HTMLElement>>;
        }
    }
}

Cypress.Commands.add("login", () => {
    cy.visit("/");
    cy.get("[data-test=form_loginname]").type("alice");
    cy.get("[data-test=form_pw]").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("getProjectId", (project_shortname: string) => {
    cy.visit(`/projects/${project_shortname}/`);
    return cy.get("[data-test=project-sidebar]").should("have.attr", "data-project-id");
});

export {};

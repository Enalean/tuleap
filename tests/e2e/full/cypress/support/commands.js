/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

Cypress.Commands.add("login", () => {
    cy.visit("/");
    cy.get("#form_loginname").type("alice");
    cy.get("#form_pw").type("Correct Horse Battery Staple{enter}");
});

Cypress.Commands.add("projectMemberLogin", () => {
    cy.visit("/");
    cy.get("#form_loginname").type("bob");
    cy.get("#form_pw").type("Correct Horse Battery Staple{enter}");
});

const cache_service_urls = {};
Cypress.Commands.add("visitProjectService", (project_unixname, service_label) => {
    if (
        cache_service_urls.hasOwnProperty(project_unixname) &&
        cache_service_urls[project_unixname].hasOwnProperty(service_label)
    ) {
        cy.visit(cache_service_urls[project_unixname][service_label]);
        return;
    }

    cy.visit("/projects/" + project_unixname);
    cy.get("[data-test=project-sidebar]")
        .contains(service_label)
        .should("have.attr", "href")
        .then(href => {
            cache_service_urls[project_unixname] = cache_service_urls[project_unixname] || {};
            cache_service_urls[project_unixname][service_label] = href;
            cy.visit(href);
        });
});

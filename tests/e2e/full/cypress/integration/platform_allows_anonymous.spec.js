/*
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

describe("Plateform allows anonymous", function () {
    it("project administrator can define permission access level of mediawiki", function () {
        cy.ProjectAdministratorLogin();

        cy.visit("/plugins/mediawiki/wiki/platform-allows-anonymous/");

        cy.get("[data-test=mediawiki-administration-link]").click();
        cy.get("[data-test=mediawiki-read-ugroups]").select("1");

        cy.get("[data-test=mediawiki-administration-permission-submit-button]").click();
    });

    it("given project is public anonymous can browse it", function () {
        cy.visit("/plugins/mediawiki/wiki/platform-allows-anonymous/");
    });

    it("given project is switched from public to private, anonymous are redirected to login page", function () {
        cy.ProjectAdministratorLogin();

        cy.visitProjectService("platform-allows-anonymous", "Admin");
        cy.get("[data-test=project_visibility]").select("private");
        cy.get("[data-test=project-details-short-description-input]").type("My short description");
        cy.get("[data-test=project-details-submit-button]").click();
        cy.get("[data-test=term_of_service]").click();

        cy.get("[data-test=project-details-submit-button]").click();

        cy.userLogout();

        cy.visit("/plugins/mediawiki/wiki/platform-allows-anonymous/");

        cy.get("[data-test=login-page-title]").contains("Login");
    });
});

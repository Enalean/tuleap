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

describe("Plateform allows restricted", function () {
    it("project administrator can define permission access level of mediawiki", function () {
        cy.updatePlatformVisibilityAndAllowRestricted();

        cy.ProjectAdministratorLogin();

        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");

        cy.get("[data-test=mediawiki-administration-link]").click();
        cy.get("[data-test=mediawiki-read-ugroups]").select("2");

        cy.get("[data-test=mediawiki-administration-permission-submit-button]").click();
    });

    it("given project is public only restricted project members can access it", function () {
        cy.RestrictedMemberLogin();
        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");

        cy.userLogout();

        cy.RestrictedRegularUserLogin();
        //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/", {
            failOnStatusCode: false,
        });

        cy.get("[data-test=error-user-is-restricted]").contains(
            "You have a restricted user account."
        );
    });

    it("given project is switched from public to private, permissions are respected", function () {
        cy.ProjectAdministratorLogin();

        cy.visitProjectService("platform-allows-restricted", "Admin");
        cy.get("[data-test=project_visibility]").select("private");
        cy.get("[data-test=project-details-submit-button]").click();
        cy.get("[data-test=term_of_service]").click();

        cy.get("[data-test=project-details-submit-button]").click();

        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");
        cy.get("[data-test=mediawiki-administration-link]").click();

        cy.get('[data-test=mediawiki-read-ugroups] > [value="3"]')
            .should("be.selected")
            .contains("Project members");
    });
});

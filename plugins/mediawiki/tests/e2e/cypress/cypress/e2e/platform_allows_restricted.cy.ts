/*
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

describe("Platform allows restricted", function () {
    it("project administrator can define permission access level of mediawiki", function () {
        cy.updatePlatformVisibilityAndAllowRestricted();
        cy.projectAdministratorSession();

        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");

        cy.get("[data-test=mediawiki-administration-link]").click({ force: true });
        cy.get("[data-test=mediawiki-read-ugroups]").select("2");

        cy.get("[data-test=mediawiki-administration-permission-submit-button]").click();
    });

    it("given project is public only restricted project members can access it", function () {
        cy.restrictedMemberSession();
        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");

        cy.restrictedRegularUserSession();
        //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/", {
            failOnStatusCode: false,
        });

        cy.get("[data-test=error-user-is-restricted]").contains(
            "You have a restricted user account",
        );
    });

    it("given project is switched from public to private, permissions are respected", function () {
        cy.projectAdministratorSession();

        cy.visitProjectAdministration("platform-allows-restricted");
        cy.switchProjectVisibility("private");
        cy.visit("/plugins/mediawiki/wiki/platform-allows-restricted/");
        cy.get("[data-test=mediawiki-administration-link]").click({ force: true });

        cy.get('[data-test=mediawiki-read-ugroups] > [value="3"]')
            .should("be.selected")
            .contains("Project members");

        cy.visitProjectAdministration("platform-allows-restricted");
        cy.switchProjectVisibility("public");
    });
});

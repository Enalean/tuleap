/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

describe("Mediawiki public projects", function () {
    before(() => {
        cy.clearSessionCookie();
    });

    it("platform administrator can choose mediawiki visibility", function () {
        cy.projectAdministratorLogin();

        cy.visit("/plugins/mediawiki/wiki/mediawiki-public-project/");

        cy.get("[data-test=mediawiki-administration-link]").click({ force: true });
        cy.get("[data-test=mediawiki-read-ugroups]").select("2");
        cy.get("[data-test=mediawiki-write-ugroups]").select("3");

        // button is not visible in view port
        cy.get("[data-test=mediawiki-administration-permission-submit-button]").click({
            force: true,
        });
    });

    it("Registered users can read", function () {
        cy.regularUserLogin();
        cy.visit("/plugins/mediawiki/wiki/mediawiki-public-project/");

        cy.get("[data-test=mediawiki-content]").contains("My custom content");
        // check user can not write
        // ignore rule for mediawiki generated content
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("#ca-edit").should("not.exist");
    });

    it("Project member can read and write", function () {
        cy.projectMemberLogin();
        cy.visit("/plugins/mediawiki/wiki/mediawiki-public-project/");

        cy.get("[data-test=mediawiki-content]").contains("My custom content");
        // check user can not write
        // ignore rule for mediawiki generated content
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("#ca-edit").should("exist");
    });
});

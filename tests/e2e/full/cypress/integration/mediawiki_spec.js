/*
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

describe("Mediawiki", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    it("project is imported", function () {
        cy.ProjectAdministratorLogin();

        cy.visit("/plugins/mediawiki/wiki/mediawiki-public-project/");

        cy.get("#content").contains("My custom content");
        cy.get(".image").should(
            "have.attr",
            "href",
            "/plugins/mediawiki/wiki/mediawiki-public-project/index.php?title=File:Tuleap.png"
        );

        cy.get("[data-test=mediawiki-administration-link]").click({ force: true });

        cy.get('[data-test="mediawiki-read-ugroups"]')
            .find('option[value="3"]')
            .should("be.selected");
        cy.get('[data-test="mediawiki-write-ugroups"]')
            .find('option[value="4"]')
            .should("be.selected");
    });
});

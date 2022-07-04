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

describe("Mediawiki private projects", function () {
    before(() => {
        cy.clearSessionCookie();
    });

    it("Project members can read", function () {
        cy.projectMemberLogin();
        cy.visit("/plugins/mediawiki/wiki/mediawiki-private-project/");

        cy.get("[data-test=mediawiki-content]").contains("My custom content");
    });

    it("Non project members can not access to mediawiki", function () {
        cy.regularUserLogin();

        //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
        cy.visit("/plugins/mediawiki/wiki/mediawiki-private-project/", {
            failOnStatusCode: false,
        });

        cy.get("[data-test=project-is-private-exception]").contains("This is a private project");
    });
});

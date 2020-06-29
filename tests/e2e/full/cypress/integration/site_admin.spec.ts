/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

describe("Site admin", function () {
    context("Platform administrator", function () {
        before(() => {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.platformAdminLogin();
        });

        beforeEach(function () {
            Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        });

        it("can search user on admin page", function () {
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=global-admin-search-user]").type("heisenberg{enter}");
            cy.get("[data-test=user-login]").should("have.value", "Heisenberg");
        });
    });
});

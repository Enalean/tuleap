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

describe("TTM campaign", () => {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.server();
    });

    context("As project administrator", () => {
        before(() => {
            cy.ProjectAdministratorLogin();
        });

        it("Creates a project with TTM", () => {
            cy.visit("/project/new");
            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-agile_alm]"
            ).click();
            cy.get("[data-test=project-registration-next-button]").click();

            cy.get("[data-test=new-project-name]").type("Test TTM " + Date.now());
            cy.get("[data-test=approve_tos]").click();
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=start-working]").click({
                timeout: 20000,
            });
        });
    });
});

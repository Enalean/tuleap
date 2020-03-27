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

describe("Git", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.ProjectAdministratorLogin();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    context("Git repository list", function () {
        it("can create a new repository", function () {
            cy.visit("/plugins/git/git-project/");
            cy.get("[data-test=empty_state_create_repository]").click();
            cy.get("[data-test=create_repository_name]").type("Aquali");
            cy.get("[data-test=create_repository]").click();

            cy.get("[data-test=git_repo_name]").contains("Aquali", {
                timeout: 20000,
            });
        });

        it("shows the new repository in the list", function () {
            cy.visit("/plugins/git/git-project/");
            cy.get("[data-test=repository_name]").contains("Aquali", {
                timeout: 20000,
            });
        });
    });
});

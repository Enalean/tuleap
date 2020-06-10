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

describe("PhpWiki", function () {
    let project_id: string;
    context("Project administrators", function () {
        before(() => {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.ProjectAdministratorLogin();
            cy.getProjectId("permissions-project-01").as("project_id");
        });

        beforeEach(function () {
            Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        });

        it("can access to admin section", function () {
            project_id = this.project_id;
            cy.visit("/wiki/admin/index.php?group_id=" + this.project_id + "&view=wikiPerms");
        });
    });
    context("Project members", function () {
        before(() => {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.projectMemberLogin();
        });

        beforeEach(function () {
            Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        });
        it("should raise an error when user try to access to wiki admin page", function () {
            cy.visit("/wiki/admin/index.php?group_id=" + project_id + "&view=wikiPerms");

            cy.get("[data-test=feedback]").contains(
                "You are not granted sufficient permission to perform this operation."
            );
        });
    });
});

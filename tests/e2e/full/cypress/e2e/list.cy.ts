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

describe("List", function () {
    let project_id: string;
    context("Project administrators", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.getProjectId("permissions-project-01").as("project_id");
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });

        it("can access to admin section", function () {
            project_id = this.project_id;
            cy.visit("/project/" + project_id + "/admin/mailing-lists");
        });
    });
    context("Project members", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectMemberLogin();
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });
        it("should raise an error when user try to access to List admin page", function () {
            //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
            cy.visit("/project/" + project_id + "/admin/mailing-lists", {
                failOnStatusCode: false,
            });

            cy.contains("You don't have permission to access administration of this project.");
        });
    });
});

/**
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

describe("Permissions", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
        cy.getProjectId("permissions-project-01").as("permission_project_id");
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("should raise an error when user try to access to project admin page", function () {
        cy.visit("/project/admin/?group_id=" + this.permission_project_id);

        cy.get("[data-test=feedback]").contains("You do not have permission to view this page");
    });

    it("should raise an error when user try to access to docman admin page", function () {
        cy.visit("/plugins/docman/?group_id=" + this.permission_project_id + "&action=admin");

        cy.get("[data-test=feedback]").contains(
            "You do not have sufficient access rights to administrate the document manager."
        );
    });

    it("should raise an error when user try to access to wiki admin page", function () {
        cy.visit(
            "/wiki/admin/index.php?group_id=" + this.permission_project_id + "&view=wikiPerms"
        );

        cy.get("[data-test=feedback]").contains(
            "You are not granted sufficient permission to perform this operation."
        );
    });

    it("should raise an error when user try to access to plugin SVN admin page", function () {
        cy.visit("/plugins/svn/?group_id=" + this.permission_project_id + "&action=admin-groups");

        cy.get("[data-test=feedback]").contains("Permission Denied");
    });

    it("should raise an error when user try to access to plugin files admin page", function () {
        cy.visit(
            "/file/admin/?group_id=" + this.permission_project_id + "&action=edit-permissions"
        );

        cy.get("[data-test=feedback]").contains(
            "You are not granted sufficient permission to perform this operation."
        );
    });

    it("should raise an error when user try to access to plugin Tracker admin page", function () {
        cy.request({
            url: "/plugins/tracker/global-admin/" + this.permission_project_id,
            failOnStatusCode: false,
        }).then((response) => {
            expect(response.status).to.eq(403);
        });
    });

    it("should raise an error when user try to access to plugin Git admin page", function () {
        cy.visit("/plugins/git/?group_id=" + this.permission_project_id + "&action=admin");

        cy.get("[data-test=git-administration-page]").should("not.exist");
    });

    it("should raise an error when user try to access to Forum admin page", function () {
        cy.visit("/forum/admin/?group_id=" + this.permission_project_id);

        cy.get("[data-test=feedback]").contains(
            "You are not granted sufficient permission to perform this operation."
        );
    });

    it("should raise an error when user try to access to List admin page", function () {
        cy.visit("/mail/admin/?group_id=" + this.permission_project_id);

        cy.get("[data-test=feedback]").contains(
            "You are not granted sufficient permission to perform this operation."
        );
    });

    it("should raise an error when user try to access to News admin page", function () {
        cy.visit("/news/admin/?group_id=" + this.permission_project_id);

        cy.get("[data-test=feedback]").contains(
            "Permission Denied. You have to be an admin on the News service of this project."
        );
    });

    it("should redirect user to Agiledashboard home page when user try to access to Agiledashboard admin page", function () {
        cy.visit(
            "/plugins/agiledashboard/?group_id=" + this.permission_project_id + "&action=admin"
        );

        cy.get("[data-test=scrum_title]").contains("Scrum");
    });
});

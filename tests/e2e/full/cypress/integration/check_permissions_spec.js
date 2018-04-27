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

describe("Permissions", () => {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
        cy.projectMemberLogin();
    });

    beforeEach(() => {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
    });

    it("should raise an error when user try to access to project admin page", () => {
        cy.visit('/project/admin/?group_id=101');

        cy.get('[data-test=feedback]').contains('You do not have permission to view this page');
    });

    it("should raise an error when user try to access to docman admin page", () => {
        cy.visit('/plugins/docman/?group_id=101&action=admin');

        cy.get('[data-test=feedback]').contains('You do not have sufficient access rights to administrate the document manager.');
    });

    it("should raise an error when user try to access to wiki admin page", () => {
        cy.visit('/wiki/admin/index.php?group_id=101&view=wikiPerms');

        cy.get('[data-test=feedback]').contains('You are not granted sufficient permission to perform this operation.');
    });

    it("should raise an error when user try to access to plugin SVN admin page", () => {
        cy.visit('/plugins/svn/?group_id=101&action=admin-groups');

        cy.get('[data-test=feedback]').contains('Permission Denied');
    });

    it("should raise an error when user try to access to plugin files admin page", () => {
        cy.visit('/file/admin/?group_id=101&action=edit-permissions');

        cy.get('[data-test=feedback]').contains('You are not granted sufficient permission to perform this operation.');
    });

    it("should raise an error when user try to access to plugin Tracker admin page", () => {
        cy.visit('/plugins/tracker/?func=global-admin&group_id=101');

        cy.get('[data-test=feedback]').contains('Access denied. You don\'t have permissions to perform this action.');
    });

    it("should raise an error when user try to access to plugin Git admin page", () => {
        cy.visit('/plugins/git/?group_id=101&action=admin');

        cy.get('[data-test=feedback]').contains('You are not allowed to access this page');
    });

    it("should raise an error when user try to access to Forum admin page", () => {
        cy.visit('/forum/admin/?group_id=101');

        cy.get('[data-test=feedback]').contains('You are not granted sufficient permission to perform this operation.');
    });

    it("should raise an error when user try to access to List admin page", () => {
        cy.visit('/mail/admin/?group_id=101');

        cy.get('[data-test=feedback]').contains('You are not granted sufficient permission to perform this operation.');
    });

    it("should raise an error when user try to access to News admin page", () => {
        cy.visit('/news/admin/?group_id=101');

        cy.get('[data-test=feedback]').contains('Permission Denied. You have to be an admin on the News service of this project.');
    });

    it("should redirect user to Agiledashboard home page when user try to access to Agiledashboard admin page", () => {
        cy.visit('/plugins/agiledashboard/?group_id=101&action=admin');

        cy.get('[data-test=scrum_title]').contains('Scrum');
    });
});

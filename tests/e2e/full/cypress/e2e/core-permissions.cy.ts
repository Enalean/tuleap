/*
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

describe("Core", function () {
    let now = 0;
    let restricted_project_notification: string;
    let private_project_notification: string;
    before(() => {
        now = Date.now();
        restricted_project_notification = "restricted-" + now;
        private_project_notification = "private-" + now;
        cy.projectAdministratorSession();
        cy.getProjectId("permissions-project-01").as("project_id");
    });

    it("Permissions are respected", function () {
        cy.log("Project administrator can access core administration pages");
        cy.visit("/forum/admin/?group_id=" + this.project_id);
        cy.visit("/news/admin/?group_id=" + this.project_id);
        cy.visit(`/wiki/admin/index.php?group_id=${this.project_id}&view=wikiPerms`);
        cy.visit(`/file/admin/?group_id=${this.project_id}&action=edit-permissions`);

        cy.projectMemberSession();
        cy.log("Project members has never access to core administration pages");
        checkForumPermissions(this.project_id);
        checkNewsPermissions(this.project_id);
        checkPhpWikiPermissions(this.project_id);
        checkFrsPermissions(this.project_id);
        checkProjectAdminPermissions(this.project_id);
    });

    it("restricted users can request access to a project they are not members of", function () {
        cy.projectAdministratorSession();
        cy.createNewPrivateProject(restricted_project_notification);

        cy.updatePlatformVisibilityAndAllowRestricted();

        cy.restrictedRegularUserSession();
        //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
        cy.visit(`/projects/${restricted_project_notification}`, {
            failOnStatusCode: false,
        });
        cy.get("[data-test=button-ask-to-join-for-restricted]").click();
        const message = "restricted_join";
        cy.get("[data-test=message-ask-to-join-project]").clear().type(message);
        cy.get("[data-test=ask-to-join-project-button]").click();

        cy.assertEmailWithContentReceived("ProjectAdministrator@example.com", message);

        cy.updatePlatformVisibilityForAnonymous();
    });

    it("users can request access to a private project", function () {
        cy.projectAdministratorSession();
        cy.createNewPrivateProject(private_project_notification);

        cy.regularUserSession();
        //failOnStatusCode ignore the 401 thrown in HTTP Headers by server
        cy.visit(`/projects/${private_project_notification}`, {
            failOnStatusCode: false,
        });

        cy.get("[data-test=button-ask-to-join-private-project]").click();
        const message = "private_join";
        cy.get("[data-test=message-ask-to-join-project]").clear().type(message);
        cy.get("[data-test=ask-to-join-project-button]").click();

        cy.assertEmailWithContentReceived("ProjectAdministrator@example.com", message);
    });
});

function checkForumPermissions(project_id: string): void {
    cy.visit("/forum/admin/?group_id=" + project_id);
    cy.get("[data-test=feedback]").contains(
        "You are not granted sufficient permission to perform this operation.",
    );
}

function checkNewsPermissions(project_id: string): void {
    cy.visit("/news/admin/?group_id=" + project_id);

    cy.get("[data-test=feedback]").contains(
        "Permission Denied. You have to be an admin on the News service of this project.",
    );
}

function checkPhpWikiPermissions(project_id: string): void {
    cy.visit(`/wiki/admin/index.php?group_id=${project_id}&view=wikiPerms`);

    cy.get("[data-test=feedback]").contains(
        "You are not granted sufficient permission to perform this operation.",
    );
}

function checkFrsPermissions(project_id: string): void {
    cy.visit(`/file/admin/?group_id=${project_id}&action=edit-permissions`);
    cy.get("[data-test=feedback]").contains(
        "You are not granted sufficient permission to perform this operation.",
    );
}

function checkProjectAdminPermissions(project_id: string): void {
    cy.visit(`/project/admin/?group_id=${project_id}`, { failOnStatusCode: false });

    cy.contains("You don't have permission to access administration of this project.");
}

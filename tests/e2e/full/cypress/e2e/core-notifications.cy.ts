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

import { WEB_UI_SESSION } from "@tuleap/cypress-utilities-support";

context("Platform notifications", function () {
    let now: number, project: string, project_member: string;

    before(() => {
        now = Date.now();
        project = `new-project-${now}`;
        project_member = `project-member-${now}`;
    });

    beforeEach(() => {
        cy.log("Be sure that platform is in auto-approval for projects");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=project-settings-link]").click();

        cy.get("[data-test=projects-must-be-approved]").uncheck();
        cy.get("[data-test=save-settings]").click();
    });

    it("sends a notification when a new project is created", function () {
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=project-settings-link]").click();

        cy.get("[data-test=projects-must-be-approved]").check();
        cy.get("[data-test=save-settings]").click();

        cy.get("[data-test=feedback]").contains("Settings saved");

        cy.projectMemberSession();
        cy.visit("/");
        cy.get("[data-test=new-button]").click();
        cy.get("[data-test=create-new-item]").click();
        cy.get(
            `[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]`
        ).click();
        cy.get('[data-test="project-registration-next-button"]').click();
        cy.get('[data-test="new-project-name"]').type(project);
        cy.get('[data-test="approve_tos"]').check();

        cy.intercept(`/api/projects`).as("createProject");
        cy.get('[data-test="project-registration-next-button"]').click();
        cy.wait("@createProject", { timeout: 60000 });

        cy.log("Validate the project");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=pending-projects-button]").click();
        cy.get("[data-test=validate-project-button]").click();

        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectMember@example.com",
            "Project approved"
        );
    });

    it("Notifications for membership management", function () {
        cy.projectAdministratorSession();
        cy.log("sends a notification when a project member is added");
        cy.createNewPublicProject(project_member, "issues");
        cy.visit(`/projects/${project_member}`);
        cy.addProjectMember("ProjectMember");

        cy.assertUserMessagesReceivedByWithSpecificContent(
            "ProjectMember@example.com",
            "You are now a member of project "
        );
        cy.log("sends a notification when a project member leave project");
        cy.projectMemberSession();
        cy.visit("/my");
        cy.get(`[data-test=my-project-widget]`)
            .contains("tr", project_member)
            .within(() => {
                cy.get(`[data-test=leave-project-button]`).click();
                cy.on("window:alert", (txt) => {
                    expect(txt).to.contains("Quit this project?");
                });

                cy.assertUserMessagesReceivedByWithSpecificContent(
                    "ProjectAdministrator@example.com",
                    "user ProjectMember has chosen to"
                );
            });
    });

    it("sends a notification when a new user is created", function () {
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=user-settings-link]").click();

        cy.get("[data-test=user-must-be-approved]").check();
        cy.get("[data-test=save-settings]").click();

        cy.get("[data-test=feedback]").contains("settings have been saved");

        cy.session([WEB_UI_SESSION, "newUserNotification"], () => {
            cy.visit("/");
            // Do not log in
        });

        cy.visit("/account/register.php");

        cy.get("[data-test=user-login]").type(`user-${now}`);
        cy.get("[data-test=user-email]").type("user@example.com");
        cy.get("[data-test=user-pw]").type("welcome0");
        cy.get("[data-test=user-pw2]").type("welcome0");
        cy.get("[data-test=user-name]").type(`user-${now}`);
        cy.get("[data-test=form_register_purpose]").type("My purpose");

        cy.get("[data-test=register-user-button]").click();

        cy.assertUserMessagesReceivedByWithSpecificContent(
            "codendi-admin@tuleap",
            "A new user has just registered on Tuleap"
        );

        cy.siteAdministratorSession();
        cy.visit("/admin/");

        cy.get("[data-test=pending-users-link]").click();
        cy.get("[data-test=activate-user]").click();

        cy.assertUserMessagesReceivedByWithSpecificContent(
            "user@example.com",
            "You are now a registered user on Tuleap"
        );
    });
});

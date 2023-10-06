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

context("PHPWiki notifications", function () {
    let now: number,
        project: string,
        user1: string,
        user2: string,
        user3: string,
        user4: string,
        user5: string;

    const PASSWORD = "welcome0";

    const loginAs = (user: string): void => {
        cy.session([WEB_UI_SESSION, user], () => {
            cy.visit("/");
            cy.get("[data-test=form_loginname]").type(user);
            cy.get("[data-test=form_pw]").type(`${PASSWORD}{enter}`);
        });
    };

    before(() => {
        now = Date.now();
        project = `phpwiki-project-${now}`;
        user1 = `phpwiki-member-1-${now}`;
        user2 = `phpwiki-member-2-${now}`;
        user3 = `phpwiki-member-3-${now}`;
        user4 = `phpwiki-member-4-${now}`;
        user5 = `phpwiki-member-5-${now}`;
    });

    it("", () => {
        cy.log("Be sure that platform is in auto-approval for projects and uses");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=project-settings-link]").click();

        cy.get("[data-test=projects-must-be-approved]").uncheck();
        cy.get("[data-test=save-settings]").click();

        cy.updatePlatformAndMakeUserInAutoApprovalMode();

        cy.log("Create project");
        cy.projectAdministratorSession();
        cy.visit("/project/new");
        cy.get("[data-test=project-registration-advanced-templates-tab]").click();
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-other-user-project]",
        ).click();
        cy.get("[data-test=from-another-project]").select("PhpWiki Template");
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(project);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });
        cy.visitProjectService(project, "Wiki");
        cy.get("[data-test=create-wiki]").click();

        cy.log("Create users and add them to the project");
        [user1, user2, user3, user4, user5].forEach((user) => {
            cy.siteAdministratorSession();
            cy.visit("/admin/");
            cy.get("[data-test=new-user-link]").click();
            cy.get("[data-test=user-login]").type(user);
            cy.get("[data-test=user-email]").type(`${user}@example.com`);
            cy.get("[data-test=user-pw]").type(PASSWORD);
            cy.get("[data-test=user-name]").type(user);
            cy.get("[data-test=register-user-button]").click();
            cy.addProjectMember(project, user);
        });

        cy.siteAdministratorSession();
        cy.log("Users subscribe to phpwiki change");
        [user1, user2, user3, user4, user5].forEach((user) => {
            loginAs(user);
            cy.visitProjectService(project, "Wiki");
            cy.get("[data-test=wiki-preferences]").click();
            cy.get("[data-test=wiki-notification-pages]").type("*");
            cy.get("[data-test=wiki-form-notification]").submit();
        });

        cy.deleteAllMessagesInMailbox();

        cy.log("wiki page is updated so we have notifications");
        loginAs(user5);
        cy.visitProjectService(project, "Wiki");
        cy.get("[data-test=phpwiki-page-HomePage]").click();
        cy.get("[data-test=php-wiki-edit-page]").contains("Edit").click();
        cy.get("[data-test=textarea-wiki-content]").clear().type("Lorem ipsum");
        cy.get("[data-test=edit-page-action-buttons]").contains("Save").click();

        [user1, user2, user3, user4, user5].forEach((user) => {
            cy.assertEmailWithContentReceived(`${user}@example.com`, "Page change HomePage");
        });

        cy.deleteAllMessagesInMailbox();

        cy.log("Make sure that some users don't have access anymore to pages");
        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project);
        cy.get("[data-test=admin-nav-groups]").click();
        cy.get("[data-test=project-admin-ugroups-modal]").click();
        cy.get("[data-test=ugroup_name]").clear().type("AccessToHomePage");
        cy.get("[data-test=create-user-group]").click();
        [user1, user2, user4, user5].forEach((user) => {
            cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type(`${user}{enter}`);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();
        });
        cy.get("[data-test=admin-nav-groups]").click();
        cy.get("[data-test=project-admin-ugroups-modal]").click();
        cy.get("[data-test=ugroup_name]").clear().type("AccessToWikiService");
        cy.get("[data-test=create-user-group]").click();
        [user1, user2, user3, user5].forEach((user) => {
            cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type(`${user}{enter}`);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();
        });

        cy.visitProjectService(project, "Wiki");
        cy.get("[data-test=wiki-admin]").click();
        cy.get("[data-test=manage-wiki-documents]").click();
        cy.get("[data-test=table-test]").contains("[Define Permissions]").click();
        cy.get("[data-test=form-permissions] select").select("AccessToHomePage");
        cy.get("[data-test=submit-form-permissions]").click();

        cy.visitProjectService(project, "Wiki");
        cy.get("[data-test=wiki-admin]").click();
        cy.get("[data-test=set-wiki-permissions]").click();
        cy.get("[data-test=form-permissions] select").select("AccessToWikiService");
        cy.get("[data-test=submit-form-permissions]").click();

        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=global-admin-search-user]").clear().type(`${user1}{enter}`);
        cy.get("[data-test=user-status]").select("Deleted");
        cy.get("[data-test=save-user]").click();
        cy.get("[data-test=global-admin-search-user]").clear().type(`${user2}{enter}`);
        cy.get("[data-test=user-status]").select("Suspended");
        cy.get("[data-test=save-user]").click();

        cy.log("wiki page is updated so we have notifications");
        loginAs(user5);
        cy.visitProjectService(project, "Wiki");
        cy.get("[data-test=phpwiki-page-HomePage]").click();
        cy.get("[data-test=php-wiki-edit-page]").contains("Edit").click();
        cy.get("[data-test=textarea-wiki-content]").clear().type("Lorem ipsum doloret");
        cy.get("[data-test=edit-page-action-buttons]").contains("Save").click();

        cy.log("User1 is deleted: no notifications");
        cy.log("User2 is suspended: no notifications");
        cy.log("User3 has no access to a page: no notifications");
        cy.log("User4 has no access to wiki service: no notifications");
        [user1, user2, user3, user4].forEach((user) => {
            loginAs(user);
            cy.assertNotEmailWithContentReceived(`${user}@example.com`, "Page change HomePage");
        });
        cy.log("Remaining user should still get notifications");
        loginAs(user5);
        cy.assertEmailWithContentReceived(`${user5}@example.com`, "Page change HomePage");
    });
});

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

Cypress.Commands.add("updatePlatformVisibilityAndAllowRestricted", (): void => {
    cy.siteAdministratorSession();
    cy.visit("/admin/");
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-restricted]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });

    cy.get("[data-test=global-admin-search-user]").type("RestrictedMember{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();

    cy.get("[data-test=global-admin-search-user]").type("RestrictedRegularUser{enter}");
    cy.get("[data-test=user-status]").select("Restricted");
    cy.get("[data-test=save-user]").click();

    cy.anonymousSession();
});

Cypress.Commands.add("updatePlatformVisibilityForAnonymous", (): void => {
    cy.siteAdministratorSession();
    cy.visit("/admin/");
    cy.get("[data-test=global_access_right]").click({ force: true });

    cy.get("[data-test=access_mode-anonymous]").check();

    cy.get("[data-test=update_forge_access_button]").click({ force: true });

    cy.anonymousSession();
});

Cypress.Commands.add("updatePlatformAndMakeUserInAutoApprovalMode", (): void => {
    cy.siteAdministratorSession();
    cy.visit("/admin/");
    cy.get("[data-test=user-settings-link]").click();

    cy.get("[data-test=user-must-be-approved]").uncheck();
    cy.get("[data-test=save-settings]").click();

    cy.anonymousSession();
});

Cypress.Commands.add("updatePlatformAndMakeUserInAdminApprovalMode", (): void => {
    cy.siteAdministratorSession();
    cy.visit("/admin/");
    cy.get("[data-test=user-settings-link]").click();

    cy.get("[data-test=user-must-be-approved]").check();
    cy.get("[data-test=save-settings]").click();

    cy.anonymousSession();
});

Cypress.Commands.add("addBotMattermost", (bot_name: string): void => {
    cy.siteAdministratorSession();
    cy.log("Add new bot");
    cy.visit("/admin/");
    cy.get("[data-test=botmattermost]").click();
    cy.get("[data-test=add-bot]").click();
    cy.get("[data-test=bot-mattermost-name]").type(bot_name);
    cy.get("[data-test=bot-mattermost-webhook-url]").type("https://example.com");
    cy.get("[data-test=add-bot-button]").click();
    cy.get("[data-test=bot-list]").contains(bot_name);

    cy.anonymousSession();
});
Cypress.Commands.add("deleteBotMattermost", (bot_name: string): void => {
    cy.siteAdministratorSession();
    cy.log("remove bot");
    cy.visit("/admin/");
    cy.get("[data-test=botmattermost]").click();
    cy.get("[data-test=delete-bot]").click();
    cy.get("[data-test=confirm-bot-delete]").click();
    cy.get("[data-test=bot-list]").should("not.contain", bot_name);

    cy.anonymousSession();
});

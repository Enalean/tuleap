/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

describe(`Bot Mattermost`, function () {
    it(`can configure backlog notifications`, function () {
        cy.log("Add new bot");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=botmattermost]").click();
        cy.get("[data-test=add-bot]").click();
        cy.get("[data-test=bot-mattermost-name]").type("My bot");
        cy.get("[data-test=bot-mattermost-webhook-url]").type("https://example.com");
        cy.get("[data-test=add-bot-button]").click();
        cy.get("[data-test=bot-list]").contains("My bot");

        cy.log("configure backlog notifications");
        cy.projectAdministratorSession();
        const now = Date.now();
        const project_name = "ad-mattermost-" + now;
        cy.createNewPublicProject(project_name, "scrum");
        cy.visitProjectService(project_name, "Backlog");
        // admin link is displayed on hover, needs to force click
        // eslint-disable-next-line cypress/no-force
        cy.get("[data-test=link-to-ad-administration]").click({ force: true });
        cy.get("[data-test=add-mattermost-notification]").click();
        cy.get("[data-test=channels] + .select2-container").click();
        // No data selector for select2
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-search__field").type(`test{enter}`);
        cy.get("[data-test=bot-agiledashboard-send-time]").type("08:00");
        cy.get('[data-test="add-notification-button"]').click();

        cy.get("[data-test=configured-bot-mattermost]").contains("My bot");

        cy.log("remove bot");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=botmattermost]").click();
        cy.get("[data-test=delete-bot]").click();
        cy.get("[data-test=confirm-bot-delete]").click();
        cy.get("[data-test=bot-list]").should("not.contain", "My bot");
    });
});

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

export function disableSpecificErrorThrownByPrototypeJs(): void {
    cy.on("uncaught:exception", (err) => {
        // the message bellow is only thrown by prototypejs, if any other js exception is thrown
        // the test will fail
        if (err.message.includes("Assignment to constant variable")) {
            return false;
        }
    });
}

describe(`Bot Mattermost`, function () {
    it(`can configure git notifications`, function () {
        cy.addBotMattermost("My bot");
        cy.log("configure git notifications");
        cy.projectAdministratorSession();
        const now = Date.now();
        const project_name = "git-mattermost-" + now;
        cy.createNewPublicProject(project_name, "agile_alm");
        cy.visitProjectService(project_name, "Git");

        cy.get("[data-test=create-repository-button]").click();
        cy.get("[data-test=create_repository_name]").type("repository");
        cy.get("[data-test=create_repository]").click();

        cy.get("[data-test=git_repo_name]").contains("repository", {
            timeout: 20000,
        });
        cy.get("[data-test=git-repository-settings]").click();
        cy.get("[data-test=mail]").click();

        disableSpecificErrorThrownByPrototypeJs();

        cy.get("[data-test=add-git-bot]").click();

        cy.get("[data-test=channels]").within(() => {
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-input").type(`test{enter}`, { force: true });
        });
        cy.get('[data-test="add-notification-button"]').click();
        cy.get("[data-test=git-bot-mattermost-list]").contains("test");

        cy.deleteBotMattermost("My bot");
    });
});

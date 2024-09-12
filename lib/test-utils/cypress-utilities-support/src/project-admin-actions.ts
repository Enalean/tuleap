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

Cypress.Commands.add("visitProjectAdministration", (project_unixname: string) => {
    cy.visit("/projects/" + project_unixname);
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add("visitProjectAdministrationInCurrentProject", () => {
    cy.get('[data-test="project-administration-link"]', { includeShadowDom: true }).click();
});

Cypress.Commands.add(
    "switchProjectVisibility",
    (project_unix_name: string, visibility: string): void => {
        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project_unix_name);
        cy.get("[data-test=admin-nav-details]").click();
        cy.get("[data-test=project_visibility]").select(visibility);
        cy.get("[data-test=project-details-short-description-input]").type("My short description");
        cy.get("[data-test=project-details-submit-button]").click();
        cy.get("[data-test=term_of_service]").click({ force: true });

        cy.get("[data-test=project-details-submit-button]").click();

        cy.anonymousSession();
    },
);

Cypress.Commands.add("addProjectMember", (project_unix_name: string, user_name: string): void => {
    cy.projectAdministratorSession();
    cy.visitProjectAdministration(project_unix_name);
    cy.get("[data-test=project-admin-members-add-user-select] + .select2-container").click();

    cy.get(".select2-search__field").type(`${user_name}{enter}`);

    cy.get(".select2-result-user").click();
    cy.get('[data-test="project-admin-submit-add-member"]').click();
    cy.anonymousSession();
});

Cypress.Commands.add(
    "removeProjectMember",
    (project_unix_name: string, user_name: string): void => {
        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project_unix_name);
        cy.get("[data-test=project-admin-members-list]")
            .contains(user_name)
            .should("have.attr", "data-user-id")
            .then((user_id) => {
                cy.get(`[data-test=remove-user-${user_id}]`).click();
                cy.get("[data-test=remove-from-member]").click();
            });
        cy.anonymousSession();
    },
);

Cypress.Commands.add(
    "addUserGroupWithUsers",
    (user_group_name: string, users: Array<string>): void => {
        cy.log("Add a group with a user to project");
        cy.get("[data-test=project-admin-ugroups-modal]").click();
        cy.get("[data-test=ugroup_name]").type(user_group_name);
        cy.get("[data-test=create-user-group]").click();

        cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
        users.forEach((user) => {
            // ignore rule for select2

            cy.get(".select2-search__field").type(`${user}{enter}`);

            cy.get(".select2-result-user").click();
        });

        cy.get('[data-test="project-admin-submit-add-member"]').click();
    },
);

Cypress.Commands.add("enableService", (project_name: string, service_short_name: string): void => {
    cy.visitProjectAdministration(project_name);
    cy.get("[data-test=project-administration-navigation]").within(() => {
        cy.get("[data-test=services]").click({ force: true });
    });

    cy.get(`[data-test=edit-service-plugin_${service_short_name}]`).click();

    cy.get("[data-test=service-edit-modal]").within(() => {
        cy.get("[data-test=service-is-used]").click();
        cy.get("[data-test=save-service-modifications]").click();
    });

    cy.get("[data-test=feedback]").contains("Service updated successfully", {
        timeout: 40000,
    });
});

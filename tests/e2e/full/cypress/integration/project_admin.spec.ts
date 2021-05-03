/*
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

describe("Project admin", function () {
    let project_id: string;
    const project_admin_group_id = 4;
    before(() => {
        cy.clearSessionCookie();
        cy.projectAdministratorLogin();
        cy.getProjectId("project-admin-test").as("project_id");
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    context("project basic administration", function () {
        it("should be able to create a new public project", function () {
            cy.get("[data-test=new-button]").click();
            cy.get("[data-test=create-new-item]").click();

            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
            ).click();

            cy.get("[data-test=project-registration-next-button").click();

            cy.get("[data-test=new-project-name]").type("project admin test");
            cy.get("[data-test=approve_tos]").check();

            cy.get("[data-test=project-registration-next-button]").click();
        });

        it("should be able to create a new private project", function () {
            cy.get("[data-test=new-button]").click();
            cy.get("[data-test=create-new-item]").click();

            cy.get(
                "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
            ).click();

            cy.get("[data-test=project-registration-next-button").click();

            cy.get("[data-test=new-project-name]").type("private project");
            // force is due to select2 usage
            // eslint-disable-next-line cypress/no-force
            cy.get("[data-test=register-new-project-information-list]").select("Private", {
                force: true,
            });
            cy.get("[data-test=approve_tos]").check();

            cy.get("[data-test=project-registration-next-button]").click();
        });

        it("should be able to add users to a public project", function () {
            cy.visitProjectService("project-admin-test", "Admin");
            cy.contains("Members").click();

            cy.get(
                "[data-test=project-admin-members-add-user-select] + .select2-container"
            ).click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("SecondProjectAdministrator{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.get("[data-test=feedback]").contains("User added", {
                timeout: 40000,
            });

            project_id = this.project_id;
            const project_admin_group_id = 4;
            cy.visit(
                "/project/admin/editugroup.php?group_id=" +
                    project_id +
                    "&ugroup_id=" +
                    project_admin_group_id
            );

            cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("SecondProjectAdministrator{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();

            cy.contains("SecondProjectAdministrator", {
                timeout: 40000,
            });
        });

        it("should verify that icon for project visibility is correct", function () {
            cy.visit("/projects/project-admin-test");

            cy.get("[data-test=project-icon]").eq(0).should("have.class", "fa-lock-open");

            cy.visit("/projects/private-project");

            cy.get("[data-test=project-icon]").eq(0).should("have.class", "fa-lock");
        });

        it("should verify that a project administrator can enable a new service", () => {
            cy.visitProjectService("project-admin-test", "Admin");
            cy.contains("Services").click({ force: true });

            cy.get("[data-test=edit-service-plugin_svn]").click();

            cy.get("[data-test=service-edit-modal]").within(() => {
                cy.get("[data-test=service-is-used]").click();
                cy.get("[data-test=save-service-modifications]").click();
            });

            cy.get("[data-test=feedback]").contains("Successfully Updated Service", {
                timeout: 40000,
            });
        });

        it("should be able to add member that is not project member as project administrator", () => {
            cy.visit(
                "/project/admin/editugroup.php?group_id=" +
                    project_id +
                    "&ugroup_id=" +
                    project_admin_group_id
            );

            cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
            // ignore rule for select2
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-search__field").type("Heisenberg{enter}");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get(".select2-result-user").click();
            cy.get('[data-test="project-admin-submit-add-member"]').click();
            cy.get('[data-test="project-admin-confirm-add-member"]').click();

            cy.get("[data-test=feedback]").contains("User added", {
                timeout: 40000,
            });
            cy.contains("Heisenberg");
        });
    });
});

context("Project member", function () {
    before(() => {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    it("should raise an error when user try to access to project admin page", function () {
        //here we don't care about project, member should not be admin of any project
        cy.visit("/project/admin/?group_id=101", { failOnStatusCode: false });

        cy.contains("You don't have permission to access administration of this project.");
    });
});

context("Restricted users", function () {
    it("should not be able to create new project", function () {
        cy.clearSessionCookie();

        // enable restricted users
        cy.updatePlatformVisibilityAndAllowRestricted();

        // check restricted user can NOT create a project
        cy.restrictedRegularUserLogin();
        cy.get("[data-test=new-button]").should("not.exist");

        cy.userLogout();

        //enable project creation for restricted users
        cy.platformAdminLogin();
        cy.get("[data-test=platform-administration-link]").click();

        cy.get("[data-test=project-settings-link]").click();

        // need to force, tlp switch hides checkbox
        // eslint-disable-next-line cypress/no-force
        cy.get("[data-test=restricted-users-can-create-project]").check({ force: true });

        cy.get("[data-test=save-settings]").click();
        cy.userLogout();

        // restricted users can now create projects
        cy.restrictedRegularUserLogin();
        cy.get("[data-test=new-button]").click();
        cy.get("[data-test=create-new-item]").click();

        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
        );
        cy.userLogout();

        // make platform accessible to anonymous again
        cy.updatePlatformVisibilityForAnonymous();
    });
});

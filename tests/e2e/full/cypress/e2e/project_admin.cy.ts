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

const project_admin_group_id = 4;

context("Suspended users", function () {
    let project_name: string, now: number;

    before(() => {
        now = Date.now();
        project_name = "test-suspended-" + now;
    });

    it("can be removed from administrator and members", function () {
        cy.log("As precondition for test, be sure that user who will be suspended is Active");
        cy.siteAdministratorSession();
        updateUserStatus("Active");

        cy.log("create new project for suspended users");
        cy.projectAdministratorSession();
        createNewPublicProject(project_name);
        cy.visitProjectAdministration(project_name);

        cy.log("add suspended user to project members");
        addNonMemberAdministrator("suspendedUser");

        cy.log("Make SuspendedUser suspended");
        cy.siteAdministratorSession();
        updateUserStatus("Suspended");

        cy.log("Remove Suspended user from Administrators and members");
        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project_name);

        cy.log("Check user can be removed from administrators");
        cy.get("[data-test=admin-nav-groups]").click();
        cy.get(`[data-test=ugroup-${project_admin_group_id}-details]`).click();
        cy.get("[data-test=project-admin-ugroups-members-list]")
            .contains("Suspended")
            .should("have.attr", "data-user-id")
            .then((user_id) => {
                cy.get(`[data-test=remove-user-${user_id}]`).click();
                cy.get("[data-test=remove-from-ugroup]").click();
            });
        cy.get("[data-test=project-admin-ugroups-members-list]").should("not.contain", "Suspended");

        cy.log("Check user can be removed from members");
        cy.removeProjectMember("Suspended");
    });
});

context("Disk usage", function () {
    let project_name: string, now: number;

    before(() => {
        now = Date.now();
        project_name = "test-disk-usage-" + now;
    });

    it("project admin can check diskusage", function () {
        cy.projectAdministratorSession();

        cy.log("create project");
        createNewPublicProject(project_name);
        cy.visitProjectAdministration(project_name);
        cy.log("add project member");
        addUser("projectMember");

        cy.visitProjectAdministration(project_name);
        // need to force true because click on data does not display the submenu
        cy.get("[data-test=statistics-disk-usage]").click({ force: true });
        cy.get("[data-test=disk-usage-graph]").contains("Remaining space");
        cy.get("[data-test=statistics-period]").contains("last months");
        cy.get("[data-test=table-test]").contains("Service");
        cy.get("[data-test=table-test]").contains("Size evolution");

        cy.get("[data-test=last-year-statistics]").click();
        cy.get("[data-test=statistics-period]").contains("last year");
        cy.get("[data-test=table-test]").contains("Service");
        cy.get("[data-test=table-test]").contains("Size evolution");

        cy.visit(`/projects/${project_name}/`);
        cy.log("Add widget, and check its display");
        cy.get("[data-test=dashboard-configuration-button]").click();
        cy.get("[data-test=dashboard-add-widget-button]").click();
        //need to force true cause the line is not visible in modal
        cy.get("[data-test=plugin_statistics_projectstatistics]").click({
            force: true,
        });
        cy.get("[data-test=dashboard-add-widget-button-submit]").click();

        cy.contains("Total project size");

        cy.log("project member can see the statistics");
        cy.projectMemberSession();
        cy.visit(`/projects/${project_name}/`);

        cy.contains("Total project size");
    });
});

describe("Project admin", function () {
    let public_project_name: string, private_project_name: string, now: number;

    before(() => {
        now = Date.now();
        public_project_name = "public-admin-" + now;
        private_project_name = "private-admin-" + now;
    });

    context("project basic administration", function () {
        it("Emoji is displayed", function () {
            cy.projectAdministratorSession();
            cy.visitProjectAdministration("project-admin-test");
            cy.get("[data-test=project-sidebar-title", { includeShadowDom: true }).contains("😀");
        });

        it("should be able to manipulate projects", function () {
            cy.projectAdministratorSession();
            createNewPublicProject(public_project_name);
            createNewPrivateProject(private_project_name);

            cy.log("Check project visibility");
            cy.visit("/projects/" + public_project_name);
            cy.get("[data-test=project-icon]", { includeShadowDom: true })
                .eq(0)
                .should("have.class", "fa-lock-open");

            cy.visit("/projects/" + private_project_name);
            cy.get("[data-test=project-icon]", { includeShadowDom: true })
                .eq(0)
                .should("have.class", "fa-lock");

            cy.log("Check administrator can enable a service");
            cy.visitProjectAdministration(public_project_name);
            cy.get("[data-test=project-administration-navigation]").within(() => {
                cy.get("[data-test=services]").click({ force: true });
            });

            cy.get("[data-test=edit-service-plugin_svn]").click();

            cy.get("[data-test=service-edit-modal]").within(() => {
                cy.get("[data-test=service-is-used]").click();
                cy.get("[data-test=save-service-modifications]").click();
            });

            cy.get("[data-test=feedback]").contains("Service updated successfully", {
                timeout: 40000,
            });
        });

        it("should be able to add users to a public project", function () {
            const project_name = "project-admin-" + now;

            cy.projectAdministratorSession();
            createNewPublicProject(project_name);
            cy.visitProjectAdministration(project_name);
            addUser("SecondProjectAdministrator");
            addAdminUser("SecondProjectAdministrator");
            cy.visitProjectAdministration(project_name);
            cy.get("[data-test=admin-nav-groups]").click();
            cy.get(`[data-test=ugroup-${project_admin_group_id}-details]`).click();

            cy.get("[data-test=project-admin-ugroups-members-list]").contains(
                "SecondProjectAdministrator",
                { timeout: 40000 },
            );
        });
    });
});

context("Project member", function () {
    it("should raise an error when user try to access to project admin page", function () {
        cy.projectMemberSession();
        //here we don't care about project, member should not be admin of any project
        cy.visit("/project/admin/?group_id=101", { failOnStatusCode: false });

        cy.contains("You don't have permission to access administration of this project.");
    });
});

context("Restricted users", function () {
    it("should not be able to create new project", function () {
        cy.log("Enable restricted users");
        cy.siteAdministratorSession();
        cy.updatePlatformVisibilityAndAllowRestricted();

        cy.log("Check restricted user CANNOT create a project");
        cy.restrictedRegularUserSession();
        cy.visit("/my/");
        cy.get("[data-test=new-button]").should("not.exist");

        cy.log("Enable project creation for restricted users");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=project-settings-link]").click();

        // need to force, tlp switch hides checkbox
        // eslint-disable-next-line cypress/no-force
        cy.get("[data-test=restricted-users-can-create-project]").check({ force: true });
        cy.get("[data-test=save-settings]").click();

        cy.log("Restricted users can now create projects");
        cy.restrictedRegularUserSession();
        cy.visit("/my/");
        cy.get("[data-test=new-button]").click();
        cy.get("[data-test=create-new-item]").click();

        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]",
        );

        cy.log("Remove the permission for restricted");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=project-settings-link]").click();

        // need to force, tlp switch hides checkbox
        cy.get("[data-test=restricted-users-can-create-project]").uncheck({ force: true });
        cy.get("[data-test=save-settings]").click();

        cy.log("Make platform accessible to anonymous again");
        cy.updatePlatformVisibilityForAnonymous();
    });
});

context("Membership management", function () {
    let project_name: string, now: number;

    before(() => {
        now = Date.now();
        project_name = "test-membership-" + now;
    });
    it("chosen users can manage members", function () {
        cy.projectAdministratorSession();

        cy.log("create project");
        createNewPublicProject(project_name);
        cy.visitProjectAdministration(project_name);
        addUser("RestrictedRegularUser");
        cy.visitProjectAdministration(project_name);

        cy.get("[data-test=admin-nav-groups]").click();

        cy.log("Add restricted group to project");
        cy.get("[data-test=project-admin-ugroups-modal]").click();
        cy.get("[data-test=ugroup_name]").type("restricted");
        cy.get("[data-test=create-user-group]").click();

        cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
        // ignore rule for select2
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-search__field").type("RestrictedRegularUser{enter}");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-result-user").click();
        cy.get('[data-test="project-admin-submit-add-member"]').click();

        // all membership pane is outside of viewport, need to force true evey action
        cy.get("[data-test=membership-management]").check({ force: true });
        cy.get("[data-test=save-delegated-permissions]").click({ force: true });

        cy.log("Restricted user can manage members");
        cy.restrictedRegularUserSession();
        cy.visit(`/projects/${project_name}/`);
        cy.visitProjectAdministrationInCurrentProject();
        cy.get("[data-test=project-administration-navigation]").should("not.contain", "Data");

        cy.get("[data-test=project-admin-members-add-user-select] + .select2-container").click();
        // ignore rule for select2
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-search__field").type("RestrictedMember{enter}");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-result-user").click();
        cy.get('[data-test="project-admin-submit-add-member"]').click();

        cy.get("[data-test=project-admin-members-list]")
            .contains("RestrictedMember (RestrictedMember)")
            .should("have.attr", "data-user-id")
            .then((user_id) => {
                cy.get(`[data-test=remove-user-${user_id}]`).click();
                cy.get(`[data-test=remove-from-member]`).click();
            });

        cy.log("Use project admin to remove restricted");
        cy.projectAdministratorSession();
        cy.visitProjectAdministration(project_name);

        let project_admin_members;
        cy.url().then((url) => {
            project_admin_members = url;

            cy.log(project_admin_members);

            cy.get("[data-test=admin-nav-groups]").click();
            cy.get("[data-test=custom-groups]").should("not.contain", "RestrictedMember");

            cy.log("Remove restricted user permission");
            cy.visitProjectAdministration(project_name);
            cy.get("[data-test=admin-nav-groups]").click();
            cy.get("[data-test=custom-groups]").contains("Details").click();
            cy.get("[data-test=membership-management]").uncheck({ force: true });
            cy.get("[data-test=save-delegated-permissions]").click({ force: true });

            cy.visit("/");

            cy.log("Restricted user can no longer access to member section");
            cy.restrictedRegularUserSession();
            cy.visit(project_admin_members, { failOnStatusCode: false });
            cy.contains("You don't have permission to access administration of this project.");
        });
    });
});

function createNewPublicProject(project_name: string): void {
    const payload = {
        shortname: project_name,
        description: "",
        label: project_name,
        is_public: true,
        categories: [],
        fields: [],
        xml_template_name: "issues",
        allow_restricted: false,
    };

    cy.postFromTuleapApi("https://tuleap/api/projects/", payload);
}

function createNewPrivateProject(project_name: string): void {
    const payload = {
        shortname: project_name,
        description: "",
        label: project_name,
        is_public: false,
        categories: [],
        fields: [],
        xml_template_name: "issues",
        allow_restricted: true,
    };

    cy.postFromTuleapApi("https://tuleap/api/projects/", payload);
}

function addUser(user_name: string): void {
    cy.visitProjectAdministrationInCurrentProject();
    cy.get("[data-test=project-admin-members-add-user-select] + .select2-container").click();
    // ignore rule for select2
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-search__field").type(`${user_name}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-user").click();
    cy.get('[data-test="project-admin-submit-add-member"]').click();
}

function addAdminUser(user_name: string): void {
    cy.visitProjectAdministrationInCurrentProject();
    cy.get("[data-test=admin-nav-groups]").click();
    cy.get(`[data-test=ugroup-${project_admin_group_id}-details]`).click();
    cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
    // ignore rule for select2
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-search__field").type(`${user_name}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-user").click();
    cy.get('[data-test="project-admin-submit-add-member"]').click();
}

function addNonMemberAdministrator(user_name: string): void {
    cy.visitProjectAdministrationInCurrentProject();
    cy.get("[data-test=admin-nav-groups]").click();
    cy.get(`[data-test=ugroup-${project_admin_group_id}-details]`).click();
    cy.get("[data-test=select-member-to-add-in-ugroup] + .select2-container").click();
    // ignore rule for select2
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-search__field").type(`${user_name}{enter}`);
    // eslint-disable-next-line cypress/require-data-selectors
    cy.get(".select2-result-user").click();
    cy.get('[data-test="project-admin-submit-add-member"]').click();
    cy.get('[data-test="project-admin-confirm-add-member"]').click();

    cy.get("[data-test=feedback]").contains("User added", {
        timeout: 40000,
    });
}

function updateUserStatus(status: string): void {
    cy.visit("/admin/");
    cy.get("[data-test=global-admin-search-user]").type("suspendedUser{enter}");
    cy.get("[data-test=user-status]").select(status);
    cy.get("[data-test=save-user]").click();
}

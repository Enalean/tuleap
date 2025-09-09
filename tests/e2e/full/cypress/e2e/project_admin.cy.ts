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

export interface ProjectServiceResponse {
    root_item: {
        id: number;
    };
}

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
        cy.removeProjectMember(project_name, "Suspended");
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
    let public_project_name: string,
        private_project_name: string,
        project_visibility: string,
        project_acces_log: string,
        service_site_admin: string,
        project_reference: string,
        now: number;

    before(function () {
        now = Date.now();
        public_project_name = "public-admin-" + now;
        private_project_name = "private-admin-" + now;
        project_visibility = `visibility-${now}`;
        project_acces_log = `access-${now}`;
        service_site_admin = `service-${now}`;
        project_reference = `reference-${now}`;
        cy.projectAdministratorSession();
        cy.createNewPublicProject(project_acces_log, "agile_alm").as("access_project_id");
        cy.createNewPublicProject(project_reference, "agile_alm").as("project_reference_id");
    });

    context("project basic administration", function () {
        before(function () {
            const TITLE_FIELD_NAME = "i_want_to";
            const REFERENCED_ARTIFACT_TITLE = "The referenced artifact";
            cy.getTrackerIdFromREST(this.project_reference_id, "story").then((tracker_id) => {
                cy.createArtifact({
                    tracker_id,
                    title_field_name: TITLE_FIELD_NAME,
                    artifact_title: REFERENCED_ARTIFACT_TITLE,
                }).as("referenced_artifact_id");
            });
        });
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
            cy.enableService(public_project_name, "svn");
        });

        it("site admin can enable services for projects templates", function () {
            cy.siteAdministratorSession();
            createNewPublicProject(service_site_admin);

            cy.log("Check site administrator can enable a service");
            cy.enableService(service_site_admin, "agiledashboard");

            cy.log("Check that services are available or not at project inheritance");
            cy.get("[data-test=new-button]").click();
            cy.get("[data-test=create-new-item]").last().click();
            cy.get("[data-test=project-registration-advanced-templates-tab]").click();
            cy.getContains("[data-test=project-registration-card-label]", "From another project")
                .closest("[data-test=project-registration-card-label]")
                .click();
            cy.get("[data-test=from-another-project]").select(service_site_admin);
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=new-project-name]").type(`duplicated-${now}`);
            cy.get("[data-test=approve_tos]").click();
            cy.get("[data-test=project-registration-next-button]").click();
            cy.get("[data-test=start-working]").click({
                timeout: 20000,
            });

            cy.log("Backlog is available");
            cy.get("[data-test=project-sidebar-tool]", { includeShadowDom: true })
                .contains("[data-test=project-sidebar-tool]", "Backlog", { includeShadowDom: true })
                .contains("Backlog");

            cy.log("SVN is not available");
            cy.get("[data-test=project-sidebar-tool]", { includeShadowDom: true })
                .contains("[data-test=project-sidebar-tool]", "SVN", { includeShadowDom: true })
                .should("not.exist");
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

        it("project visibilty can be changed", function () {
            cy.siteAdministratorSession();
            cy.visit("/admin/");
            cy.get("[data-test=project-settings-link]").click();
            cy.get("[data-test=project-visibility]").click();
            cy.get("[data-test=project-admin-can-choose-visibility]").uncheck();
            cy.get("[data-test=project-settings-submit]").click();

            cy.projectAdministratorSession();

            cy.createNewPrivateProject(project_visibility);

            cy.projectAdministratorSession();
            cy.visitProjectAdministration(project_visibility);
            cy.get("[data-test=admin-nav-details]").click();
            cy.get("[data-test=project-info-access-level]").contains("Private");
            cy.get("[data-test=project-icon]", { includeShadowDom: true })
                .eq(0)
                .should("have.class", "fa-lock");

            cy.siteAdministratorSession();
            cy.visit("/admin/");
            cy.get("[data-test=project-settings-link]").click();
            cy.get("[data-test=project-visibility]").click();
            cy.get("[data-test=project-admin-can-choose-visibility]").check();
            cy.get("[data-test=project-settings-submit]").click();

            cy.switchProjectVisibility(project_visibility, "public");
            cy.projectAdministratorSession();
            cy.visitProjectAdministration(project_visibility);
            cy.get("[data-test=admin-nav-details]").click();
            cy.get("[data-test=project-icon]", { includeShadowDom: true })
                .eq(0)
                .should("have.class", "fa-lock-open");
        });

        it("should be able to export project access log, history and xml structure", function () {
            cy.projectAdministratorSession();
            cy.log("Add a document");
            cy.getFromTuleapAPI<ProjectServiceResponse>(
                `api/projects/${this.access_project_id}/docman_service`,
            ).then((response) => {
                const root_folder_id = response.body.root_item.id;
                const embedded_payload = {
                    title: "test",
                    description: "",
                    type: "embedded",
                    embedded_properties: {
                        content: "<p>embedded</p>\n",
                    },
                    should_lock_file: false,
                };
                return cy.postFromTuleapApi(
                    `api/docman_folders/${root_folder_id}/embedded_files`,
                    embedded_payload,
                );
            });
            cy.log("Create a package");
            cy.createFRSPackage(this.access_project_id, "P1");

            cy.log("access to svn repo");
            cy.enableService(project_acces_log, "svn");
            cy.visitProjectService(project_acces_log, "SVN");
            cy.get("[data-test=create-repository-creation]").click();
            cy.get("[data-test=create-repository-field-name]").type("My_new_repo");
            cy.get("[data-test=create-repository]").click();
            cy.get("[data-test=svn-repository-access-My_new_repo]").click();

            cy.log("access to git repo");
            cy.visitProjectService(project_acces_log, "Git");
            cy.get("[data-test=create-repository-button]").click();
            cy.get("[data-test=create_repository_name]").type("Aquali");
            cy.get("[data-test=create_repository]").click();

            cy.get("[data-test=git_repo_name]").contains("Aquali", {
                timeout: 20000,
            });

            cy.log("Download access file");
            cy.visitProjectAdministration(project_acces_log);
            cy.get("[data-test=access-log]").click({ force: true });

            const download_folder = Cypress.config("downloadsFolder");
            cy.get("[data-test=export-access-log]")
                .click()
                .then(() => {
                    cy.readFile(`${download_folder}/access_logs.csv`).should("exist");
                });

            cy.log("Download project history");
            cy.visitProjectAdministration(project_acces_log);
            cy.get("[data-test=project-history]").click({ force: true });
            cy.get("[data-test=search-in-history]").click();
            // select can be outside of viewport
            cy.get("[data-test=selectbox]").select("Permissions", { force: true });
            cy.get("[data-test=filter-history-submit]").click();
            // button is outside of viewport
            cy.get("[data-test=export-history-button]")
                .click({ force: true })
                .then(() => {
                    cy.readFile(`${download_folder}/project_history.csv`).should("exist");
                });

            cy.log("Download project structure");
            cy.visitProjectAdministration(project_acces_log);
            cy.get("[data-test=project-structure-export]").click({ force: true });
            cy.get("[data-test=export-project-structure]")
                .click()
                .then(() => {
                    cy.readFile(`${download_folder}/${project_acces_log}.zip`).should("exist");
                });
        });
        describe("project reference", function () {
            before(function () {
                cy.projectAdministratorSession();
                const SOURCE_ARTIFACT_TITLE_WITH_REFERENCE = `The source artifact sla #${this.referenced_artifact_id}`;
                cy.getTrackerIdFromREST(this.project_reference_id, "task").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: "title",
                        artifact_title: SOURCE_ARTIFACT_TITLE_WITH_REFERENCE,
                    }).as("source_artifact_id");
                });
            });
            it("creates custom references", function () {
                cy.log("Create the new reference");
                cy.visit(
                    `/project/admin/reference.php?view=creation&group_id=${this.project_reference_id}`,
                );
                const reference_keyword = "sla";
                cy.get("[data-test=add-reference-keyword-input]").type(reference_keyword);
                cy.get("[data-test=add-reference-link-input]").type("/plugins/tracker/?aid=$1");
                cy.get("[data-test=add-reference-create-button]").click();

                cy.getContains("[data-test=reference-pattern-row]", reference_keyword).should(
                    "exist",
                );

                cy.visit(`/plugins/tracker/?aid=${this.source_artifact_id}`);
                cy.get("[data-test=cross-reference-link]").click();
                cy.get("[data-test=xref-in-title]").should("contain", this.referenced_artifact_id);
            });
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
        cy.updatePlatformVisibilityAndAllowRestricted();

        cy.restrictedRegularUserSession();
        cy.log("Check restricted user CANNOT create a project");
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

context("Project creation", function () {
    let project_name: string, now: number;

    before(() => {
        now = Date.now();
        project_name = "zip-import-" + now;
    });
    it("project can be created from archive template", function () {
        cy.projectAdministratorSession();

        cy.visit("/my/");
        cy.get("[data-test=new-button]").click();
        cy.get("[data-test=create-new-item]").click();

        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]",
        );
        cy.get("[data-test=project-registration-advanced-templates-tab]").click();
        cy.get("[data-test=archive-project-description]").click();

        cy.get('[data-test="archive-project-file-input"]');
        cy.get("[data-test=archive-project-file-input]").selectFile(
            "cypress/fixtures/import-project-xml.zip",
        );

        cy.get("[data-test=project-registration-next-button]").click({ force: true });
        cy.get("[data-test=new-project-name]").type(project_name);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.reloadUntilCondition(
            () => cy.visit(`/projects/${project_name}/?should-display-created-project-modal=true`),
            (number_of_attempts: number, max_attempts: number) => {
                cy.log(
                    `Check that project is available (attempt ${number_of_attempts}/${max_attempts})`,
                );

                return cy
                    .get("[data-test=dashboard-project-title-name]")
                    .then((title) => Promise.resolve(title.text().includes(project_name)));
            },
            `Timed out while checking if "${project_name}" has been created`,
        );
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

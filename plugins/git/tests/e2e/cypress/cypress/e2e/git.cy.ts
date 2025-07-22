/*
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

function waitForRepositoryCreation(): void {
    cy.get("[data-test=delete]").click();
    cy.reloadUntilCondition(
        () => {
            cy.log("Wait 10 seconds for the Git system event to be finished");
            // eslint-disable-next-line cypress/no-unnecessary-waiting -- the system event to create the git repo can be long
            cy.wait(10000);
            cy.reload();
        },
        (number_of_attempts, max_attempts) => {
            cy.log(
                `Check that the git repository can be deleted (attempt ${number_of_attempts}/${max_attempts})`,
            );
            return cy
                .get("[data-test=confirm-repository-deletion-button]")
                .invoke("attr", "disabled")
                .then((disabled) => disabled === undefined);
        },
        "Timed out while checking if the git repository can be deleted",
    );
}

describe("Git", function () {
    const now = Date.now();
    const git_project_name = `git-project-${now}`;
    before(() => {
        cy.projectAdministratorSession();
        cy.createNewPublicProject(git_project_name, "agile_alm");
        cy.getProjectId(git_project_name).as("project_id");
        cy.createNewPublicProject(`gnotif-${now}`, "agile_alm");
        cy.getProjectId(git_project_name).as("gnotif_project_id");
        cy.createNewPublicProject(`gadmin-${now}`, "agile_alm");

        cy.visitProjectService(`git-fork`, "Git");
        cy.log("Create a repository and fork it");
        cy.get("[data-test=create-repository-button]").click();
        cy.get("[data-test=create-repository-modal-form]").within(() => {
            cy.get("[data-test=create_repository_name]").type("ToBeForked");
            cy.root().submit();
        });

        cy.visitProjectService(`git-access`, "Git");
        cy.log("Create a repository for access test");
        cy.get("[data-test=create-repository-button]").click();
        cy.get("[data-test=create-repository-modal-form]").within(() => {
            cy.get("[data-test=create_repository_name]").type(`UpdateRepository${now}`);
            cy.root().submit();
        });

        cy.regularUserSession();
        cy.createNewPublicProject(`ruser-fork-${now}`, "agile_alm");
        cy.visitProjectService(`ruser-fork-${now}`, "Git");
        cy.get("[data-test=create-repository-button]").click();
        cy.get("[data-test=create-repository-modal-form]").within(() => {
            cy.get("[data-test=create_repository_name]").type("RegularUserRepo");
            cy.root().submit();
        });
    });

    context("Project administrators", function () {
        const visitGitService = (): void => {
            cy.visit(`/plugins/git/${git_project_name}/`);
        };

        it("can access to admin section", function () {
            cy.projectAdministratorSession();
            cy.visit(`/plugins/git/?group_id=${this.project_id}&action=admin`);
        });

        context("Git repository list", function () {
            it("can add widget in project and user dashboards", function () {
                cy.projectAdministratorSession();
                cy.visit(`/projects/git-dashboard/`);

                cy.log(
                    "Push some content inside repository to be sure that last commit can be displayed",
                );
                cy.cloneRepository(
                    "ProjectAdministrator",
                    "tuleap/plugins/git/git-dashboard/Aquali.git",
                    `Aquali${now}`,
                );
                cy.pushGitCommit(`Aquali${now}`);

                cy.log("Add widget to project dashboard");
                cy.visit(`projects/git-dashboard`);
                cy.get("[data-test=dashboard-add-button]").click();
                cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
                cy.get("[data-test=dashboard-add-button-submit]").click();
                cy.get("[data-test=dashboard-configuration-button]").click();
                cy.get("[data-test=dashboard-add-widget-button]").click();
                cy.get("[data-test=plugin_git_project_pushes]").click();
                cy.get("[data-test=dashboard-add-widget-button-submit]").click();
                cy.log("Check that graph is rendered as an image");
                cy.get("[data-test=plugin_git_project_pushes]")
                    .find("img")
                    .should("be.visible")
                    .and(($img) => {
                        expect($img[0].naturalWidth).to.be.greaterThan(0);
                    });

                cy.log("Add widget to user dashboard");
                cy.visit(`my`);
                cy.get("[data-test=dashboard-add-button]").click();
                cy.get("[data-test=dashboard-add-input-name]").type(`tab-${now}`);
                cy.get("[data-test=dashboard-add-button-submit]").click();
                cy.get("[data-test=dashboard-configuration-button]").click();
                cy.get("[data-test=dashboard-add-widget-button]").click();
                cy.get("[data-test=plugin_git_user_pushes]").last().click();
                cy.get("[data-test=dashboard-add-widget-button-submit]").click();
                cy.get("[data-test=widget-last-git-pushes-project]").contains("Aquali");
                // Note: force true is required for test re-playability
                // Even if I'm in a dedicated dashboard, the expand/collapse of widget is stored globally
                cy.get("[data-test=commit-direct-link]").first().click({ force: true });
                cy.url().should("include", "/plugins/git/git-dashboard/Aquali");
            });

            it("can create a new repository and delete it", function () {
                cy.projectAdministratorSession();
                visitGitService();
                const repository_name = `Aquali-${now}`;
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create_repository_name]").type(repository_name);
                cy.get("[data-test=create_repository]").click();

                cy.get("[data-test=git_repo_name]").contains(repository_name, {
                    timeout: 20000,
                });
                cy.log("shows the new repository in the list");
                visitGitService();
                cy.get("[data-test=git-repositories-page]")
                    .find("[data-test=git-repository-spinner]")
                    .should("not.exist");
                cy.get("[data-test=git-repositories-page]")
                    .find("[data-test=git-repository]")
                    .should("contain.text", repository_name);

                cy.log("delete the repository");
                cy.getContains("[data-test=git-repository]", repository_name)
                    .get("[data-test=git-repository-card-admin-link]")
                    .click();
                waitForRepositoryCreation();

                cy.log("User can checkout the repository");
                const repository_path = `tuleap/plugins/git/${git_project_name}/${repository_name}`;
                cy.cloneRepository("ProjectAdministrator", repository_path, repository_name);

                cy.log("Delete the repository");

                cy.get("[data-test=confirm-repository-deletion-button]").click();
                cy.get("[data-test=deletion-confirmation-button]").click();

                cy.get("[data-test=no-repositories]").should("be.visible");

                cy.log("User can no longer checkout the repository");
                cy.cloneRepositoryWillFail(
                    "ProjectAdministrator",
                    repository_path,
                    repository_name,
                ).then((result) => {
                    expect(result.includes("fatal")).to.be.true;
                });
            });

            it("other groups can be defined as git admin", function () {
                cy.siteAdministratorSession();
                cy.log("Create a fake gerrit server");
                cy.visit("/admin/git/");
                cy.get("[data-test=gerrit_servers_admin]").click();
                cy.get("[data-test=gerrit-server-add-button]").click();
                cy.get("[data-test=gerrit-server-url]").type("http://localhost:8080");
                cy.get("[data-test=gerrit-server-http-port]").type("8080");
                cy.get("[data-test=gerrit-server-ssh-port]").type("29418");
                cy.get("[data-test=gerrit-server-login]").type("fake-admin");
                cy.get("[data-test=gerrit-server-password]").type("example");
                cy.get("[data-test=gerrit-identity-file]").type("/fake/path/to/identify/file");
                cy.get("[data-test=gerrit-server-add-button-submit]").click();

                cy.projectAdministratorSession();
                cy.addProjectMember(`gadmin-${now}`, "projectMember");

                cy.projectAdministratorSession();
                cy.visit(`/plugins/git/gadmin-${now}/`);
                cy.get("[data-test=git-administration]").click({ force: true });
                cy.get("[data-test=git-administrators]").click();
                cy.get("[data-test=git-admins-select]").select("Project Members");
                cy.get("[data-test=update-git-administrators]").click();

                cy.log("User can paramter gerrit templates");
                cy.projectMemberSession();
                cy.visit(`/plugins/git/gadmin-${now}/`);
                cy.get("[data-test=git-administration]").click({ force: true });
                cy.get("[data-test=create-new-gerrit-template]").click();
                cy.get("[data-test=from-gerrit-config]").click();

                cy.get("[data-test=git-admin-config-data]").type("fake data");
                cy.get("[data-test=git-admin-file-name]").type("template name");

                cy.get("[data-test=save-gerrit-config]").click();

                cy.log("User can create repository");
                cy.visit(`/plugins/git/gadmin-${now}/`);
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create-repository-modal-form]").within(() => {
                    cy.get("[data-test=create_repository_name]").type("AdminDelegationRepository");
                    cy.root().submit();
                });
                cy.get("[data-test=git_repo_name]").contains("AdminDelegationRepository");

                cy.log("User can change git administration permissions");
                cy.visit(`/plugins/git/gadmin-${now}/`);
                cy.get("[data-test=git-administration]").click({ force: true });
                cy.get("[data-test=git-administrators]").click();
                cy.get("[data-test=git-admins-select]").select([]);
                cy.get("[data-test=update-git-administrators]").click();

                cy.log("Project members are no longer administrators");
                cy.visit(`/plugins/git/?group_id=${this.gnotif_project_id}&action=admin/`);
                cy.get("[data-test=feedback]").contains("You are not allowed to access this page");
            });
        });
        context("Manage repository", function () {
            it(`create and see repository in tree view`, function () {
                cy.projectAdministratorSession();
                visitGitService();
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create-repository-modal-form]").within(() => {
                    cy.get("[data-test=create_repository_name]").type("Mazda/MX5");
                    cy.root().submit();
                });
                cy.get("[data-test=git_repo_name]").contains("MX5");

                cy.log("return to the git repositories list page");
                visitGitService();

                cy.log("Be sure to display repositories by date");
                cy.get("[data-test=git-repository-list-switch-last-update]").click();
                cy.get("[data-test=git-repository-card-path]").contains("Mazda/");
                cy.get("[data-test=repository_name]").contains("MX5");

                cy.get("[data-test=git-repository-list-switch-path]").click();
                cy.get("[data-test=git-repository-list-folder-label").contains("Mazda");
                cy.get("[data-test=repository_name]").contains("MX5");
            });
            it("cannot create repository", function () {
                cy.projectAdministratorSession();
                visitGitService();
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create-repository-modal-form]").within(() => {
                    cy.get("[data-test=create_repository_name]").type("Koenigseggeor,kerj,rjr");
                    cy.root().submit();
                    cy.get("[data-test=git-repository-create-modal-body-error]").should(
                        "have.length",
                        1,
                    );
                    cy.get("[data-test=create_repository_name]").clear().type("Koenigsegg.git");
                    cy.root().submit();
                    cy.get("[data-test=git-repository-create-modal-body-error]").should(
                        "have.length",
                        1,
                    );
                });
            });
            it("changes the repository description and privacy", function () {
                cy.projectAdministratorSession();
                visitGitService();

                cy.get("[data-test=git-repository-card-description]").should("have.length", 0);
                cy.get("[data-test=repository_name]")
                    .contains(`MX5`)
                    .parent()
                    .within(() => {
                        cy.get("[data-test=git-repository-card-admin-link]").click();
                    });
                cy.get("[data-test=repository-general-settings-form]").within(() => {
                    cy.get("[data-test=repository-description]").clear().type("description");
                    cy.root().submit();
                });
                cy.get("[data-test=repository-description]").contains("description");

                visitGitService();

                cy.get("[data-test=repository_name]")
                    .contains(`MX5`)
                    .parent()
                    .siblings()
                    .within(() => {
                        cy.get("[data-test=git-repository-card-description]").should(
                            "have.length",
                            1,
                        );
                        cy.get("[data-test=git-repository-card-description]").contains(
                            "description",
                        );
                    });
            });
            it("should be able to manage notifications", function () {
                cy.projectAdministratorSession();
                cy.visitProjectAdministration(`gnotif-${now}`);
                cy.addProjectMember(`gnotif-${now}`, "ProjectMember");
                cy.projectAdministratorSession();

                cy.visitProjectAdministration(`gnotif-${now}`);
                cy.get("[data-test=admin-nav-groups]").click();

                cy.addUserGroupWithUsers("developer", ["projectMember"]);

                cy.visitProjectService(`gnotif-${now}`, "Git");
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create_repository_name]").type("my-repo");
                cy.get("[data-test=create_repository]").click();

                cy.visitProjectService(`gnotif-${now}`, "Git");
                cy.get("[data-test=git-administration]").click({ force: true });
                cy.get("[data-test=git-administrators]").click();
                cy.get("[data-test=git-admins-select]").select("Project Members");
                cy.get("[data-test=update-git-administrators]").click();

                cy.projectMemberSession();
                cy.visitProjectService(`gnotif-${now}`, "Git");
                cy.get("[data-test=git-repository-card-admin-link]").click();
                cy.get("[data-test=mail]").click();

                disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype();

                addToNotifiedPeople("private");
                cy.get("[data-test=submit-git-notifications]").click();

                cy.get("[data-test=feedback]").contains("The entered value 'private' is invalid");

                addToNotifiedPeople("devel");
                cy.get("[data-test=submit-git-notifications]").click();

                cy.get("[data-test=feedback]").contains("developer");
                cy.get("[data-test=group-icon]").should(
                    "have.class",
                    "git-notification-mail-list-group-icon",
                );

                addToNotifiedPeople("members");
                cy.get("[data-test=submit-git-notifications]").click();

                cy.get("[data-test=feedback]").contains("successfully added to notifications");
            });
        });
        context("Repository permissions", function () {
            it("When Nobody can read a repository, it cannot be cloned", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(`git-access`, "Git");
                cy.get("[data-test=git-repository-path]").contains("Access").click();
                cy.get("[data-test=git-repository-tree-table]").contains("No commits");

                const repository_path = "tuleap/plugins/git/git-access/Access";
                const repository_name = `access-${now}`;
                cy.cloneRepositoryWillFail(
                    "ProjectAdministrator",
                    repository_path,
                    repository_name,
                ).then((result) => {
                    expect(result.includes("error")).to.be.true;
                });
            });
            it("User can choose permissions of his repository", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(`git-access`, "Git");
                cy.get("[data-test=git-repository-path]")
                    .contains(`UpdateRepository${now}`)
                    .click();
                cy.get("[data-test=git-repository-settings]").click();

                cy.log("User can change the access right of the repository");
                cy.get("[data-test=perms]").click();

                cy.get("[data-test=git-repository-read-permissions]").select("Nobody");
                cy.get("[data-test=git-repository-write-permissions]").select("Nobody");
                cy.get("[data-test=git-repository-rewind-permissions]").select("Nobody");

                cy.get("[data-test=git-permissions-submit]").click();
                cy.get("[data-test=feedback]").contains("Repository informations have been saved");
            });
        });
        context("Fine grained permissions", function () {
            it("Permissions should be respected", function () {
                const repository_path = "tuleap/plugins/git/git-fined-grained/fine-grained";
                const repository_name = `fine-grained-pm-${now}`;
                cy.log("clone the repository and push commit in main branch");
                cy.cloneRepository("ProjectMember", repository_path, repository_name);
                cy.pushGitCommit(repository_name);

                cy.log("Integrators should be able to push content in devel branch");
                cy.pushAndRebaseGitCommitInBranch(repository_name, "devel");

                cy.log("Check that commit is visible in the tree view");
                cy.projectMemberSession();
                cy.visitProjectService(`git-fined-grained`, "Git");
                cy.get("[data-test=git-repository-path]").click();
                cy.get("[data-test=git-repository-tree-table]").should("contain.text", "README");

                cy.log("Switch from Integrator to Contributor");
                cy.deleteClone(repository_name);
                cy.cloneRepository("ARegularUser", repository_path, repository_name);

                cy.log("Contributors should be able to push content in dev/aregularuser branch");
                cy.pushGitCommitInBranch(repository_name, "dev/aregularuser");

                cy.log("Contributors can not push content in devel branch");
                cy.pushGitCommitInBranchWillFail(repository_name, "devel");

                cy.log("Contributors should be able to delete commit in their own branch");
                cy.deleteGitCommitInExistingBranch(repository_name, "dev/aregularuser");

                cy.log("Contributors can not delete commit in main branch");
                cy.deleteGitCommitInExistingBranchWillFail(repository_name, "main");

                cy.log("reset changes");
                const reset_changes = `cd /tmp &&
                    cd /tmp/${repository_name} &&
                    git reset --hard origin/main
                `;
                cy.exec(reset_changes);

                cy.log("Contributors should be able to push tag on main branch");
                cy.createAndPushTag(repository_name, "v1");

                cy.log("Contributor can not push tags official/v1");
                cy.deleteClone(repository_name);
                cy.cloneRepository("ARegularUser", repository_path, repository_name);
                cy.createAndPushTagWillFail(repository_name, "official/v1");

                cy.deleteClone(repository_name);
                cy.cloneRepository("ProjectMember", repository_path, repository_name);
                cy.log("Integrator should be able to push tags on official v1 branch");
                cy.createAndPushTag(repository_name, "official/v1");
            });
        });
        context("Git forks", function () {
            it("Non project members can fork repository of public project", function () {
                cy.log("You can see forks in the repository");
                cy.projectMemberSession();
                cy.visitProjectService(`git-fork`, "Git");

                cy.log("You can create a new fork");
                cy.get("[data-test=fork-repositories-link]").click({ force: true });
                cy.get("[data-test=fork-reporitory-selectbox]").select("MyRepository");
                cy.get("[data-test=fork-repository-path]").type(`MyRepository${now}`);
                cy.get("[data-test=create-fork-button]").click();
                cy.get("[data-test=create-fork-with-permissions-button]").click();
                cy.log("Check that fork is displayed in repository list");
                cy.get("[data-test=select-fork-of-user]").select("ProjectMember (ProjectMember)");
                cy.get("[data-test=git-repository-path]").should("contain", `MyRepository${now}`);

                cy.log(
                    "You can change description, permissions, mail prefix and notifications on your fork",
                );
                cy.projectAdministratorSession();
                visitGitService();
                cy.visit(`plugins/git/git-fork/u/ProjectMember/MyRepository${now}/MyRepository`);
                cy.get("[data-test=git-repository-parent]").contains("MyRepository");
                cy.get("[data-test=git-repository-settings]").click();
                cy.get("[data-test=repository-description]").type("My new fork description");
                cy.get("[data-test=save-settings-button]").click();

                cy.get("[data-test=perms]").click();
                cy.get("[data-test=git-repository-read-permissions]").select("Anonymous");
                cy.get("[data-test=git-repository-write-permissions]").select(
                    "Project administrators",
                );
                cy.get("[data-test=git-repository-rewind-permissions]").select("Nobody");
                cy.get("[data-test=git-permissions-submit]").click();

                cy.get("[data-test=mail]").click();
                cy.get("[data-test=git-mail-prefix]").type("[My new fork prefix]");

                disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype();

                addToNotifiedPeople("private");
                cy.get("[data-test=submit-git-notifications]").click();

                cy.log(
                    "Description, permissions, mail prefix and notifications on parent repository are not changed",
                );
                cy.projectAdministratorSession();
                cy.visitProjectService(`git-fork`, "Git");
                cy.get("[data-test=git-repository-card-admin-link]").first().click();
                cy.get("[data-test=repository-description]").contains("-- Default description --");

                cy.get("[data-test=perms]").click();
                cy.get("[data-test=git-repository-read-permissions]")
                    .find(":selected")
                    .contains("Registered users");

                cy.get("[data-test=git-repository-write-permissions]")
                    .find(":selected")
                    .contains("Project members");

                cy.get("[data-test=mail]").click();
                cy.get("[data-test=git-mail-prefix]").should("have.value", "[SCM]");

                cy.get("[data-test=git-no-notifications]").contains("No notifications");

                cy.log("Switch user to a non project member");
                cy.regularUserSession();
                cy.visitProjectService(`git-fork`, "Git");

                cy.log("Non project members can see the repositories");
                cy.get("[data-test=git-repository-path]").its("length").should("be.gte", 1);

                cy.log("You can see forks in the repository");
                cy.get("[data-test=git-repositories-page]")
                    .find("[data-test=git-repository]")
                    .should("contain.text", "MyRepository");
                cy.get("[data-test=select-fork-of-user]").select("ProjectMember (ProjectMember)");
                cy.get("[data-test=git-repository-path]").its("length").should("be.gte", 1);

                cy.log("You can NOT create new fork in public project");
                cy.visitProjectService(`git-fork`, "Git");
                cy.get("[data-test=fork-repositories-link]").click({ force: true });
                cy.get("[data-test=fork-reporitory-selectbox]").select("MyRepository");
                cy.get("[data-test=in-this-project]").should("have.attr", "disabled");

                cy.log(
                    "But you can create new fork from public project in an other project you are admin of",
                );
                cy.get("[data-test=fork-destination-project]").select(`ruser-fork-${now}`);
                cy.get("[data-test=create-fork-button]").click();
                cy.get("[data-test=create-fork-with-permissions-button]").click();
                cy.get("[data-test=feedback]").contains("Successfully forked");
            });

            it("When parent repository is deleted, fork is still usable", function () {
                cy.projectAdministratorSession();
                cy.visitProjectService(`git-fork`, "Git");

                cy.get("[data-test=fork-repositories-link]").click({ force: true });
                cy.get("[data-test=fork-reporitory-selectbox]").select("ToBeForked");
                cy.get('[data-test="create-fork-button"]').click();
                cy.get("[data-test=create-fork-with-permissions-button]").click();

                cy.log("Delete the parent repository");
                cy.visit("/plugins/git/git-fork/ToBeForked");
                cy.get("[data-test=git-repository-settings]").click();
                waitForRepositoryCreation();
                cy.get("[data-test=confirm-repository-deletion-button]").click();
                cy.get("[data-test=deletion-confirmation-button]").click();

                cy.log("User can still browse the fork, page does not throw a fatal error");
                cy.visit("plugins/git/git-fork/u/ProjectAdministrator/ToBeForked");

                cy.log("User can push some content in a fork");
                cy.get("[data-test=git-repository-settings]").click();
                waitForRepositoryCreation();
                const repository_path =
                    "tuleap/plugins/git/git-fork/u/ProjectAdministrator/ToBeForked";
                const repository_name = `MyRepositoryClone${now}`;
                cy.cloneRepository("ProjectAdministrator", repository_path, repository_name);
                cy.pushGitCommit(repository_name);
            });
        });

        context("Artifact actions", function () {
            describe("Git branch", function () {
                it("should create a Git branch through artifact action", () => {
                    cy.projectAdministratorSession();
                    cy.visitProjectService("git-artifact-action", "Trackers");
                    cy.get("[data-test=tracker-link]").click();
                    cy.get("[data-test=new-artifact]").click();
                    cy.get("[data-test=summary]").type("My artifact");
                    cy.get("[data-test=artifact-submit-and-stay]").click();

                    cy.get("[data-test=tracker-artifact-value-status]").contains("To be done");

                    cy.get("[data-test=tracker-artifact-actions]").click();
                    cy.get("[data-test=create-git-branch-button]").click();
                    cy.get("[data-test=create-branch-submit-button]").click();
                    cy.get("[data-test=feedback]").contains("successfully created");

                    cy.get("[data-test=current-artifact-id]")
                        .should("have.attr", "data-artifact-id")
                        .then((artifact_id) => {
                            cy.visitProjectService("git-artifact-action", "Git");
                            cy.get("[data-test=pull-requests-badge]").click();
                            cy.get("[data-test=pull-request-card]").contains(
                                `${artifact_id}-my-artifact`,
                            );
                            cy.get("[data-test=pull-request-card]").contains("main");

                            const repository_path =
                                "tuleap/plugins/git/git-artifact-action/MyRepository";
                            const repository_name = `MyRepository${now}`;
                            cy.cloneRepository(
                                "ProjectAdministrator",
                                repository_path,
                                repository_name,
                            );

                            const command = `cd /tmp/${repository_name}
                                echo aa >> README &&
                                git add README &&
                                git commit -m 'Closes art #${artifact_id}' &&
                                git -c http.sslVerify=false push`;
                            cy.exec(command);

                            cy.visit(`/plugins/tracker/?&aid=${artifact_id}`);
                            cy.get("[data-test=tracker-artifact-value-status]").contains("Done");
                        });
                });
            });
        });
    });

    function addToNotifiedPeople(user: string): void {
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-container").click();
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-input").type(`${user}{enter}`);
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-result-label").last().click();
    }

    function disableSpecificErrorThrownDueToConflictBetweenCypressAndPrototype(): void {
        cy.on("uncaught:exception", (err) => {
            // the message below is only thrown by Prototypejs, if any other js exception is thrown
            // the test will fail
            return !err.message.includes("Assignment to constant variable");
        });
    }

    context("Project members", function () {
        it("should raise an error when user try to access to plugin Git admin page", function () {
            cy.projectMemberSession();
            cy.visit(`/plugins/git/?group_id=${this.project_id}&action=admin`);

            cy.get("[data-test=git-administration-page]").should("not.exist");
        });
    });
});

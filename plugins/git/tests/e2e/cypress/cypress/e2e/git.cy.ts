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

describe("Git", function () {
    const now = Date.now();
    const git_project_name = `git-project-${now}`;
    before(() => {
        cy.projectAdministratorSession();
        cy.createNewPublicProject(git_project_name, "agile_alm");
        cy.getProjectId(git_project_name).as("project_id");
        cy.createNewPublicProject(`gnotif-${now}`, "agile_alm");
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
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create_repository_name]").type("Aquali");
                cy.get("[data-test=create_repository]").click();

                cy.get("[data-test=git_repo_name]").contains("Aquali", {
                    timeout: 20000,
                });
                cy.log("shows the new repository in the list");
                visitGitService();
                cy.get("[data-test=git-repositories-page]")
                    .find("[data-test=git-repository-spinner]")
                    .should("not.exist");
                cy.get("[data-test=git-repositories-page]")
                    .find("[data-test=git-repository]")
                    .should("contain.text", "Aquali");

                cy.log("delete the repository");
                cy.getContains("[data-test=git-repository]", "Aquali")
                    .get("[data-test=git-repository-card-admin-link]")
                    .click();
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

                cy.get("[data-test=confirm-repository-deletion-button]").click();
                cy.get("[data-test=deletion-confirmation-button]").click();

                cy.get("[data-test=no-repositories]").should("be.visible");
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
        context("Fine grained permissions", function () {
            it("Permissions should be respected", function () {
                const repository_path = "tuleap/plugins/git/git-fined-grained/fine-grained";
                const repository_name = `fine-grained-pm-${now}`;
                cy.log("clone the repository and push commit in main branch");
                cy.cloneRepository("ProjectMember", repository_path, repository_name);
                cy.pushGitCommit(repository_name);

                cy.log("Integrators should be able to push content in devel branch");
                cy.pushAndRebaseGitCommitInBranch(repository_name, "devel");

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

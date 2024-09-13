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
    let now: number;
    before(() => {
        cy.projectAdministratorSession();
        now = Date.now();
        cy.createNewPublicProject(`git-project-${now}`, "agile_alm");
        cy.getProjectId(`git-project-${now}`).as("project_id");
        cy.createNewPublicProject(`gnotif-${now}`, "agile_alm");
    });

    context("Project administrators", function () {
        const visitGitService = (): void => {
            cy.visit(`/plugins/git/git-project-${now}/`);
        };

        it("can access to admin section", function () {
            cy.projectAdministratorSession();
            cy.visit(`/plugins/git/?group_id=${this.project_id}&action=admin`);
        });

        context("Git repository list", function () {
            it("can create a new repository", function () {
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
                cy.get("[data-test=repository_name]").contains("Aquali", {
                    timeout: 20000,
                });
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

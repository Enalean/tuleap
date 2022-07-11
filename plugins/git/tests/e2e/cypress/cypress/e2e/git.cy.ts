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
    let project_id: string;
    let now: number;
    context("Project administrators", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.getProjectId("git-project").as("project_id");
            now = Date.now();
        });

        beforeEach(() => {
            cy.preserveSessionCookies();
        });

        it("can access to admin section", function () {
            project_id = this.project_id;
            cy.visit("/plugins/git/?group_id=" + project_id + "&action=admin");
        });

        context("Git repository list", function () {
            it("can create a new repository", function () {
                cy.visit("/plugins/git/git-project/");
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create_repository_name]").type(`Aquali-${now}`);
                cy.get("[data-test=create_repository]").click();

                cy.get("[data-test=git_repo_name]").contains(`Aquali-${now}`, {
                    timeout: 20000,
                });
                cy.log("shows the new repository in the list");
                cy.visit("/plugins/git/git-project/");
                cy.get("[data-test=repository_name]").contains(`Aquali-${now}`, {
                    timeout: 20000,
                });
            });
        });
        context("Manage repository", function () {
            it(`create and see repository in tree view`, function () {
                cy.visit("/plugins/git/git-project/");
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create-repository-modal-form]").within(() => {
                    cy.get("[data-test=create_repository_name]").type(`Mazda/MX5-${now}`);
                    cy.root().submit();
                });
                cy.get("[data-test=git_repo_name]").contains(`MX5-${now}`);

                cy.log("return to the git repositories list page");
                cy.visit("/plugins/git/git-project/");

                cy.log("Be sure to display repositories by date");
                cy.get("[data-test=git-repository-list-switch-last-update]").click();
                cy.get("[data-test=git-repository-card-path]").contains("Mazda/");
                cy.get("[data-test=repository_name]").contains(`MX5-${now}`);

                cy.get("[data-test=git-repository-list-switch-path]").click();
                cy.get("[data-test=git-repository-list-folder-label").contains("Mazda");
                cy.get("[data-test=repository_name]").contains(`MX5-${now}`);
            });
            it("cannot create repository", function () {
                cy.visit("/plugins/git/git-project/");
                cy.get("[data-test=create-repository-button]").click();
                cy.get("[data-test=create-repository-modal-form]").within(() => {
                    cy.get("[data-test=create_repository_name]").type("Koenigseggeor,kerj,rjr");
                    cy.root().submit();
                    cy.get("[data-test=git-repository-create-modal-body-error]").should(
                        "have.length",
                        1
                    );
                    cy.get("[data-test=create_repository_name]").clear().type("Koenigsegg.git");
                    cy.root().submit();
                    cy.get("[data-test=git-repository-create-modal-body-error]").should(
                        "have.length",
                        1
                    );
                });
            });
            it("changes the repository description and privacy", function () {
                cy.visit("/plugins/git/git-project/");

                cy.get("[data-test=git-repository-card-description]").should("have.length", 0);
                cy.get("[data-test=repository_name]")
                    .contains(`MX5-${now}`)
                    .parent()
                    .within(() => {
                        cy.get("[data-test=git-repository-card-admin-link]").click();
                    });
                cy.get("[data-test=repository-general-settings-form]").within(() => {
                    cy.get("[data-test=repository-description]")
                        .clear()
                        .type(`description - ${now}`);
                    cy.root().submit();
                });
                cy.get("[data-test=repository-description]").contains(`description - ${now}`);

                cy.visit("/plugins/git/git-project/");

                cy.get("[data-test=repository_name]")
                    .contains(`MX5-${now}`)
                    .parent()
                    .siblings()
                    .within(() => {
                        cy.get("[data-test=git-repository-card-description]").should(
                            "have.length",
                            1
                        );
                        cy.get("[data-test=git-repository-card-description]").contains(
                            `description - ${now}`
                        );
                    });
            });
        });
    });

    context("Project members", function () {
        before(() => {
            cy.clearSessionCookie();
            cy.projectMemberLogin();
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });
        it("should raise an error when user try to access to plugin Git admin page", function () {
            cy.visit("/plugins/git/?group_id=" + project_id + "&action=admin");

            cy.get("[data-test=git-administration-page]").should("not.exist");
        });
    });
});

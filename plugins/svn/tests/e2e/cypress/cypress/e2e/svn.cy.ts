/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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
 *
 */

describe("SVN", function () {
    context("Regular users", function () {
        it("do not have administrator privileges", function () {
            cy.projectMemberSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=svn-admin-groups]").should("not.exist");
        });

        it("should be able to browse existing repository", function () {
            cy.projectMemberSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=svn-repository-access-sample").click();
            cy.get("[data-test=svn-repository-view").contains("branches");
            cy.get("[data-test=svn-repository-view").contains("tags");
            cy.get("[data-test=svn-repository-view").contains("trunk");
        });

        it("should raise an error when user try to access to plugin SVN admin page", function () {
            cy.projectMemberSession();
            cy.request({
                url: "/plugins/svn/svn-project-full/admin",
                failOnStatusCode: false,
            }).then((response) => {
                expect(response.status).to.eq(403);
            });
        });
    });

    context("Project Administrators", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("svn-project-full").as("svn_project_id");
        });

        it("can access to admin section", function () {
            cy.projectAdministratorSession();
            cy.visit("/plugins/svn/svn-project-full/admin");
        });

        it("should be able to delete a repository", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=svn-repository-card]")
                .contains("sample")
                .parents("[data-test=svn-repository-card]")
                .within(() => {
                    cy.get("[data-test=svn-admin-repository-access]").click();
                });
            cy.get("[data-test=svn-repository-settings-delete]").click();

            cy.get("[data-test=svn-delete-repository]").click();
            cy.get("[data-test=confirm-svn-repo-delete]").click();

            cy.get("[data-test=feedback]").contains("will be removed in a few seconds");
        });

        it("should be able to create a new repository from the UI", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=create-repository-creation]").click();
            cy.get("[data-test=create-repository-field-name]").type("My_new_repo");
            cy.get("[data-test=create-repository]").click();

            cy.get("[data-test=feedback]").contains("My_new_repo");
        });

        it("repository created by REST API should have a correct history", function () {
            cy.projectAdministratorSession();
            const now = Date.now();
            const payload = {
                project_id: Number.parseInt(this.svn_project_id, 10),
                name: "repo01" + now,
                settings: {
                    commit_rules: {
                        is_reference_mandatory: true,
                        is_commit_message_change_allowed: false,
                    },
                    immutable_tags: {
                        paths: ["/tags1", "/tags2"],
                        whitelist: ["/tags/whitelist1", "/tags/whitelist2"],
                    },
                    layout: ["/trunk", "/tags"],
                    access_file: "[/] * = rw \r\n@members = rw\r\n[/tags]@admins = rw",
                    email_notifications: [
                        {
                            path: "/trunk",
                            emails: ["foo@example.com", "bar@example.com"],
                            users: [101],
                            user_groups: [],
                        },
                        {
                            path: "/tags",
                            emails: ["foo@example.com"],
                            users: [],
                            user_groups: [`${this.svn_project_id}_3`],
                        },
                    ],
                },
            };

            cy.postFromTuleapApi("https://tuleap/api/svn/", payload);

            cy.visit("/project/admin/?group_id=" + this.svn_project_id);
            cy.get("[data-test=project-history]").click({ force: true });
            cy.get("[data-test=project-history-results]").then((history) => {
                cy.wrap(history)
                    .should("contain", "repo01")
                    .should("contain", "mandatory_reference: true")
                    .should("contain", "commit_message_can_change: false")
                    .should("contain", "/tags1")
                    .should("contain", "/tags2")
                    .should("contain", "/tags/whitelist1")
                    .should("contain", "/tags/whitelist2")
                    .should("contain", "@members = rw")
                    .should("contain", "@admins = rw")
                    .should("contain", "/trunk")
                    .should("contain", "foo@example.com, bar@example.com")
                    .should("contain", "admin")
                    .should("contain", "project_members");
            });
        });

        it("should be able to delegate the administrator permission", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=svn-admin-groups]").click({ force: true });
            cy.get("[data-test=svn-admin-group-select]").select([
                "Project administrators",
                "Registered users",
            ]);

            cy.get("[data-test=svn-admin-save]").click();

            cy.get("[data-test=feedback]").contains("it was already granted to");

            cy.log("regular user is now administrator");
            cy.projectMemberSession();
            cy.visitProjectService("svn-project-full", "SVN");
            cy.get("[data-test=svn-admin-groups]").click({ force: true });

            // remove administrator permission for repeatability
            cy.get("[data-test=svn-admin-group-select]").select(["Project administrators"]);
            cy.get("[data-test=svn-admin-save]").click();
        });
    });
});

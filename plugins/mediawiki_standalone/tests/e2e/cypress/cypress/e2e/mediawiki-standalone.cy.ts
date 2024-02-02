/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

describe("MediaWiki Standalone", () => {
    let now: number;
    it("Manage permissions", () => {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `mw-standalone-${now}`;
        cy.createNewPublicProjectFromAnotherOne(project_name, "mediawiki-standalone-tpl").then(
            () => {
                cy.addProjectMember(project_name, "projectMember");
            },
        );

        cy.log("Regular user is not allowed to see MediaWiki content");
        cy.projectAdministratorSession();
        cy.visit(`/mediawiki_standalone/admin/${project_name}/permissions`);
        cy.get("[data-test=readers]").select("Project members");
        cy.get("[data-test=writers]").select("Project members");
        cy.get("[data-test=admins]").select([]);
        cy.get("[data-test=submit]").click();
        cy.visitProjectAdministrationInCurrentProject();
        cy.get("[data-test=project-history]").click({ force: true });
        cy.contains("Permission granted for MediaWiki readers");
        cy.contains("Permission granted for MediaWiki writers");
        cy.contains("Permission reset for MediaWiki administrators");

        cy.regularUserSession();
        cy.visitProjectService(project_name, "MediaWiki");
        cy.contains("Access denied");

        cy.log("Regular user can see MediaWiki content but cannot edit pages");
        cy.projectAdministratorSession();
        cy.visit(`/mediawiki_standalone/admin/${project_name}/permissions`);
        cy.get("[data-test=readers]").select("Developers");
        cy.get("[data-test=writers]").select("Project members");
        cy.get("[data-test=admins]").select([]);
        cy.get("[data-test=submit]").click();

        cy.regularUserSession();
        visitMediaWikiMainPage();
        // MediaWiki content doesn't have data-test attribute, we have to use element selectors
        cy.contains("h1", "Main Page");
        cy.contains("a[role=button]", "Edit").should("not.exist");
        cy.contains("a", "Protect").should("not.exist");
        cy.contains("a", "Administration").should("not.exist");

        cy.log("Regular user can see and edit pages");
        cy.projectAdministratorSession();
        cy.visit(`/mediawiki_standalone/admin/${project_name}/permissions`);
        cy.get("[data-test=readers]").select("Project members");
        cy.get("[data-test=writers]").select("Developers");
        cy.get("[data-test=admins]").select([]);
        cy.get("[data-test=submit]").click();

        cy.regularUserSession();
        visitMediaWikiMainPage();
        cy.contains("h1", "Main Page");
        cy.contains("a[role=button]", "Edit");
        cy.contains("a", "Protect").should("not.exist");
        cy.contains("a", "Administration").should("not.exist");

        cy.log("Regular user can see, edit, and protect MediaWiki pages and update permissions");
        cy.projectAdministratorSession();
        cy.visit(`/mediawiki_standalone/admin/${project_name}/permissions`);
        cy.get("[data-test=readers]").select("Project members");
        cy.get("[data-test=writers]").select("Project members");
        cy.get("[data-test=admins]").select(["Developers"]);
        cy.get("[data-test=submit]").click();

        cy.regularUserSession();
        visitMediaWikiMainPage();
        cy.contains("h1", "Main Page");
        cy.contains("a[role=button]", "Edit");
        cy.contains("a", "Protect");
        cy.contains("a", "Administration").click({ force: true });
        cy.contains("[data-test=title]", "MediaWiki administration");

        cy.log("Regular user is not anymore allowed to see MediaWiki content");
        cy.projectAdministratorSession();
        cy.visit(`/mediawiki_standalone/admin/${project_name}/permissions`);
        cy.get("[data-test=readers]").select("Project members");
        cy.get("[data-test=writers]").select("Project members");
        cy.get("[data-test=admins]").select([]);
        cy.get("[data-test=submit]").click();

        cy.regularUserSession();
        visitMediaWikiMainPage();
        cy.contains("Access denied");

        cy.log("Regular user has permission delegation");
        cy.siteAdministratorSession();
        const group_name = "group-" + project_name;
        cy.visit("/admin/");
        cy.get("[data-test=permission-delegation]").click();
        cy.get("[data-test=permission-delegation-group-creation-button]").click();
        cy.get("[data-test=siteadmin-permission-delegation-add-group-modal]").within(() => {
            cy.get("[data-test=permission-group-name]").type(group_name);
            cy.get("[data-test=permission-delegation-create-button]").click();
        });
        cy.contains("a", group_name).click();
        cy.get("[data-test=add-user-to-delegation-permission] + .select2-container").click();
        // ignore rule for select2
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-search__field").type("ARegularUser{enter}");
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get(".select2-result-user").first().click();
        cy.get("[data-test=add-user-permission-button").click();
        cy.get("[data-test=button-add-a-new-delegation]").click();
        // 3 is the id of Mediawiki delegation, see MediawikiAdminAllProjects::ID
        cy.get("[data-test=permission-3]").check();
        cy.get("[data-test=modal-add-permission-submit]").click();

        cy.regularUserSession();
        visitMediaWikiMainPage();
        cy.contains("h1", "Main Page");
        cy.contains("a[role=button]", "Edit");
        cy.contains("a", "Protect");
        cy.contains("a", "Administration").click({ force: true });
        cy.contains("[data-test=title]", "MediaWiki administration");

        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=permission-delegation]").click();
        cy.contains("a", group_name).click();
        cy.get("[data-test=permission-delegation-page]").then(($permissions) => {
            if ($permissions.find("[data-test=admin-delegation-no-user]").length === 0) {
                cy.get("[data-test=ARegularUser]").check();
                cy.get("[data-test=permission-delegation-remove-permission-button]").click();
            }
        });

        cy.regularUserSession();
        visitMediaWikiMainPage();
        cy.contains("Access denied");

        function visitMediaWikiMainPage(): void {
            // After change of permissions, there is a slight delay before MW apply them and clear current session
            // We need to refresh the page until we see "Main page" instead of "Login error"
            cy.reloadUntilCondition(
                () => {
                    cy.log("Refreshing again...");
                },
                (number_of_attempts, max_attempts) => {
                    cy.log(`Refresh Main page (attempt ${number_of_attempts}/${max_attempts})`);

                    cy.visitProjectService(project_name, "MediaWiki");

                    return cy
                        .title()
                        .then((title) => Promise.resolve(!title.startsWith("Login error")));
                },
                `Cannot load Main page`,
            );
        }
    });
});

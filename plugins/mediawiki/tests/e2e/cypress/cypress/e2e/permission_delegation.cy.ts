/*
 * Copyright (c) Enalean 2021 -  Present. All Rights Reserved.
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

describe("Permission delegation", function () {
    it("mediwiki can have a delegated permission", function () {
        cy.log("site admin can delegate delegation permission");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=permission-delegation]").click();

        cy.get("[data-test=permission-delegation-page]").then(($permissions) => {
            if (
                $permissions.find("[data-test=permission-delegation-group-creation-button]")
                    .length > 0
            ) {
                cy.get("[data-test=permission-delegation-group-creation-button]").click();
                cy.get("[data-test=permission-group-name]").type("My delegations");
                cy.get("[data-test=permission-delegation-create-button]").click();
            }
        });

        cy.get("[data-test=permission-delegation]").click();
        cy.get("[data-test=permission-delegation-page]").then(($permissions) => {
            if ($permissions.find("[data-test=delegation-has-no-permissions-set]").length > 0) {
                cy.get("[data-test=button-add-a-new-delegation]").click();
                cy.get("[data-test=permission-3]").check();
                cy.get("[data-test=modal-add-permission-submit]").click();
            }

            if ($permissions.find("[data-test=admin-delegation-no-user]").length > 0) {
                cy.get(
                    "[data-test=add-user-to-delegation-permission] + .select2-container"
                ).click();
                // ignore rule for select2
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get(".select2-search__field").type("ARegularUser{enter}");
                // eslint-disable-next-line cypress/require-data-selectors
                cy.get(".select2-result-user").first().click();
                cy.get("[data-test=add-user-permission-button").click();
            }
        });

        cy.log("user can write in public and private project he's not member of");
        cy.regularUserSession();

        cy.log("Check for private project");
        cy.visit("/plugins/mediawiki/wiki/mediawiki-private-project/");

        cy.get("[data-test=mediawiki-content]").contains("My custom content");
        cy.get("[data-test=mediawiki-content]").contains("Edit");
        cy.get("[data-test=mediawiki-content]").contains("Delete");

        cy.log("Check for public project");
        cy.visit("/plugins/mediawiki/wiki/mediawiki-public-project/");

        cy.get("[data-test=mediawiki-content]").contains("My custom content");
        cy.get("[data-test=mediawiki-content]").contains("Edit");
        cy.get("[data-test=mediawiki-content]").contains("Delete");

        cy.log("site admin can remove the delegation");
        cy.siteAdministratorSession();
        cy.visit("/admin/");
        cy.get("[data-test=permission-delegation]").click();

        cy.get("[data-test=permission-delegation-page]").then(($permissions) => {
            if ($permissions.find("[data-test=admin-delegation-no-user]").length === 0) {
                cy.get("[data-test=ARegularUser]").check();
                cy.get("[data-test=permission-delegation-remove-permission-button]").click();
            }
        });
    });
});

/*
 * Copyright (c) Enalean, 2025-present. All Rights Reserved.
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

describe("Project flag", function () {
    let now: number;
    let flag_now = Date.now();
    before(function () {
        cy.projectAdministratorSession();
        now = Date.now();
        cy.createNewPublicProject(`project-flag-${now}`, "agile_alm").as("project_id");
    });
    it("can add a new project flag and flag value as Site Admin", function () {
        flag_now = Date.now();
        cy.siteAdministratorSession();
        cy.visit(`/admin/project-creation/categories`);

        cy.log("Create a new flag category");
        cy.get("[data-test=add-project-category-button]").click();
        cy.get("[data-test=fullname]").type(`Adzam-${flag_now}`);
        cy.get("[data-test=shortname]").type(`adzam_flag_${flag_now}`);
        cy.get("[data-test=add-trove-cat-submit-button]").click();

        cy.log("Create a new value for the 'Adzam flag'");
        cy.get("[data-test=add-project-category-button]").click();
        cy.get("[data-test=fullname]").type("5 XM");
        cy.get("[data-test=shortname]").type("5_xm");
        cy.get("[data-test=trove-cat-add-modal-select-parent-category]").select(
            `Adzam-${flag_now}`,
        );
        cy.get("[data-test=add-trove-cat-submit-button]").click();

        cy.log("Edit the created flag");
        cy.get("[data-test=trove-cat-row]")
            .contains("div:visible", `Adzam-${flag_now}`)
            .parentsUntil("[data-test=trove-cat-row]")
            .parent()
            .within(() => {
                cy.get("[data-test=trove-cat-edit-button]")
                    .click()
                    .then((modal) => {
                        cy.wrap(modal)
                            .get("[data-test=trove-cats-nb-max-values-input]")
                            .clear()
                            .type("1");
                        cy.wrap(modal).get("[data-test=trove-cats-modal-is-project-flag]").check();
                        cy.wrap(modal).get("[data-test=add-trove-cat-update-button]").click();
                    });
            });
        cy.contains("div:visible", `Adzam-${flag_now}`)
            .parentsUntil("[data-test=trove-cat-row]")
            .parent()
            .within(() => {
                cy.get("[data-test=trove-cats-project-flag-checked]").should("exist");
            });

        cy.log("Connect as Project admin to add the created flag");
        cy.projectAdministratorSession();
        cy.visit(`/project/${this.project_id}/admin/categories`);
        cy.get("[data-test=project-admin-category-form]").within(function () {
            cy.getContains("[data-test=project-admin-category-select]", "Adzam-" + flag_now).select(
                `Adzam-${flag_now} :: 5 XM`,
            );
        });
        cy.get("[data-test=project-admin-category-form-submit]").click();
        cy.get("[data-test=project-sidebar-project-flags]", { includeShadowDom: true })
            .contains("5 XM")
            .should("exist");

        cy.log("Go to Tracker page which is in Flamming Parrot");
        cy.visit(`plugins/tracker/?group_id=${this.project_id}`);
        cy.get("[data-test=project-sidebar-project-flags]", { includeShadowDom: true })
            .contains("5 XM")
            .should("exist");
    });
});

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

import { getAntiCollisionNamePart } from "@tuleap/cypress-utilities-support";

describe("Project flag", function () {
    before(function () {
        cy.projectAdministratorSession();
        const project_name = "project-flag-" + getAntiCollisionNamePart();
        cy.createNewPublicProject(project_name, "agile_alm").as("project_id");
    });

    it("can add a new project flag and flag value as Site Admin", function () {
        const anti_collision = getAntiCollisionNamePart();
        cy.siteAdministratorSession();
        cy.visit(`/admin/project-creation/categories`);

        cy.log("Create a new flag category");
        cy.get("[data-test=add-project-category-button]").click();
        const flag_name = `Adzam-${anti_collision}`;
        cy.get("[data-test=fullname]").type(flag_name);
        cy.get("[data-test=shortname]").type(`adzam_flag_${anti_collision}`);
        cy.get("[data-test=add-trove-cat-submit-button]").click();

        cy.log("Create a new value for the 'Adzam flag'");
        cy.get("[data-test=add-project-category-button]").click();
        cy.get("[data-test=fullname]").type("5 XM");
        cy.get("[data-test=shortname]").type("5_xm");
        cy.get("[data-test=trove-cat-add-modal-select-parent-category]").select(flag_name);
        cy.get("[data-test=add-trove-cat-submit-button]").click();

        cy.log("Edit the created flag");
        cy.getContains("[data-test=trove-cat-row]", flag_name)
            .find("[data-test=trove-cat-edit-button]")
            .click();
        cy.get("[data-test=edit-category-modal]:visible").then((modal) => {
            cy.wrap(modal).find("[data-test=trove-cats-nb-max-values-input]").type("{selectAll}1");
            cy.wrap(modal).find("[data-test=trove-cats-modal-is-project-flag]").check();
            cy.wrap(modal).find("[data-test=add-trove-cat-update-button]").click();
        });

        cy.getContains("[data-test=trove-cat-row]", flag_name)
            .find("[data-test=trove-cats-project-flag-checked]")
            .should("exist");

        cy.log("Connect as Project admin to add the created flag");
        cy.projectAdministratorSession();
        cy.visit(`/project/${this.project_id}/admin/categories`);
        cy.get("[data-test=project-admin-category-form]").within(function () {
            cy.getContains("[data-test=project-admin-category-select]", flag_name).select(
                `${flag_name} :: 5 XM`,
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

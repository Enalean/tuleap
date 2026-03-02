/*
 * Copyright (c) Enalean, 2026 - present. All Rights Reserved.
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

describe("Document properties", () => {
    let project_unixname: string;

    before(() => {
        cy.projectAdministratorSession();
        project_unixname = "doc-properties-" + getAntiCollisionNamePart();

        cy.createNewPublicProject(project_unixname, "issues");
        cy.visit(`${"/plugins/document/" + project_unixname + "/admin-search"}`);
        cy.contains("Properties").should("have.attr", "href").as("manage_properties_url");
    });
    it("document properties", function () {
        cy.projectAdministratorSession();
        cy.visit(this.manage_properties_url);
        cy.log("Create a custom property");
        cy.get("[data-test=docman-admin-properties-create-button]").click();

        cy.get("[data-test=metadata_name]").type("my custom property");
        cy.get("[data-test=empty_allowed]").uncheck();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=admin_create_metadata]").submit();

        cy.log("property is displayed in modal");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-folder-creation-button]").click();
        });
        cy.get("[data-test=document-new-folder-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My folder");

            cy.get("[data-test=document-custom-property-text]").contains("my custom property");
        });

        cy.log("Remove the property");
        cy.visit(this.manage_properties_url);
        cy.get("[data-test=docman-admin-properties-delete-button]").click();
        cy.get("[data-test=docman-admin-properties-delete-confirm-button]").click();

        cy.get("[data-test=feedback]").contains('"my custom property" successfully deleted');
    });
});

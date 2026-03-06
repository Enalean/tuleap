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
import { openQuickLook } from "../support/helpers";

describe("Document properties", () => {
    let project_unixname: string;
    let document_status_properties_project_name: string;
    let document_obsolescence_properties_project_name: string;
    let document_all_properties_project_name: string;

    before(() => {
        project_unixname = "doc-properties-" + getAntiCollisionNamePart();
        document_status_properties_project_name = "doc-status-" + getAntiCollisionNamePart();
        document_obsolescence_properties_project_name = "doc-date-" + getAntiCollisionNamePart();
        document_all_properties_project_name = "doc-all-" + getAntiCollisionNamePart();

        cy.createNewPublicProject(project_unixname, "issues");
        cy.visit(`${"/plugins/document/" + project_unixname + "/admin-search"}`);
        cy.contains("Properties").should("have.attr", "href").as("manage_properties_url");

        cy.createNewPublicProject(document_status_properties_project_name, "issues");
        cy.visit(
            `${"/plugins/document/" + document_status_properties_project_name + "/admin-search"}`,
        );
        cy.contains("Properties").should("have.attr", "href").as("manage_status_properties_url");

        cy.createNewPublicProject(document_obsolescence_properties_project_name, "issues");
        cy.visit(
            `${"/plugins/document/" + document_obsolescence_properties_project_name + "/admin-search"}`,
        );
        cy.contains("Properties")
            .should("have.attr", "href")
            .as("manage_obsolescence_properties_url");

        cy.createNewPublicProject(document_all_properties_project_name, "issues");
        cy.visit(
            `${"/plugins/document/" + document_all_properties_project_name + "/admin-search"}`,
        );
        cy.contains("Properties").should("have.attr", "href").as("manage_all_properties_url");
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

    it("document status property", function () {
        cy.log("Enable status property");
        cy.projectAdministratorSession();
        cy.visit(this.manage_status_properties_url);
        cy.get("[data-test=available-property]").contains("Status").click();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=update-metadata-submit]").click();

        cy.log("Add a new document");
        cy.visitProjectService(document_status_properties_project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-embedded-creation-button]").click();
        });
        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My document");

            cy.get("[data-test=document-new-item-status]").select("Draft");
        });
        cy.get("[data-test=document-modal-submit-button-create-item]").click();
        cy.log("status property is displayed in preview");
        openQuickLook("My document");
        getPropertyValue("Status").then((value) => {
            expect(value).to.eq("Draft");
        });

        cy.log("Update status");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "My document")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-update-properties]").click({ force: true });
            });
        cy.get("[data-test=document-new-item-status]").select("Approved");
        cy.get("[data-test=document-modal-submit-update-properties]").click();

        cy.visitProjectService(document_status_properties_project_name, "Documents");
        openQuickLook("My document");
        cy.log("new status is displayed in preview");
        getPropertyValue("Status").then((value) => {
            expect(value).to.eq("Approved");
        });
    });

    it("document obsolescence date property", function () {
        const some_future_date = new Date();
        some_future_date.setDate(some_future_date.getDate() + 5);
        const some_future_date_iso_date = some_future_date.toISOString().split("T")[0];

        const yesterday = new Date();
        yesterday.setDate(yesterday.getDate() - 1);
        const yesterday_iso_date = yesterday.toISOString().split("T")[0];

        cy.log("Enable obsolescence date property");
        cy.projectAdministratorSession();
        cy.visit(this.manage_obsolescence_properties_url);
        cy.get("[data-test=available-property]").contains("Obsolescence Date").click();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=update-metadata-submit]").click();

        cy.log("Add a new document");
        cy.visitProjectService(document_obsolescence_properties_project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-embedded-creation-button]").click();
        });
        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My document");

            cy.get("[data-test=obsolescence-date-input]").type(some_future_date_iso_date);
        });
        cy.get("[data-test=document-modal-submit-button-create-item]").click();
        cy.log("obsolescence date property is displayed in preview");
        openQuickLook("My document");
        getPropertyValue("Validity").then((value) => {
            expect(value).to.eq(some_future_date_iso_date);
        });

        cy.log("Update date and make document obsolete");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "My document")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-update-properties]").click({ force: true });
            });
        cy.get("[data-test=obsolescence-date-input]").type(`{selectAll}${yesterday_iso_date}`);
        cy.get("[data-test=document-modal-submit-update-properties]").click();

        cy.log("document is no longer listed in preview");
        cy.visitProjectService(document_obsolescence_properties_project_name, "Documents");
        cy.get("[data-test=document-empty-state]").should("be.visible");

        cy.log("document is listed in obsolete list");
        cy.get("[data-test=breadcrumb-project-documentation]").click();
        cy.get("[data-test=breadcrumb-administrator-link]").click();
        cy.get("[data-test=admin_obsolete]").click();

        cy.log("but can be accessed through the link, a warning is displayed");
        cy.get("[data-test=obsolete-document-link]").click();
        getPropertyValue("Validity").then((value) => {
            expect(value).to.eq(yesterday_iso_date);
        });
        cy.get("[data-test=document-quicklook-obsolescence-warning]").should("be.visible");
    });

    it("document obsolescence status, obsolescence date and custom list properties", function () {
        cy.log("Configure status, obsolescence and a list property  ");
        cy.projectAdministratorSession();
        cy.visit(this.manage_all_properties_url);
        cy.get("[data-test=available-property]").contains("Status").click();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=update-metadata-submit]").click();

        cy.visit(this.manage_all_properties_url);
        cy.get("[data-test=available-property]").contains("Obsolescence Date").click();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=update-metadata-submit]").click();

        cy.visit(this.manage_all_properties_url);
        cy.get("[data-test=docman-admin-properties-create-button]").click();

        cy.get("[data-test=metadata_name]").type("my custom property");
        cy.get("[data-test=empty_allowed]").uncheck();
        cy.get("[data-test=use_it]").check();
        cy.get("[data-test=property-type]").select("List of values");
        cy.get("[data-test=admin_create_metadata]").submit();

        cy.get("[data-test=available-property]").contains("my custom property").click();
        cy.get("[data-test=create-new-list-value-button]").click();
        cy.get("[data-test=new-list-value]").type("my custom value");
        cy.get("[data-test=create-list-value-button]").click();

        cy.get("[data-test=create-new-list-value-button]").click();
        cy.get("[data-test=new-list-value]").type("my other value");
        cy.get("[data-test=create-list-value-button]").click();

        cy.log("Create a document with all metadata");
        const some_future_date = new Date();
        some_future_date.setDate(some_future_date.getDate() + 5);
        const some_future_date_iso_date = some_future_date.toISOString().split("T")[0];

        const today = new Date();
        const today_iso_date = today.toISOString().split("T")[0];

        cy.visitProjectService(document_all_properties_project_name, "Documents");
        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
            cy.get("[data-test=document-new-embedded-creation-button]").click();
        });
        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=document-new-item-title]").type("My document");

            cy.get("[data-test=obsolescence-date-input]").type(some_future_date_iso_date);
            cy.get("[data-test=document-new-item-status]").select("Approved");
            cy.get("[data-test=document-custom-list-select]").select("my custom value");
        });
        cy.get("[data-test=document-modal-submit-button-create-item]").click();

        cy.log("Properties are displayed in preview");
        openQuickLook("My document");
        getPropertyValue("Status").then((value: string) => {
            expect(value).to.eq("Approved");
        });
        getPropertyValue("Validity").then((value: string) => {
            expect(value).to.eq(some_future_date_iso_date);
        });
        getPropertyValue("my custom property").then((value: string) => {
            expect(value).to.eq("my custom value");
        });

        cy.log("Update properties");
        cy.get("[data-test=document-tree-content]")
            .contains("tr", "My document")
            .within(() => {
                cy.get("[data-test=document-drop-down-button]").click({ force: true });
                cy.get("[data-test=document-update-properties]").click({ force: true });
            });
        cy.get("[data-test=document-new-item-status]").select("Draft");
        cy.get("[data-test=document-custom-list-select]").select("my other value");
        cy.get("[data-test=obsolescence-date-input]").type(`{selectAll}${today_iso_date}`);
        cy.get("[data-test=document-modal-submit-update-properties]").click();

        cy.log("Updated properties are displayed in preview");
        cy.visitProjectService(document_all_properties_project_name, "Documents");
        openQuickLook("My document");
        getPropertyValue("Status").then((value: string) => {
            expect(value).to.eq("Draft");
        });
        getPropertyValue("Validity").then((value: string) => {
            expect(value).to.eq("Today");
        });
        getPropertyValue("my custom property").then((value: string) => {
            expect(value).to.eq("my other value");
        });
    });
});

function getPropertyValue(label: string): Cypress.Chainable<string> {
    return cy
        .contains(".tlp-property", label)
        .find("p")
        .invoke("text")
        .then((t) => t.trim());
}

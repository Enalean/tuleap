/**
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

describe("Document search", () => {
    let project_unixname: string, public_name: string, now: number;

    before(() => {
        cy.clearSessionCookie();

        cy.projectAdministratorLogin();
    });

    beforeEach(() => {
        now = Date.now();

        project_unixname = "doc-search-" + now;
        public_name = "Doc Search " + now;
        cy.preserveSessionCookies();
    });

    it("User can search", () => {
        cy.log("Create a new project");
        cy.visit("/project/new");
        cy.get(
            "[data-test=project-registration-card-label][for=project-registration-tuleap-template-issues]"
        ).click();
        cy.get("[data-test=project-registration-next-button]").click();

        cy.get("[data-test=new-project-name]").type(public_name);
        cy.get("[data-test=project-shortname-slugified-section]").click();
        cy.get("[data-test=new-project-shortname]").type("{selectall}" + project_unixname);
        cy.get("[data-test=approve_tos]").click();
        cy.get("[data-test=project-registration-next-button]").click();
        cy.get("[data-test=start-working]").click({
            timeout: 20000,
        });

        cy.log("Define custom filters/columns to display");
        cy.visitProjectService(project_unixname, "Documents");

        cy.get("[data-test=breadcrumb-project-documentation]").click();
        cy.get("[data-test=breadcrumb-administrator-link]").click();

        cy.get("[data-test=list-picker-criteria]").select(["id", "title", "description"], {
            force: true,
        });

        cy.get("[data-test=list-picker-columns]").select(["id", "title", "owner"], {
            force: true,
        });

        cy.get("[data-test=save-configuration]").click();

        cy.log("Create a document");
        cy.visitProjectService(project_unixname, "Documents");
        const title = `Lorem ipsum doloret`;

        cy.get("[data-test=document-header-actions]").within(() => {
            cy.get("[data-test=document-item-action-new-button]").click();
        });
        cy.get("[data-test=document-new-item-modal]").within(() => {
            cy.get("[data-test=empty]").click();

            cy.get("[data-test=document-new-item-title]").type(title);

            cy.get("[data-test=document-modal-submit-button-create-item]").click();
        });

        cy.userLogout();

        cy.log("Project member can find documents");
        cy.projectMemberLogin();
        cy.visitProjectService(project_unixname, "Documents");

        cy.log(`Searching for "ipsum"`);
        cy.get("[data-test=document-search-box]").clear().type(`ipsum{enter}`);
        cy.get("[data-test=search-results-table-body]").contains("tr", title);

        for (const term of ["ips*", "*psu*", "*loret"]) {
            cy.log(`Searching for "${term}"`);
            cy.get("[data-test=global-search]").clear().type(`${term}`);
            cy.get("[data-test=submit]").click();
            cy.get("[data-test=search-results-table-body]").contains("tr", title);
        }

        cy.log("Assert on filters");
        cy.get("[data-test=document-criterion-number-id]").should("exist");
        cy.get("[data-test=document-criterion-text-title]").should("exist");
        cy.get("[data-test=document-criterion-text-description]").should("exist");
        cy.get("[data-test=document-criterion-owner]").should("not.exist");

        cy.log("Assert on table columns");
        cy.get("[data-test=document-search-table-columns]").should("contain", "Title");
        cy.get("[data-test=document-search-table-columns]").should("contain", "Id");
        cy.get("[data-test=document-search-table-columns]").should("contain", "Owner");
        cy.get("[data-test=document-search-table-columns]").should("not.contain", "Description");

        const term_without_wildcards = "psu";
        cy.log(`Searching for term without wildcard "${term_without_wildcards}"`);
        cy.get("[data-test=global-search]").clear().type(`${term_without_wildcards}`);
        cy.get("[data-test=submit]").click();
        cy.get("[data-test=search-results-table-body-empty]");

        cy.log("User can use the dropdown");
        cy.get("[data-test=global-search]").clear().type(`lo*`);
        cy.get("[data-test=submit]").click();

        // Dropdown is only visible at hover, need to force visibility
        cy.get("[data-test=trigger]").first().click({ force: true });
        cy.get("[data-test=document-history]").should("be.visible");

        cy.log("User can go back to documents tree from the search page");
        cy.get("[data-test=breadcrumb-project-documentation]").click();
    });
});

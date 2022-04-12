/*
 * Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
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
 *
 */

describe("Document search", () => {
    let project_unixname: string, public_name: string, now: number;

    before(() => {
        cy.clearSessionCookie();
        now = Date.now();

        project_unixname = "doc-pattern-" + now;
        public_name = "Doc Pattern " + now;
        cy.projectAdministratorLogin();
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
    });

    it("Creates a project with document service", () => {
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
    });

    it("administrator can define a specific pattern", () => {
        cy.log("Pattern can be set");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=breadcrumb-project-documentation]").click();
        cy.get("[data-test=breadcrumb-administrator-link]").click();
        cy.get("[data-test=filename-pattern]").click({ force: true });
        cy.get("[data-test=docman-enforce-pattern]").check();

        // eslint-disable-next-line no-template-curly-in-string
        cy.get("[data-test=docman-pattern]").type("tuleap-${ID}-${TITLE}", {
            parseSpecialCharSequences: false,
        });
        cy.get("[data-test=docman-save-pattern-button]").click();

        cy.log("At file creation pattern is displayed");
        cy.visitProjectService(project_unixname, "Documents");
        cy.get("[data-test=document-item-action-new-button]").click();
        cy.get("[data-test=document-new-item-title]").type("test");
        // eslint-disable-next-line no-template-curly-in-string
        cy.get("[data-test=preview]").contains("tuleap-${ID}-test");
    });
});

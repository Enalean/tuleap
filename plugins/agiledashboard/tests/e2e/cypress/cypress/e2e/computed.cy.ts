/*
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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

describe(`Computed fields`, function () {
    let now: number;

    before(function () {
        now = Date.now();
        cy.projectAdministratorSession();
        cy.createNewPublicProject(`computed-${now}`, "issues");
        cy.enableService(`computed-${now}`, "agiledashboard");
        cy.addProjectMember(`computed-${now}`, "ProjectMember");
        cy.projectAdministratorSession();

        cy.visitProjectService(`computed-${now}`, "Backlog");
        cy.get("[data-test=link-to-ad-administration]").click({ force: true });
        cy.get("[data-test=backlog-template-import]").click();
        cy.get("[data-test=input-backlog-import-file]").selectFile(
            "cypress/fixtures/agile_dashboard_template_computed.xml",
        );
        cy.get("[data-test=import-backlog-xml-submit]").click();
    });

    it(`User can copy release with sprints`, function () {
        cy.projectAdministratorSession();
        cy.visitProjectService(`computed-${now}`, "Backlog");
        cy.log("Create a release");
        cy.get("[data-test=add-milestone]").click();
        cy.get("[data-test=string-field-input]").first().type("R0");
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.log("Add a sprint inside release");
        cy.get("[data-test=expand-collapse-milestone]").click();
        cy.get("[data-test=go-to-submilestone-planning]").click();

        cy.get("[data-test=add-milestone]").click();
        cy.get("[data-test=string-field-input]").first().type("Sprint 1");
        cy.get("[data-test=artifact-modal-save-button]").click();

        cy.visitProjectService(`computed-${now}`, "Tracker");
        cy.getContains("[data-test=tracker-link]", "Releases").click();

        cy.log("copy artifact with children with 0 as manual value");
        cy.get("[data-test=direct-link-to-artifact]").click();
        cy.get("[data-test=artifact-copy-button]").click({ force: true });
        cy.get("[data-test=edit-field-computed]").click();
        cy.get("[data-test=field-label]")
            .contains("Computed")
            .parent()
            .find("[data-test=field-default-value]")
            .type("0");

        cy.get("[data-test=copy-children-button]").click({ force: true });
        cy.get("[data-test=tracker-artifact-value-links]").contains("Sprint 1");

        cy.log("copy artifact with children with computed");
        cy.get("[data-test=artifact-copy-button]").click({ force: true });
        cy.get("[data-test=edit-field-computed]").click();
        cy.get("[data-test=switch-to-autocompute]").first().click();

        cy.get("[data-test=copy-children-button]").click({ force: true });
        cy.get("[data-test=tracker-artifact-value-links]").contains("Sprint 1");
    });

    it(`User can submit new artifacts`, function () {
        cy.projectMemberSession();
        cy.visitProjectService(`computed-${now}`, "Trackers");
        cy.getContains("[data-test=tracker-link]", "Releases").click();
        cy.get("[data-test=new-artifact]").click();
        cy.get("[data-test=release_number]").type("R0");
        cy.get("[data-test=submit-and-continue]").click();
        cy.get("[data-test=feedback]").contains("Artifact Successfully Created");

        cy.get("[data-test=release_number]").type("R0");
        cy.get("[data-test=field-label]")
            .contains("Computed")
            .parent()
            .find("[data-test=field-default-value]")
            .type("0");
        cy.get("[data-test=submit-and-continue]").click();
        cy.get("[data-test=feedback]").contains("Artifact Successfully Created");
    });
});

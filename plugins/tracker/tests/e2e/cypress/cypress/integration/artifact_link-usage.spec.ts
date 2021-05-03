/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

describe("Artifact link usage", () => {
    let project_id: string;
    describe("Site administrator", () => {
        before(() => {
            cy.clearSessionCookie();
            cy.platformAdminLogin();
        });

        it("must be able to create and delete new types of link", () => {
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=admin-tracker]").click();
            cy.get("[data-test=artifact-link-types]").click();

            cy.get("[data-test=artifact-link-add-type]").click();

            cy.get("[data-test=artlink-shortname]").type("test");
            cy.get("[data-test=artlink-forward-label]").type("Tested in");
            cy.get("[data-test=artlink-reverse-label]").type("Tested");

            cy.get("[data-test=artlink-add-button]").click();
            cy.get("[data-test=feedback]").contains("The type test has been successfully created.");

            cy.get("[data-test=artlink-delete-test]").click();
            cy.get("[data-test=confirm-delete-test-button]").click();

            cy.get("[data-test=feedback]").contains("The type has been successfully deleted.");
        });
    });

    function toggleArtifactLinkUsage(project_id: string): void {
        cy.visit("/plugins/tracker/global-admin/" + project_id);
        cy.get("[data-test=artifact-links]").click();
        // tlp switch made input not visible, need to force the uncheck action
        cy.get("[data-test=toggle-fixed_in-link]").uncheck({ force: true });
    }

    describe("Tracker administration", function () {
        before(function () {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.getProjectId("tracker-artifact").as("project_id");
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });

        it("can enable/disable artifact links", function () {
            project_id = this.project_id;
            toggleArtifactLinkUsage(project_id);

            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=tracker-link-artifact_link]").click();
            cy.get("[data-test=direct-link-to-artifact]").first().click();
            cy.get("[data-test=edit-field-links]").click();

            cy.get("[data-test=artifact-link-nature-selector]").should("not.contain", "fixed_in");

            toggleArtifactLinkUsage(project_id);
        });
    });
});

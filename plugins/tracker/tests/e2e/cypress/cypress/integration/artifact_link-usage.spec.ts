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

function submitArtifactAndStay(): void {
    cy.get("[data-test=artifact-submit-options]").click();
    cy.get("[data-test=artifact-submit-and-stay]").click();
}

function createArtifact(title: string): void {
    cy.visitProjectService("tracker-artifact", "Trackers");
    cy.get("[data-test=tracker-link-artifact_link]").click();
    cy.get("[data-test=create-new]").click();
    cy.get("[data-test=create-new-item]").first().click();
    cy.get("[data-test=title]").type(title);
    submitArtifactAndStay();

    cy.get("[data-test=feedback]").contains("Artifact Successfully");
}

describe("Artifact link usage", () => {
    let project_id: string;
    let fixed_by_artifact: string;
    let fixed_in_artifact: string;
    let parent_artifact: string;
    let child_artifact: string;
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

    function disableArtifactLinkUsage(project_id: string): void {
        cy.visit("/plugins/tracker/global-admin/" + project_id);
        cy.get("[data-test=artifact-links]").click();
        // tlp switch made input not visible, need to force the uncheck action
        cy.get("[data-test=toggle-fixed_in-link]").uncheck({ force: true });
        cy.get("[data-test=artifact-link-form-fixed_in]").submit();
    }

    function enableArtifactLinkUsage(project_id: string): void {
        cy.visit("/plugins/tracker/global-admin/" + project_id);
        cy.get("[data-test=artifact-links]").click();
        // tlp switch made input not visible, need to force the uncheck action
        cy.get("[data-test=toggle-fixed_in-link]").check({ force: true });
        cy.get("[data-test=artifact-link-form-fixed_in]").submit();
    }

    describe("Tracker administration", function () {
        before(function () {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.getProjectId("tracker-artifact").as("project_id");

            createArtifact("Fixed By");
            cy.get("[data-test=current-artifact-id]").should(($input) => {
                fixed_by_artifact = String($input.val());
            });

            createArtifact("Fixed In");
            cy.get("[data-test=current-artifact-id]").should(($input) => {
                fixed_in_artifact = String($input.val());
            });

            createArtifact("Parent of");
            cy.get("[data-test=current-artifact-id]").should(($input) => {
                parent_artifact = String($input.val());
            });

            createArtifact("Child of");
            cy.get("[data-test=current-artifact-id]").should(($input) => {
                child_artifact = String($input.val());
            });
        });

        beforeEach(function () {
            cy.preserveSessionCookies();
        });

        it("can enable/disable artifact links", function () {
            project_id = this.project_id;
            disableArtifactLinkUsage(project_id);

            cy.log("Fixed in nature is not available when nature is disabled");
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=tracker-link-artifact_link]").click();
            cy.get("[data-test=direct-link-to-artifact]").first().click();
            cy.get("[data-test=edit-field-links]").click();

            cy.get("[data-test=artifact-link-nature-selector]").should("not.contain", "Fixed in");

            enableArtifactLinkUsage(project_id);
            cy.log("Fixed in nature can be used");
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visit("/plugins/tracker/?&aid=" + fixed_in_artifact);
            cy.get("[data-test=edit-field-links]").click();
            cy.get("[data-test=artifact-link-submit]").type(fixed_by_artifact);
            cy.get("[data-test=artifact-link-nature-selector]").first().select("Fixed in");
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=artifact-link-section]").contains(fixed_by_artifact);

            cy.log("Reverse link display fixed in nature");
            cy.visit("/plugins/tracker/?&aid=" + fixed_by_artifact);
            cy.get("[data-test=display-reverse-links]").click();
            cy.get("[data-test=reverse-link-section").contains(fixed_in_artifact);
        });

        it("can use _is_child nature", function () {
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visit("/plugins/tracker/?&aid=" + child_artifact);
            cy.get("[data-test=edit-field-links]").click();
            cy.get("[data-test=artifact-link-submit]").type(parent_artifact);
            cy.get("[data-test=artifact-link-nature-selector]").first().select("Child");
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=artifact-link-section]").contains(parent_artifact);

            cy.visit("https://tuleap/plugins/tracker/?&aid=" + parent_artifact);
            cy.get("[data-test=display-reverse-links]").click();
            cy.get("[data-test=reverse-link-section").contains(child_artifact);
        });
    });
});

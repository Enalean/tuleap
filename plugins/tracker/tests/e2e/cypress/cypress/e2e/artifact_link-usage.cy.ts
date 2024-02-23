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

const TITLE_FIELD_NAME = "title";
function submitArtifactAndStay(): void {
    cy.get("[data-test=artifact-submit-and-stay]").click();
}

describe("Artifact link usage", () => {
    describe("Site administrator", () => {
        it("must be able to create and delete new types of link", () => {
            cy.siteAdministratorSession();
            cy.visit("/admin/");
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
    }

    function enableArtifactLinkUsage(project_id: string): void {
        cy.visit("/plugins/tracker/global-admin/" + project_id);
        cy.get("[data-test=artifact-links]").click();
        // tlp switch made input not visible, need to force the uncheck action
        cy.get("[data-test=toggle-fixed_in-link]").check({ force: true });
    }

    describe("Tracker administration", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("tracker-artifact")
                .as("project_id")
                .then((project_id) => {
                    cy.getTrackerIdFromREST(project_id, "artifact_link").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id: tracker_id,
                            artifact_title: "Fixed By",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_by_artifact");

                        cy.createArtifact({
                            tracker_id: tracker_id,
                            artifact_title: "Fixed In",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_in_artifact");

                        cy.createArtifact({
                            tracker_id: tracker_id,
                            artifact_title: "Parent of",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("parent_of_artifact");

                        cy.createArtifact({
                            tracker_id: tracker_id,
                            artifact_title: "Child of",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("child_of_artifact");
                    });
                });
        });

        it("can enable/disable artifact links", function () {
            cy.projectAdministratorSession();
            disableArtifactLinkUsage(this.project_id);

            cy.log("Fixed in nature is not available when nature is disabled");
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.get("[data-test=tracker-link-artifact_link]").click();
            cy.get("[data-test=direct-link-to-artifact]").first().click();
            cy.get("[data-test=edit-field-links]").click();

            cy.get("[data-test=artifact-link-type-selector]").should("not.contain", "Fixed in");

            enableArtifactLinkUsage(this.project_id);
            cy.log("Fixed in nature can be used");
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_in_artifact);
            cy.get("[data-test=edit-field-links]").click();
            cy.get("[data-test=artifact-link-submit]").type(this.fixed_by_artifact);
            cy.get("[data-test=artifact-link-type-selector]").first().select("Fixed in");
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=artifact-link-section]").contains(this.fixed_by_artifact);

            cy.log("Reverse link display fixed in nature");
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_by_artifact);
            cy.get("[data-test=reverse-link-section").contains(this.fixed_in_artifact);
        });

        it("can use _is_child nature", function () {
            cy.projectAdministratorSession();
            cy.visitProjectService("tracker-artifact", "Trackers");
            cy.visit("/plugins/tracker/?&aid=" + this.child_of_artifact);
            cy.get("[data-test=edit-field-links]").click();
            cy.get("[data-test=artifact-link-submit]").type(this.parent_of_artifact);
            cy.get("[data-test=artifact-link-type-selector]").first().select("Child");
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=artifact-link-section]").contains(this.parent_of_artifact);

            cy.visit("https://tuleap/plugins/tracker/?&aid=" + this.parent_of_artifact);
            cy.get("[data-test=reverse-link-section").contains(this.child_of_artifact);
        });
    });

    describe("Project administration", function () {
        it("can create a new `Parent` link between two artifact", function () {
            cy.projectMemberSession();
            cy.visitProjectService("hierarchy", "Trackers");

            cy.get("[data-test=tracker-link-bugs]").click();
            cy.get("[data-test=direct-link-to-artifact]").click();
            cy.get("[data-test=current-artifact-id]")
                .should("have.attr", "data-artifact-id")
                .then((artifact_id) => {
                    cy.projectMemberSession();
                    cy.visitProjectService("hierarchy", "Trackers");
                    cy.get("[data-test=tracker-link-issue]").click();
                    cy.get("[data-test=direct-link-to-artifact]").click();

                    cy.get("[data-test=edit-field-linked_issues]").click();
                    cy.get("[data-test=artifact-link-submit]").type(`${artifact_id}`);
                    cy.get("[data-test=artifact-link-type-selector]").first().select("Parent");
                    submitArtifactAndStay();

                    cy.get("[data-test=tracker-hierarchy]").contains(`${artifact_id}`);
                    cy.get("[data-test=tracker-artifact-title]").contains("issue");
                });
        });
    });
});

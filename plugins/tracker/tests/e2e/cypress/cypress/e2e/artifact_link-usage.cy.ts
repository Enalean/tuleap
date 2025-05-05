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
                        cy.wrap(tracker_id).as("tracker_id");
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Fixed By",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_by_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Fixed In",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_in_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Parent of",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("parent_of_artifact");

                        cy.createArtifact({
                            tracker_id,
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
            cy.visit(`/plugins/tracker/?tracker=${this.tracker_id}`);
            cy.get("[data-test=direct-link-to-artifact]").first().click();

            cy.get("[data-test=link-type-select]").should("not.contain", "Fixed in");

            enableArtifactLinkUsage(this.project_id);
            cy.log("Fixed in nature can be used");
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_in_artifact);
            cy.get("[data-test=link-type-select]").first().select("Fixed in");
            cy.get("[data-test=link-field-add-link-input]").click();
            cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                .focus()
                .type(this.fixed_by_artifact);
            cy.get("[data-test=lazybox-item]").first().click();
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=tracker-artifact-value-links]").contains(this.fixed_by_artifact);

            cy.log("Reverse link display fixed in nature");
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_by_artifact);
            cy.get("[data-test=tracker-artifact-value-links").contains(this.fixed_in_artifact);
        });

        it("can use _is_child nature", function () {
            cy.projectAdministratorSession();
            cy.visit("/plugins/tracker/?&aid=" + this.child_of_artifact);
            cy.get("[data-test=link-type-select]").select("is Child of");
            cy.get("[data-test=link-field-add-link-input]").click();
            cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                .focus()
                .type(this.parent_of_artifact);
            cy.get("[data-test=lazybox-item]").first().click();
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");

            cy.get("[data-test=tracker-artifact-value-links]").contains(this.parent_of_artifact);

            cy.visit("https://tuleap/plugins/tracker/?&aid=" + this.parent_of_artifact);
            cy.get("[data-test=tracker-artifact-value-links").contains(this.child_of_artifact);
        });
    });

    describe("Project administration", function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("hierarchy")
                .as("project_id")
                .then((project_id) => {
                    cy.getTrackerIdFromREST(project_id, "issue").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Parent",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("parent_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Update",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("update_parent");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Create parent",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("create_parent");
                    });
                });
        });

        it("can create a new `Parent` link between two artifact", function () {
            cy.projectMemberSession();

            cy.visit("/plugins/tracker/?&aid=" + this.create_parent);

            cy.get("[data-test=link-type-select]").first().select("is Child of");
            cy.get("[data-test=link-field-add-link-input]").click();
            cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                .focus()
                .type(this.parent_artifact);
            cy.get("[data-test=lazybox-item]").first().click();

            submitArtifactAndStay();

            cy.get("[data-test=tracker-hierarchy]").contains(`${this.parent_artifact}`);
            cy.get("[data-test=tracker-artifact-title]").contains("issue");
        });

        it("can update a `Parent` link between two existing artifact", function () {
            cy.projectMemberSession();
            cy.intercept("api/v1/artifacts/*").as("getArtifact");

            cy.visit("/plugins/tracker/?&aid=" + this.update_parent);

            cy.get("[data-test=link-field-add-link-input]").click();
            cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                .focus()
                .type(this.parent_artifact);
            cy.wait("@getArtifact");
            cy.get("[data-test=lazybox-item]").first().click();
            submitArtifactAndStay();

            cy.get("[data-test=artifact-link-field]").within(() => {
                cy.get("[data-test=link-type-select]")
                    .first()
                    .select("is Child of", { force: true });
            });

            submitArtifactAndStay();

            cy.get("[data-test=tracker-hierarchy]").contains("Parent");
        });

        it("can create a new artifact by editing artifact links", function () {
            const new_artifact_title = "New artifact";

            cy.projectMemberSession();

            cy.visit("/plugins/tracker/?&aid=" + this.update_parent);

            cy.get("[data-test=link-field-add-link-input]").click();
            cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                .focus()
                .type(new_artifact_title);
            cy.get("[data-test=new-item-button]").click();
            cy.get("[data-test=artifact-creator-submit]").click();
            submitArtifactAndStay();

            cy.get("[data-test=artifact-link-field]").contains(new_artifact_title);
        });
    });
});

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

function toggleLinkTypeUsage(project_id: string): void {
    cy.visit("/plugins/tracker/global-admin/" + project_id);
    cy.get("[data-test=artifact-links]").click();
    cy.getContains("[data-test=artifact-link-type-line]", "fixed_in")
        .find("[data-test=toggle-link-type]")
        .click();
}

function waitForArtifactLinksToBeLoaded(): void {
    cy.get("[data-test=artifact-link-field]")
        .find("[data-test=link-field-table-skeleton]")
        .should("not.exist");
}

describe(`Artifact links usage`, () => {
    describe(`Site administrator`, () => {
        it(`can create and delete types of links`, () => {
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

            cy.getContains("[data-test=link-type-line]", "test")
                .find("[data-test=link-type-delete-button]")
                .click();
            cy.get("[data-test=link-type-delete-modal].tlp-modal-shown")
                .find("[data-test=confirm-delete-link-type-button]")
                .click();
            cy.get("[data-test=feedback]").contains("The type has been successfully deleted.");
        });
    });

    describe(`Tracker administrator`, function () {
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("tracker-artifact")
                .as("project_id")
                .then((project_id) => {
                    cy.getTrackerIdFromREST(project_id, "artifact_link").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Fixed By Artifact",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_by_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Fixed In Artifact",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("fixed_in_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Parent of Artifact",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("parent_of_artifact");

                        cy.createArtifact({
                            tracker_id,
                            artifact_title: "Child of Artifact",
                            title_field_name: TITLE_FIELD_NAME,
                        }).as("child_of_artifact");
                    });
                });
        });

        it(`can enable/disable artifact link types`, function () {
            cy.projectAdministratorSession();
            toggleLinkTypeUsage(this.project_id);

            cy.log(`"Fixed in" type is not available when it is disabled`);
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_in_artifact);
            waitForArtifactLinksToBeLoaded();
            cy.get("[data-test=link-type-select]").should("not.contain", "Fixed in");

            toggleLinkTypeUsage(this.project_id);

            cy.log(`"Fixed in" type can be used when enabled again`);
            cy.projectMemberSession();
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_in_artifact);
            waitForArtifactLinksToBeLoaded();
            cy.get("[data-test=artifact-link-field]").within(() => {
                cy.get("[data-test=link-field-add-link-section]")
                    .find("[data-test=link-type-select]")
                    .select("Fixed in");
                cy.searchItemInLazyboxDropdown(
                    this.fixed_by_artifact,
                    this.fixed_by_artifact,
                ).click();
            });
            submitArtifactAndStay();

            cy.get("[data-test=feedback]").contains("Successfully Updated");
            cy.get("[data-test=tracker-artifact-value-links]").contains(this.fixed_by_artifact);

            cy.log(`Reverse link displays "Fixed in" type`);
            cy.visit("/plugins/tracker/?&aid=" + this.fixed_by_artifact);
            cy.get("[data-test=tracker-artifact-value-links").contains(this.fixed_in_artifact);
        });

        it(`can use _is_child type`, function () {
            cy.projectMemberSession();
            cy.visit("/plugins/tracker/?&aid=" + this.child_of_artifact);

            waitForArtifactLinksToBeLoaded();
            cy.get("[data-test=artifact-link-field]").within(() => {
                cy.get("[data-test=link-field-add-link-section]")
                    .find("[data-test=link-type-select]")
                    .select("is Child of");
                cy.searchItemInLazyboxDropdown(
                    this.parent_of_artifact,
                    this.parent_of_artifact,
                ).click();
            });
            submitArtifactAndStay();
            cy.get("[data-test=feedback]").contains("Successfully Updated");
            cy.log(`Parent is shown in the title`);
            cy.get("[data-test=tracker-hierarchy]").contains(this.parent_of_artifact);

            cy.visit("https://tuleap/plugins/tracker/?&aid=" + this.parent_of_artifact);
            cy.get("[data-test=tracker-artifact-value-links").contains(this.child_of_artifact);
        });

        it(`can create a new artifact by editing artifact links`, function () {
            const new_artifact_title = "New artifact";

            cy.projectMemberSession();

            cy.visit("/plugins/tracker/?&aid=" + this.child_of_artifact);

            cy.get("[data-test=artifact-link-field]").within(() => {
                cy.get("[data-test=lazybox]").click();
                cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
                    .focus()
                    .type(new_artifact_title);
                cy.get("[data-test=new-item-button]").click();
                cy.get("[data-test=artifact-creator-submit]").click();
            });
            submitArtifactAndStay();

            cy.get("[data-test=artifact-link-field]").contains(new_artifact_title);
        });
    });

    describe(`Legacy artifact links field`, function () {
        const PARENT_ARTIFACT_TITLE = "Parent";
        before(function () {
            cy.projectAdministratorSession();
            cy.getProjectId("hierarchy")
                .as("project_id")
                .then((project_id) => {
                    cy.getTrackerIdFromREST(project_id, "issue").then((tracker_id) => {
                        cy.createArtifact({
                            tracker_id,
                            artifact_title: PARENT_ARTIFACT_TITLE,
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

                        cy.log(`Use the legacy artifact links field`);
                        cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=admin-formElements`);
                        // eslint-disable-next-line cypress/no-force -- edit button is shown only on CSS :hover
                        cy.getContains("[data-test=tracker-admin-field]", "Linked Issues")
                            .find("[data-test=edit-field]")
                            .click({ force: true });

                        cy.get(
                            "[data-test=checkbox-specific-properties] > [data-test=input-type-checkbox]",
                        ).uncheck();
                        cy.get("[data-test=formElement-submit]").click();
                    });
                });
        });

        it(`can create a new Parent link between two artifact`, function () {
            cy.projectMemberSession();
            cy.visit("/plugins/tracker/?&aid=" + this.create_parent);
            cy.getContains("[data-test-artifact-form-element]", "Linked Issues")
                .find("[data-test-edit-field]")
                .click();
            cy.get("[data-test=artifact-link-submit]").type(this.parent_artifact);
            cy.get("[data-test=artifact-link-type-selector]").first().select("Parent");
            submitArtifactAndStay();

            cy.get("[data-test=tracker-hierarchy]").contains(this.parent_artifact);
            cy.get("[data-test=tracker-hierarchy]").contains(PARENT_ARTIFACT_TITLE);
        });

        it(`can update a Parent link between two existing artifact`, function () {
            cy.projectMemberSession();
            cy.intercept("*?func=artifactlink-renderer-async*").as("loadLinksPost");

            cy.visit("/plugins/tracker/?&aid=" + this.update_parent);
            cy.getContains("[data-test-artifact-form-element]", "Linked Issues")
                .find("[data-test-edit-field]")
                .click();
            cy.get("[data-test=artifact-link-submit]").type(this.parent_artifact);
            submitArtifactAndStay();

            cy.getContains("[data-test-artifact-form-element]", "Linked Issues")
                .find("[data-test-edit-field]")
                .click();
            cy.wait("@loadLinksPost", { timeout: 6000 });
            cy.get("[data-test=artifact-report-table] [data-test=artifact-link-type-selector]")
                .last()
                .select("Parent", { force: true });
            submitArtifactAndStay();

            cy.get("[data-test=tracker-hierarchy]").contains(this.parent_artifact);
            cy.get("[data-test=tracker-hierarchy]").contains(PARENT_ARTIFACT_TITLE);
        });
    });
});

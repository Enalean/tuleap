/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

describe(`Link field of Artifact Modal`, function () {
    let now: number;

    const ARTIFACT_TITLE = "Artifact links types",
        TITLE_FIELD_NAME = "i_want_to",
        CHILD_TITLE = "Child Artifact",
        PARENT_TITLE = "Parent Artifact",
        LINKS_FIELD_NAME = "Links",
        HISTORY_ARTIFACT_TITLE = "History Artifact",
        TASKS_TRACKER_NAME = "Tasks";

    before(function () {
        now = Date.now();
        cy.log("Create a new project for repeatability");
        cy.projectAdministratorSession();
        const project_name = "artifact-lnx-mdl-" + now;
        cy.createNewPublicProject(project_name, "scrum");
        cy.getProjectId(project_name)
            .as("project_id")
            .then((project_id) => {
                cy.visit(`/projects/${project_name}`);
                cy.addProjectMember("projectMember");
                cy.getTrackerIdFromREST(project_id, "story").then((tracker_id) => {
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: TITLE_FIELD_NAME,
                        artifact_title: ARTIFACT_TITLE,
                    }).then((artifact_id) => {
                        addToTopBacklog(project_id, artifact_id);
                    });
                    cy.log("Create more artifacts to link to");
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: TITLE_FIELD_NAME,
                        artifact_title: CHILD_TITLE,
                    }).as("child_artifact_id");
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: TITLE_FIELD_NAME,
                        artifact_title: PARENT_TITLE,
                    }).as("parent_artifact_id");
                    cy.createArtifact({
                        tracker_id,
                        title_field_name: TITLE_FIELD_NAME,
                        artifact_title: HISTORY_ARTIFACT_TITLE,
                    }).as("history_artifact_id");
                });
            });
    });

    it(`can select artifacts from user's history`, function () {
        cy.projectMemberSession();
        cy.log(`Visit History Artifact to ensure it is in history`);
        cy.visit(`/plugins/tracker/?aid=${this.history_artifact_id}`);

        cy.log("Open the Artifact modal");
        cy.visit(
            `/plugins/agiledashboard/?action=show-top&group_id=${this.project_id}&pane=topplanning-v2`
        );
        cy.getContains("[data-test=backlog-item]", ARTIFACT_TITLE).within(() => {
            cy.get("[data-test=backlog-item-details-link]").click();
            cy.get("[data-test=edit-item]").click();
        });
        cy.get("[data-test=artifact-modal-form]").within(() => {
            cy.getContains("[data-test=artifact-link-field]", LINKS_FIELD_NAME).within(() => {
                cy.get("[data-test=link-field-add-link-section]")
                    .find("[data-test=link-type-select]")
                    .select("is Linked to");
            });
            cy.searchItemInLazyboxDropdown(HISTORY_ARTIFACT_TITLE, HISTORY_ARTIFACT_TITLE).should(
                "contain",
                HISTORY_ARTIFACT_TITLE
            );
        });
        cy.log("Close the modal");
        cy.get("[data-test=artifact-modal-cancel-button]").click();
    });

    it(`can change the type of links`, function () {
        cy.projectMemberSession();
        cy.log("Open the Artifact modal");
        cy.visit(
            `/plugins/agiledashboard/?action=show-top&group_id=${this.project_id}&pane=topplanning-v2`
        );
        cy.getContains("[data-test=backlog-item]", ARTIFACT_TITLE).within(() => {
            cy.get("[data-test=backlog-item-details-link]").click();
            cy.get("[data-test=edit-item]").click();
        });
        cy.get("[data-test=artifact-modal-form]").within(() => {
            cy.getContains("[data-test=artifact-link-field]", LINKS_FIELD_NAME).within(() => {
                cy.searchItemInLazyboxDropdown(String(this.child_artifact_id), CHILD_TITLE).click();
                cy.getContains("[data-test=link-row]", CHILD_TITLE)
                    .find("[data-test=link-type-select]")
                    .select("is Parent of");
                cy.searchItemInLazyboxDropdown(
                    String(this.parent_artifact_id),
                    PARENT_TITLE
                ).click();
                cy.getContains("[data-test=link-row]", PARENT_TITLE)
                    .find("[data-test=link-type-select]")
                    .select("is Child of");
            });
            cy.get("[data-test=artifact-modal-save-button]").click();
        });
        cy.get("[data-test=artifact-modal-loading]").should("not.exist");
        cy.getContains("[data-test=backlog-item]", ARTIFACT_TITLE).should("contain", PARENT_TITLE);
    });

    it(`can create a new artifact and add it to the links`, function () {
        const NEW_LINK_TITLE = "Nocturnal Beam";
        cy.projectMemberSession();
        cy.log("Open the Artifact modal");
        cy.visit(
            `/plugins/agiledashboard/?action=show-top&group_id=${this.project_id}&pane=topplanning-v2`
        );
        cy.getContains("[data-test=backlog-item]", ARTIFACT_TITLE).within(() => {
            cy.get("[data-test=backlog-item-details-link]").click();
            cy.get("[data-test=edit-item]").click();
        });
        cy.get("[data-test=artifact-modal-form]").within(() => {
            cy.getContains("[data-test=artifact-link-field]", LINKS_FIELD_NAME).within(() => {
                cy.get("[data-test=lazybox]").click();
                cy.get("[data-test=new-item-button]").click();
                cy.get("[data-test=artifact-creator-title]").type(NEW_LINK_TITLE);
                cy.get("[data-test=tracker-picker-form-element]").within(() => {
                    cy.searchItemInListPickerDropdown(TASKS_TRACKER_NAME).click();
                });
                cy.get("[data-test=artifact-creator-submit]").click();
                cy.get("[data-test=link-row]").should("contain", NEW_LINK_TITLE);
            });
            cy.get("[data-test=artifact-modal-save-button]").click();
        });
    });
});

function addToTopBacklog(project_id: number, artifact_id: number): void {
    cy.patchFromTuleapAPI(`/api/v1/projects/${project_id}/backlog`, {
        add: [{ id: artifact_id }],
    });
}

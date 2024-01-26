/**
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

describe("Tuleap Functions for Trackers", () => {
    let now: number;
    it("Show milestones below backlog", () => {
        cy.projectAdministratorSession();
        now = Date.now();
        const project_name = `tracker-func-${now}`;
        cy.createNewPublicProjectFromAnotherOne(project_name, "tracker-functions-template").then(
            () => {
                cy.addProjectMember(project_name, "projectMember");
            },
        );

        cy.log("Upload Function");
        cy.projectAdministratorSession();
        cy.visitProjectService(project_name, "Trackers");
        cy.get("[data-test=tracker-link-functions]").click();
        cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
        cy.get("[data-test=tracker-cce]").click({ force: true });
        cy.get("[data-test=tracker-functions-admin-upload-modal-trigger]").click();
        cy.get("[data-test=tracker-functions-upload-file]").selectFile(
            "cypress/fixtures/post-action-add-comment.wasm",
        );
        cy.get("[data-test=tracker-functions-upload-submit]").click();
        cy.get("[data-test=tracker-functions-uploaded]");

        cy.log("Create artifact");
        cy.projectMemberSession();
        cy.getProjectId(project_name)
            .as("project_id")
            .then((project_id) => {
                return cy.getTrackerIdFromREST(project_id, "functions");
            })
            .then((tracker_id) => {
                return cy.createArtifactWithFields({
                    tracker_id,
                    fields: [
                        {
                            shortname: "field_a",
                            value: "3",
                        },
                        {
                            shortname: "field_b",
                            value: "4",
                        },
                    ],
                });
            })
            .then((artifact_id) => {
                cy.reloadUntilCondition(
                    () => {
                        cy.log("Waiting again...");
                    },
                    (number_of_attempts, max_attempts) => {
                        cy.log(
                            `Wait for Tuleap Functions for Tracker (attempt ${number_of_attempts}/${max_attempts})`,
                        );

                        // More than one comment means artifact creation + update by async worker
                        return cy
                            .getFromTuleapAPI(`/api/artifacts/${artifact_id}/changesets`)
                            .then((response) => response.body.length > 1);
                    },
                    `Cannot find results of Tuleap Functions for Tracker computation`,
                );

                cy.visit(`https://tuleap/plugins/tracker/?aid=${artifact_id}`);

                cy.log("Follow-up comment is added");
                cy.contains("Sum of field_a and field_b is odd -> 3 + 4 = 7");

                cy.log("Readonly field is updated");
                cy.get("[data-test=tracker-artifact-value-field_sum]").contains("odd");
            });
    });
});

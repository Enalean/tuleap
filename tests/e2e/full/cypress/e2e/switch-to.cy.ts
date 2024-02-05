/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

describe("Switch To", () => {
    it("has keyboard navigation", () => {
        cy.projectAdministratorSession();

        const now = Date.now();
        const project_name = `switch-to-${now}`;
        cy.createNewPublicProject(project_name, "kanban")
            .then((project_id) => {
                cy.addProjectMember(project_name, "projectMember");
                return Promise.resolve(project_id);
            })
            .then((project_id) => cy.getTrackerIdFromREST(project_id, "activity"))
            .then((tracker_id) => {
                cy.projectAdministratorSession();
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "first title",
                    artifact_status: "To be done",
                    title_field_name: "title",
                }).as("first_artifact");
                cy.createArtifact({
                    tracker_id: tracker_id,
                    artifact_title: "second title",
                    artifact_status: "To be done",
                    title_field_name: "title",
                }).as("second_artifact");
            });

        cy.projectMemberSession();
        cy.get("@first_artifact").then((artifact_id) => {
            cy.visit(`/plugins/tracker/?aid=${artifact_id}`);
        });
        cy.get("@second_artifact").then((artifact_id) => {
            cy.visit(`/plugins/tracker/?aid=${artifact_id}`);
        });

        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").as("body");

        cy.log("Open Switch To modal");
        cy.get("@body").type("s");
        cy.get("[data-test=switch-to-modal]").should("be.visible");
        cy.focused().invoke("attr", "data-test").should("eq", "switch-to-filter");

        cy.log("Jump to first project");
        cy.focused().tab();
        assertFocusedElementIsNthElementInCollection(0, "switch-to-projects-project");

        cy.log("Jump to second project");
        cy.focused().tab();
        assertFocusedElementIsNthElementInCollection(1, "switch-to-projects-project");

        cy.log("Jump to recent item");
        cy.get("@body").type("{rightArrow}");
        assertFocusedElementIsNthElementInCollection(0, "switch-to-item-entry");

        cy.log("Jump to next item");
        cy.get("@body").type("{downArrow}");
        assertFocusedElementIsNthElementInCollection(1, "switch-to-item-entry");

        cy.log("Jump back to project list");
        cy.get("@body").type("{leftArrow}");
        assertFocusedElementIsNthElementInCollection(0, "switch-to-projects-project");

        cy.log("Close Switch To modal");
        cy.get("@body").type("{esc}");
        cy.get("[data-test=switch-to-modal]").should("not.be.visible");

        function assertFocusedElementIsNthElementInCollection(
            expected_index: number,
            collection_name: string,
        ): void {
            cy.focused().should(
                "satisfy",
                (element) =>
                    getElementIndex(element[0].closest(`[data-test=${collection_name}]`)) ===
                    expected_index,
            );
        }

        function getElementIndex(element: HTMLElement): number {
            if (!element.parentElement) {
                return 0;
            }

            return Array.prototype.indexOf.call(element.parentElement.children, element);
        }
    });
});

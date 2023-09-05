/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

import { PROJECT_ADMINISTRATORS_ID } from "@tuleap/core-constants";
import { POST_ACTION_TYPE } from "../../../../../scripts/workflow-transitions/src/constants/workflow-constants";

function getTrackerIdFromTrackerListPage(): Cypress.Chainable<JQuery<HTMLElement>> {
    cy.visitProjectService("tracker-project", "Trackers");
    return cy.get("[data-test=tracker-link-workflow]").should("have.attr", "data-test-tracker-id");
}

describe(`Tracker Workflow`, () => {
    const STATUS_FIELD_LABEL = "Status";
    const REMAINING_EFFORT_FIELD_LABEL = "Remaining Effort";
    const INITIAL_EFFORT_FIELD_LABEL = "Initial Effort";

    before(function () {
        cy.projectAdministratorSession();
        cy.getProjectId("tracker-project").as("project_id");
    });

    it(`has an empty state`, function () {
        cy.projectAdministratorSession();
        getTrackerIdFromTrackerListPage()
            .as("workflow_tracker_id")
            .then((workflow_tracker_id: Cypress.ObjectLike) => {
                cy.visit(`/plugins/tracker/workflow/${workflow_tracker_id}/transitions`);
            });
    });

    beforeEach(function () {
        cy.intercept("/api/tracker_workflow_transitions").as("createTransitions");
        cy.intercept("/api/tracker_workflow_transitions/*").as("updateTransitions");
    });

    context("Simple mode", () => {
        it(`can create and configure a workflow`, function () {
            cy.projectAdministratorSession();
            getTrackerIdFromTrackerListPage()
                .as("workflow_tracker_id")
                .then((workflow_tracker_id: Cypress.ObjectLike) => {
                    cy.visit(`/plugins/tracker/workflow/${workflow_tracker_id}/transitions`);
                });
            /* Create the workflow */
            cy.get("[data-test=tracker-workflow-first-configuration]").within(() => {
                cy.get("[data-test=list-fields]").select(STATUS_FIELD_LABEL);
                cy.get("[data-test=create-workflow]").click();
            });

            /* Add transitions */
            cy.get("[data-test=tracker-workflow-matrix]").within(() => {
                cy.get("[data-test=matrix-row]")
                    .contains("On Going")
                    .parent("[data-test=matrix-row]")
                    .within(() => {
                        cy.get("[data-test-action=create-transition]").each(($button) => {
                            cy.wrap($button).click();
                            cy.wait("@createTransitions");
                        });
                        // Making sure the transition has been created by checking if we can delete it before continuing the test
                        cy.get("[data-test-action=confirm-delete-transition]");
                    });

                cy.get("[data-test=matrix-row]")
                    .contains("(New artifact)")
                    .parent("[data-test=matrix-row]")
                    .within(() => {
                        cy.get("[data-test-action=create-transition]").first().click();
                    });
                cy.get("[data-test=configure-state]").first().click();
            });
            /* Configure a state */
            cy.get("[data-test=transition-modal]").within(() => {
                const project_administrators_ugroup_id =
                    this.project_id + "_" + PROJECT_ADMINISTRATORS_ID;
                cy.get("[data-test=authorized-ugroups-select]").select(
                    project_administrators_ugroup_id,
                    { force: true },
                );
                cy.get("[data-test=not-empty-field-form-element]").within(() => {
                    cy.get("[data-test=list-picker-search-field]").type(
                        REMAINING_EFFORT_FIELD_LABEL + "{enter}",
                    );
                });
                cy.get("[data-test=not-empty-comment-checkbox]").check();
                cy.get("[data-test=add-post-action]").click();
                cy.get("[data-test=post-action-type-select]").select(
                    POST_ACTION_TYPE.FROZEN_FIELDS,
                );
                cy.get("[data-test=frozen-fields-form-element]").within(() => {
                    cy.get("[data-test=list-picker-search-field]").type(
                        INITIAL_EFFORT_FIELD_LABEL + "{enter}",
                    );
                });
                cy.get("[data-test=save-button]").click();
            });
            /* Delete a transition */
            cy.get("[data-test=tracker-workflow-matrix]").within(() => {
                cy.get("[data-test=matrix-row]")
                    .contains("(New artifact)")
                    .parent("[data-test=matrix-row]")
                    .and("contain", "(New artifact)")
                    .within(() => {
                        cy.get("[data-test-action=delete-transition]").first().click();
                        // Making sure the transition deletion is visible in the UI (aka there is no more a delete button) before continuing
                        cy.wait("@updateTransitions");
                        cy.get("[data-test-action=confirm-delete-transition]").should("not.exist");
                    });
            });
            /* Delete the entire workflow */
            cy.get("[data-test=change-or-remove-button]").click();
            cy.get("[data-test=change-field-confirmation-modal]").within(() => {
                cy.get("[data-test=confirm-button]").click();
            });
        });

        context("Workflow switch mode", () => {
            it(`User can switch mode to use simple mode`, function () {
                cy.projectAdministratorSession();
                cy.visitProjectService("workflow", "Trackers");
                cy.get("[data-test=tracker-link-workflow_simple_mode]").click();
                cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
                cy.get("[data-test=workflow]").click();
                cy.get("[data-test=transitions]").click();

                cy.log("Warns user that they can lose part of configuration");

                cy.get("[data-test=switch-mode]").then(($switch_mode) => {
                    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
                    if (($switch_mode[0] as HTMLInputElement).checked) {
                        cy.get("[data-test=switch-button-mode]").click();
                        cy.get("[data-test=button-switch-to-simple-configuration]").click();
                    }
                });

                cy.log("Pre condition for open status are correct");
                cy.get("[data-test=configure-state").first().click();
                cy.get("[data-test=authorized-ugroups-select]")
                    .find("option:selected")
                    .should("contain", "Project members");

                cy.get("[data-test=not-empty-field-select]")
                    .find("option:selected")
                    .should("contain", "Summary");
                cy.get("[data-test=post-action-type-select]")
                    .first()
                    .find("option:selected")
                    .should("contain", "Change the value of a field");

                cy.get("[data-test=field]")
                    .first()
                    .find("option:selected")
                    .should("contain", "Close date");

                cy.get("[data-test=field]")
                    .last()
                    .find("option:selected")
                    .should("contain", "Effort");
                cy.get("[data-test=cancel-button]").click();

                cy.log("Pre condition for closed status are correct");
                cy.get("[data-test=configure-state").last().click();
                cy.get("[data-test=authorized-ugroups-select]")
                    .find("option:selected")
                    .should("contain", "Project members");

                cy.get("[data-test=not-empty-field-select]")
                    .find("option:selected")
                    .should("contain", "Original Submission");
                cy.get("[data-test=post-action-type-select]")
                    .first()
                    .find("option:selected")
                    .should("contain", "Change the value of a field");

                cy.get("[data-test=field]")
                    .first()
                    .find("option:selected")
                    .should("contain", "Close date");

                cy.get("[data-test=post-action-type-select]")
                    .last()
                    .find("option:selected")
                    .contains("Launch a CI job");
            });
        });
    });

    context("Project member", () => {
        it(`Workflow hidden fieldset`, function () {
            cy.projectMemberSession();
            cy.log("Everything is visible at artifact creation");
            cy.visitProjectService("workflow", "Trackers");
            cy.get('[data-test="tracker-link-bugs_hidden"]').click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=summary]").type("My artifact");

            getFieldsetWithLabel("Access Information (Fieldset should be hidden)").should(
                "be.visible",
            );

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("`Access Information` fieldset is hidden at update");
            selectLabelInListPickerDropdown("On going");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            getFieldsetWithLabel("Access Information (Fieldset should be hidden)").should(
                "not.be.visible",
            );
        });

        it(`Workflow frozen fields`, function () {
            cy.projectMemberSession();
            cy.log("Every field can be updated at artifact creation");
            cy.visitProjectService("workflow", "Trackers");
            cy.get("[data-test=tracker-link-frozen_fields]").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");
            cy.get("[data-test=points]").type("10");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("`fields points` and `title` can no longer be updated");
            cy.get("[data-test=edit-field-title]").should("not.exist");
            cy.get("[data-test=edit-field-points]").should("not.exist");
            cy.get("[data-test=edit-field-status]").should("exist");
        });

        it(`Workflow required comment`, function () {
            cy.projectMemberSession();
            cy.log("Comment is not required at creation");
            cy.visitProjectService("workflow", "Trackers");
            cy.get("[data-test=tracker-link-required]").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("Comments are required at update");
            cy.get('[data-test="edit-field-title"]').click();
            cy.get("[data-test=title]").clear().type("My artifact updated");
            selectLabelInListPickerDropdown("Done");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Comment must not be empty");

            cy.get('[data-test="artifact_followup_comment"]').type("My comment");
            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Successfully Updated");
        });

        it(`Workflow required fields`, function () {
            cy.projectMemberSession();
            cy.log("Fields are not required a submission");
            cy.visitProjectService("workflow", "Trackers");
            cy.get("[data-test=tracker-link-required_fields]").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("Field are required at update");
            cy.get('[data-test="edit-field-title"]').click();
            cy.get("[data-test=title]").clear().type("My artifact updated");
            selectLabelInListPickerDropdown("Done");

            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Invalid condition: the field");

            cy.get("[data-test=required_by_workflow]").type("required");
            cy.get("[data-test=artifact-submit-options]").click();
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Successfully Updated");
        });
    });
});

function selectLabelInListPickerDropdown(label: string): void {
    cy.get("[data-test=edit-field-status]").click();
    cy.get("[data-test=list-picker-selection]").first().click();
    cy.root().within(() => {
        cy.get("[data-test-list-picker-dropdown-open]").within(() => {
            cy.get("[data-test=list-picker-item]").contains(label).click();
        });
    });
}

function getFieldsetWithLabel(label: string): Cypress.Chainable<JQuery<HTMLElement>> {
    cy.get("[data-test=fieldset-label]").contains(label).parent();
    return cy
        .get("[data-test=fieldset-label]")
        .contains(label)
        .parents("[data-test=fieldset]")
        .within(() => {
            return cy.get("[data-test=fieldset-content]");
        });
}

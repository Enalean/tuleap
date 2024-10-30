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

const FROZEN_FIELDS_POST_ACTION_TYPE = "frozen_fields";

function getTrackerIdFromTrackerListPage(
    project_name: string,
    tracker_label: string,
): Cypress.Chainable<string> {
    cy.visitProjectService(project_name, "Trackers");
    return cy
        .getContains("[data-test=tracker-link]", tracker_label)
        .invoke("data", "testTrackerId");
}

function saveTransition(
    position: number,
    transition_on_field: string,
    field_name: string,
    field_value: string,
): void {
    cy.get("[data-test-action=configure-transition]").eq(position).click();
    cy.get("[data-test=add-post-action]").click();
    cy.get("[data-test=post-action-type-select]").select(transition_on_field);

    cy.get("[data-test-type=field]").select(field_name);
    cy.get("[data-test=select-date]").select(field_value);
    cy.get("[data-test=save-button]").click();
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
        getTrackerIdFromTrackerListPage("tracker-project", "Workflow")
            .as("workflow_tracker_id")
            .then((tracker_id) => {
                cy.visit(`/plugins/tracker/workflow/${tracker_id}/transitions`);
            });
    });

    beforeEach(function () {
        cy.intercept("/api/tracker_workflow_transitions").as("createTransitions");
        cy.intercept("/api/tracker_workflow_transitions/*").as("updateTransitions");
    });

    context("Simple mode", () => {
        it(`can create and configure a workflow`, function () {
            cy.projectAdministratorSession();
            getTrackerIdFromTrackerListPage("tracker-project", "Workflow").then((tracker_id) => {
                cy.visit(`/plugins/tracker/workflow/${tracker_id}/transitions`);
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
                    FROZEN_FIELDS_POST_ACTION_TYPE,
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
                        cy.get("[data-test=delete-transition-without-confirmation]")
                            .first()
                            .click();
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
                cy.getContains("[data-test=tracker-link]", "Workflow Simple Mode").click();
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
            cy.getContains("[data-test=tracker-link]", "Bug Hidden").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=summary]").type("My artifact");

            getFieldsetWithLabel("Access Information (Fieldset should be hidden)").should(
                "be.visible",
            );

            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("`Access Information` fieldset is hidden at update");
            selectLabelInListPickerDropdown("On going");

            cy.get("[data-test=artifact-submit-and-stay]").click();

            getFieldsetWithLabel("Access Information (Fieldset should be hidden)").should(
                "not.be.visible",
            );
        });

        it(`Workflow frozen fields`, function () {
            cy.projectMemberSession();
            cy.log("Every field can be updated at artifact creation");
            cy.visitProjectService("workflow", "Trackers");
            cy.getContains("[data-test=tracker-link]", "Frozen fields").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");
            cy.get("[data-test=points]").type("10");

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
            cy.getContains("[data-test=tracker-link]", "required comments").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");

            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("Comments are required at update");
            cy.get('[data-test="edit-field-title"]').click();
            cy.get("[data-test=title]").clear().type("My artifact updated");
            selectLabelInListPickerDropdown("Done");

            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Comment must not be empty");

            cy.get('[data-test="artifact_followup_comment"]').type("My comment");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Successfully Updated");
        });

        it(`Workflow required fields`, function () {
            cy.projectMemberSession();
            cy.log("Fields are not required a submission");
            cy.visitProjectService("workflow", "Trackers");
            cy.getContains("[data-test=tracker-link]", "required fields").click();
            cy.get("[data-test=new-artifact]").click();
            cy.get("[data-test=title]").type("My artifact");

            cy.get("[data-test=artifact-submit-and-stay]").click();

            cy.log("Field are required at update");
            cy.get('[data-test="edit-field-title"]').click();
            cy.get("[data-test=title]").clear().type("My artifact updated");
            selectLabelInListPickerDropdown("Done");

            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Invalid condition: the field");

            cy.get("[data-test=required_by_workflow]").type("required");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=feedback]").contains("Successfully Updated");
        });

        it(`Workflow PostActions on transitions`, function () {
            cy.projectAdministratorSession();
            const now = Date.now();
            const project_name = "transitions-" + now;
            cy.createNewPublicProject(project_name, "issues");
            cy.addProjectMember(project_name, "projectMember");

            cy.projectAdministratorSession();
            cy.log("Import the tracker from XML");
            cy.visit(`/plugins/tracker/${project_name}/new`);
            cy.get("[data-test=template-xml-description]").click();
            cy.get("[data-test=tracker-creation-xml-file-selector]").selectFile(
                "./_fixtures/TrackerValidPostAction.xml",
            );

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=chosen-template]").contains("SR8");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").click();
            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });

            cy.get("[data-test=workflow]").click();
            cy.get("[data-test=transitions]").click();

            cy.log("Add action change value of field in transition Open => Closed");
            saveTransition(1, "set_field_value", "Close date", "Current time");

            cy.log("Try to add the same transition");
            cy.log("Add action change value of field in transition Open => Closed");
            cy.get("[data-test-action=configure-transition]").eq(1).click();
            cy.get("[data-test=add-post-action]").click();
            cy.get("[data-test=post-action-type-select]").eq(1).select("set_field_value");
            cy.get("[data-test-type=field] > option").eq(1).should("be.disabled");
            cy.get("[data-test=cancel-button]").click();

            cy.log("Add action change value of field in transition Close => Open");
            saveTransition(2, "set_field_value", "Close date", "Clear");

            cy.log("Add action change value of field in transition New Artifact => Open");
            saveTransition(0, "set_field_value", "Start date", "Current time");

            cy.get("[data-test=workflow-transitions-enabled]").click({ force: true });

            cy.log("Create a new Artifact should set Start Date");
            getTrackerIdFromTrackerListPage(project_name, "SR8")
                .as("tracker_id")
                .then((tracker_id) => {
                    cy.visit(`/plugins/tracker/?tracker=${tracker_id}&func=new-artifact`);
                });
            cy.get("[data-test=summary]").type("My artifact");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            const today = new Date().toISOString().slice(0, 10);
            cy.get("[data-test=tracker-artifact-value-start_date]").contains(today);

            cy.log("Closing an artifact should update close date");
            selectLabelInListPickerDropdown("Closed");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").contains(today);

            cy.log("Reopening an artifact should clear close date");
            selectLabelInListPickerDropdown("Open");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").should(
                "not.include.text",
                today,
            );

            cy.log("End date is set by workflow, and not by user");
            cy.get("[data-test=edit-field-start_date]").click();
            cy.get('[data-test="date-time-start_date"]').clear().type("2024-06-01");
            selectLabelInListPickerDropdown("Closed");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").contains(today);

            cy.log("User can change artifact status even when he can not update date field");
            cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=field-permissions]").click();
            cy.get("[data-test=select-field-permissions]").select("Close date");
            cy.get("[data-test=field-permissions]").eq(3).select("Read only");
            cy.get("[data-test=submit-permissions]").click();

            cy.projectMemberSession();
            cy.wrap("").then(() => {
                cy.visit(`/plugins/tracker?tracker=${this.tracker_id}`);
            });
            cy.get("[data-test=direct-link-to-artifact]").click();
            selectLabelInListPickerDropdown("Open");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").should(
                "not.include.text",
                today,
            );

            cy.projectAdministratorSession();
            cy.wrap("").then(() => {
                cy.visit(`/plugins/tracker?tracker=${this.tracker_id}&func=admin`);
            });
            cy.log("User can change artifact status even when he can not see date field");
            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=field-permissions]").click();
            cy.get("[data-test=select-field-permissions]").select("Close date");
            cy.get("[data-test=field-permissions]").eq(1).select("-");
            cy.get("[data-test=field-permissions]").eq(2).select("-");
            cy.get("[data-test=field-permissions]").eq(3).select("-");
            cy.get("[data-test=field-permissions]").eq(4).select("Read only");
            cy.get("[data-test=submit-permissions]").click();

            cy.projectMemberSession();
            cy.wrap("").then(() => {
                cy.visit(`/plugins/tracker?tracker=${this.tracker_id}`);
            });
            cy.get("[data-test=direct-link-to-artifact]").click();
            selectLabelInListPickerDropdown("Close");
            cy.get("[data-test=artifact-submit-and-stay]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").should("not.exist");

            cy.projectAdministratorSession();
            cy.wrap("").then(() => {
                cy.visit(`/plugins/tracker?tracker=${this.tracker_id}`);
            });
            cy.get("[data-test=direct-link-to-artifact]").click();
            cy.get("[data-test=tracker-artifact-value-close_date]").contains(today);
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

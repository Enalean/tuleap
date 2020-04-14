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

describe("Tracker artifacts", function () {
    let artifact_id;

    describe("Site admin specific settings for move/deletion", function () {
        it("must be able to set the artifact deletion setting", function () {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.platformAdminLogin();

            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=admin-tracker]").click();
            cy.get("[data-test=artifact-deletion]").click();
            cy.get("[data-test=input-artifacts-limit]").clear().type(50);
            cy.get("[data-test=artifact-deletion-button]").click();
            cy.get("[data-test=feedback]").contains("Limit successfully updated.");
        });
    });

    describe("Tracker administration", function () {
        before(function () {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.ProjectAdministratorLogin();
        });

        beforeEach(function () {
            Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
            cy.visitProjectService("tracker-artifact", "Trackers");
        });

        it("must be able to create tracker from Tuleap template Bug", function () {
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-default-bug]").click({ force: true });

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type(" from cypress");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });

        it("must be able to create tracker from empty", function () {
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-tracker_empty]").click({ force: true });

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type("From empty");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });

        it("must be able to create tracker from an other project", function () {
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=selected-option-tracker_another_project]").click({ force: true });

            cy.get("[data-test=project-select]").select("timetracking");
            cy.get("[data-test=project-tracker-select]").select("Issues");

            cy.get("[data-test=button-next]").click();
            cy.get("[data-test=tracker-name-input]").type("From an other project");
            cy.get("[data-test=button-create-my-tracker]").click();
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");
        });
    });

    describe("Tracker regular users", function () {
        before(function () {
            cy.clearCookie("__Host-TULEAP_session_hash");
            cy.projectMemberLogin();
        });

        beforeEach(function () {
            Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        });

        describe("Artifact manipulation", function () {
            it("must be able to create new artifact", function () {
                cy.visitProjectService("tracker-artifact", "Trackers");
                cy.get("[data-test=tracker-link-bug]").click();
                cy.get("[data-test=new-artifact]").click();

                cy.get("[data-test=summary]").type("My new bug");

                cy.get("[data-test=artifact-submit-options]").click();
                cy.get("[data-test=artifact-submit-and-stay]").click();

                cy.get("[data-test=feedback]").contains("Artifact Successfully Created");
                cy.get("[data-test=tracker-artifact-value-summary]").contains("My new bug");

                cy.get("[data-test=current-artifact-id]").should(($input) => {
                    artifact_id = $input.val();
                });
            });

            it("must be able to copy new artifact", function () {
                cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);

                cy.get("[data-test=tracker-artifact-actions]").click();
                cy.get("[data-test=artifact-copy-button]").click();
                cy.get("[data-test=edit-field-summary]").click();
                cy.get("[data-test=summary]").clear().type("My updated summary");

                cy.get("[data-test=artifact-copy]").click();

                cy.get("[data-test=artifact-followups]").contains("Copy of bug");
                cy.get("[data-test=tracker-artifact-value-summary]").contains("My updated summary");
            });

            it("can be displayed in printer version", function () {
                cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);

                let current_url;
                cy.url().then((url) => {
                    current_url = url;
                    cy.visit(current_url + "&pv=1");

                    // check that followup block is displayed
                    cy.get("[data-test=artifact-followups]");
                });
            });

            it("can switch from autocomputed mode to calculated mode and so on", function () {
                cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);

                //edit field and set 20 as values
                cy.get("[data-test=edit-field-remaining_effort]").click();
                cy.get("[data-test=remaining_effort]").clear().type(20);

                // submit and check
                cy.get("[data-test=artifact-submit-options]").click();
                cy.get("[data-test=artifact-submit-and-stay]").click();
                cy.get("[data-test=computed-value]").contains(20);

                //edit field and go back in autocomputed mode
                cy.get("[data-test=edit-field-remaining_effort]").click();
                cy.get("[data-test=switch-to-autocompute]").click();

                //submit and check
                cy.get("[data-test=artifact-submit-options]").click();
                cy.get("[data-test=artifact-submit-and-stay]").click();
                cy.get("[data-test=computed-value]").contains("Empty");
            });
        });
    });

    describe("Tracker dedicated permissions", function () {
        before(function () {
            cy.clearCookie("__Host-TULEAP_session_hash");
        });

        it("tracker admin must be able to delegate tracker administration privilege", function () {
            cy.ProjectAdministratorLogin();
            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-bug]").click();
            cy.get("[data-test=tracker-administration]").click();

            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=tracker-permissions]").click();

            cy.get("[data-test=permissions_3]").select("are admin of the tracker");

            cy.get("[data-test=tracker-permission-submit]").click();

            cy.get("[data-test=feedback]").contains("Permissions Updated");

            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-story]").click();
            cy.get("[data-test=tracker-administration]").click();

            cy.get("[data-test=admin-permissions]").click();
            cy.get("[data-test=tracker-permissions]").click();

            cy.get("[data-test=permissions_3]").select("are admin of the tracker");

            cy.get("[data-test=tracker-permission-submit]").click();

            cy.get("[data-test=feedback]").contains("Permissions Updated");
        });

        it("regular user must be able to move artifact", function () {
            cy.projectMemberLogin();
            cy.visit("https://tuleap/plugins/tracker/?&aid=" + artifact_id);

            cy.get("[data-test=tracker-artifact-actions]").click();
            cy.get("[data-test=tracker-action-button-move]").click();

            cy.get("[data-test=move-artifact-project-selector]").select("tracker artifact");
            cy.get("[data-test=move-artifact-tracker-selector]").select("User Stories");

            cy.get("[data-test=move-artifact]").click();

            // assert messages of dry run are present
            cy.get("[data-test=dry-run-message-error]");
            cy.get("[data-test=dry-run-message-info]");

            cy.get("[data-test=confirm-move-artifact]").click();
        });

        it("user with tracker admin permissions are tracker admin", function () {
            cy.projectMemberLogin();
            cy.visitProjectService("tracker-artifact", "Trackers");

            cy.get("[data-test=tracker-link-bug]").click();
            cy.get("[data-test=tracker-administration]").click();
        });
    });
});

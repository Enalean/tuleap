/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

describe("Test plan", function () {
    before(() => {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    beforeEach(function () {
        Cypress.Cookies.preserveOnce("__Host-TULEAP_PHPSESSID", "__Host-TULEAP_session_hash");
        cy.server();
    });

    context("As project member", () => {
        let now: number;
        before(() => {
            cy.projectMemberLogin();
            now = Date.now();
        });

        it("Displays no campaign nor backlog items if release does not have one", () => {
            goToTestPlanOfMilestone("Release without campaigns");

            cy.contains("There is no test campaign yet.");
            cy.contains("There is no backlog item yet");
        });

        context("In a release with campaigns and backlog items", () => {
            before(() => {
                goToTestPlanOfMilestone("Release with campaigns");
            });

            context("Test campaigns", () => {
                it("Displays the campaigns", () => {
                    cy.contains("Campaign 1");
                    cy.contains("9 tests");
                });

                it("Adds new campaign", () => {
                    cy.get("[data-test=new-campaign]").click();
                    cy.get("[data-test=new-campaign-label]").type("Campaign " + now);
                    cy.get("[data-test=new-campaign-tests]").select("Test Suite Complete");
                    cy.get("[data-test=new-campaign-submit-button]").click();
                    cy.contains("Campaign " + now);
                    cy.contains("8 tests");
                });
            });

            context("Test plan", () => {
                it("Display the backlog items", () => {
                    cy.contains("Display list of backlog items with their tests definition");
                    cy.contains("Create new campaign in new “Test” screen").within(() => {
                        cy.get("[data-test=nb-tests]").contains("3 planned tests");
                        cy.get("[data-test=backlog-item-icon]").should(($icon) => {
                            expect($icon).to.have.length(1);
                            expect($icon[0].className).to.match(
                                /test-plan-backlog-item-coverage-icon-/
                            );
                        });
                    });
                });

                it("Expand a backlog items to see its test coverage", () => {
                    cy.contains(
                        "Display list of backlog items with their tests definition"
                    ).click();
                    cy.contains("Update artifact")
                        .parent()
                        .within(() => {
                            cy.get("[data-test=test-status-icon]").should(($icon) => {
                                expect($icon).to.have.length(1);
                                expect($icon[0].className).to.match(
                                    /test-plan-test-definition-icon-status-/
                                );
                            });
                        });
                    cy.contains("Send beeper notification")
                        .parent()
                        .within(() => {
                            cy.get("[data-test=automated-test-icon]");
                        });
                });

                it("Marks a test as failed", () => {
                    assertStatusOfTestReflectsCurrentStatus(now, "failed");
                });

                it("Marks a test as blocked", () => {
                    assertStatusOfTestReflectsCurrentStatus(now, "blocked");
                });

                it("Marks a test as notrun", () => {
                    assertStatusOfTestReflectsCurrentStatus(now, "notrun");
                });

                it("Marks a test as passed", () => {
                    assertStatusOfTestReflectsCurrentStatus(now, "passed");
                });

                it("Creates a new test", () => {
                    const new_test_summary = "New test " + now;
                    cy.get("[data-test=add-test-button]").click();
                    cy.get("[data-test=summary]").type(new_test_summary + "{enter}");
                    cy.contains("Display list of backlog items with their tests definition");
                    cy.contains("Update artifact");
                    cy.contains("Send beeper notification");
                    cy.contains(new_test_summary);
                });
            });
        });
    });
});

function goToTestPlanOfMilestone(milestone_label: string): void {
    cy.visitProjectService("test-testplan-project", "Agile Dashboard");
    cy.contains(milestone_label)
        .parent()
        .within(() => {
            cy.get("[data-test=go-to-planning]").click();
        });

    cy.get("[data-test=tab-testplan]").click();
}

function assertStatusOfTestReflectsCurrentStatus(now: number, new_status: string): void {
    cy.contains("Campaign " + now).click();
    cy.contains("Update artifact").click();
    cy.get(`[data-test=mark-test-as-${new_status}]`).click();
    cy.contains("Release with campaigns").click();
    cy.contains("Display list of backlog items with their tests definition")
        .within(() => {
            if (new_status === "blocked" || new_status === "failed") {
                cy.get("[data-test=backlog-item-icon]").should(
                    "have.class",
                    "test-plan-backlog-item-coverage-icon-" + new_status
                );
            } else {
                cy.get("[data-test=backlog-item-icon]").should(
                    "have.class",
                    "test-plan-backlog-item-coverage-icon-notrun"
                );
            }
        })
        .click();
    cy.contains("Update artifact")
        .parent()
        .within(() => {
            cy.get("[data-test=test-status-icon]").should(
                "have.class",
                `test-plan-test-definition-icon-status-${new_status}`
            );
        });
}

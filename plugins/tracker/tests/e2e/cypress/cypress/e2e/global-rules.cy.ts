/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

describe("Tracker Workflow Global Rules", () => {
    context("Tracker Rule date verifications for a workflow", () => {
        it("tests rule dates", () => {
            cy.projectAdministratorSession();
            cy.visitProjectService("workflow", "Trackers");
            cy.get("[data-test=new-tracker-creation]").click();
            cy.get("[data-test=template-description]")
                .contains("Tracker from another project")
                .click();
            cy.get("[data-test=project-select]").select("Workflow");
            cy.get("[data-test=project-tracker-select]").select("Tracker Rule Date Test Tracker");
            cy.get("[data-test=button-next]").click();

            const name = "dates" + Date.now();
            cy.get("[data-test=tracker-name-input]")
                .clear()
                .type(name + "{enter}");
            cy.get("[data-test=tracker-creation-modal-success]").contains("Congratulations");

            cy.log("Set rule Start Date < End Date");
            goToWorkflowGlobalRulesAdministration();
            cy.get("[data-test=add-new-rule]").click();
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[source_date_field]"]')
                .select("Start Date");
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[comparator]"]')
                .select("<");
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[target_date_field]"]')
                .select("End Date");
            cy.get("[data-test=submit]").click();
            cy.get("[data-test=feedback]").contains("Rule successfully created");
            cy.get("[data-test=global-rule]").should("have.length", 1);

            cy.log("Test Start Date < End Date");
            cy.get("[data-test=create-new-item]").first().click({ force: true });
            cy.get("[data-test=date-time-start_date]").clear().type("2023-06-21");
            cy.get("[data-test=date-time-end_date]").clear().type("2023-06-20");
            cy.get("[data-test=summary]").type("Blah{enter}");
            cy.get("[data-test=feedback]").contains(
                "Error on the date value : Start Date must be < to End Date",
            );

            cy.get("[data-test=date-time-start_date]").clear().type("2023-06-21");
            cy.get("[data-test=date-time-end_date]").clear().type("2023-06-22");
            cy.get("[data-test=summary]").type("Blah{enter}");
            cy.get("[data-test=feedback]").contains("Artifact Successfully Created");

            cy.log("Set rule Due Date > End Date");
            goToWorkflowGlobalRulesAdministration();
            cy.get("[data-test=add-new-rule]").click();
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[source_date_field]"]')
                .select("Due Date");
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[comparator]"]')
                .select(">");
            cy.get("[data-test=global-rules-form]")
                .find('[name="add_rule[target_date_field]"]')
                .select("End Date");
            cy.get("[data-test=submit]").click();
            cy.get("[data-test=feedback]").contains("Rule successfully created");
            cy.get("[data-test=global-rule]").should("have.length", 2);

            cy.log("Test Due Date > End Date");
            cy.get("[data-test=create-new-item]").first().click({ force: true });
            cy.get("[data-test=date-time-start_date]").clear().type("2023-06-21");
            cy.get("[data-test=date-time-due_date]").clear().type("2023-06-22");
            cy.get("[data-test=date-time-end_date]").clear().type("2023-06-23");
            cy.get("[data-test=summary]").type("Blah{enter}");
            cy.get("[data-test=feedback]").contains(
                "Error on the date value : Due Date must be > to End Date.",
            );

            cy.get("[data-test=date-time-start_date]").clear().type("2023-06-21");
            cy.get("[data-test=date-time-due_date]").clear().type("2023-06-24");
            cy.get("[data-test=date-time-end_date]").clear().type("2023-06-23");
            cy.get("[data-test=summary]").type("Blah{enter}");
            cy.get("[data-test=feedback]").contains("Artifact Successfully Created");

            cy.log("Delete rule");
            goToWorkflowGlobalRulesAdministration();
            cy.get("[data-test=global-rules-form]")
                .find('[name="remove_rules[]"]')
                .first()
                .check({ force: true });
            cy.get("[data-test=submit]").click();
            cy.get("[data-test=feedback]").contains("Rule(s) successfully deleted");
            cy.get("[data-test=global-rule]").should("have.length", 1);

            cy.log("Test that deleted rule is not anymore applied");
            cy.get("[data-test=create-new-item]").first().click({ force: true });
            cy.get("[data-test=date-time-start_date]").clear().type("2023-06-21");
            cy.get("[data-test=date-time-end_date]").clear().type("2023-06-20");
            cy.get("[data-test=summary]").type("Blah{enter}");
            cy.get("[data-test=feedback]").contains("Artifact Successfully Created");
        });
    });
});

function goToWorkflowGlobalRulesAdministration(): void {
    cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
    cy.get("[data-test=workflow]").click();
    cy.get("[data-test=global-rules]").click();
}

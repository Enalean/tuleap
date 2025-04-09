/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

type CypressWrapper = Cypress.Chainable<JQuery<HTMLElement>>;
function getFieldWithLabel(label: string): CypressWrapper {
    return cy
        .get("[data-test=artifact-form-element] [data-test=field-label]")
        .contains(label)
        .parents("[data-test=artifact-form-element]");
}

function checkBoxWithLabel(label: string): void {
    cy.get("[data-test=checkbox-field-value]")
        .contains(label)
        .find("[data-test=checkbox-field-input]")
        .check();
}

function checkRadioButtonWithLabel(label: string): void {
    cy.get("[data-test=radiobutton-field-value]")
        .contains(label)
        .find("[data-test=radiobutton-field-input]")
        .check();
}

function createArtifactWithValues(): void {
    cy.log("Create an artifact with all fields");
    getFieldWithLabel("Title").find("[data-test-field-input]").type("Title A");
    getFieldWithLabel("Description").find("[data-test-cypress=text-area]").type("Description A");
    getFieldWithLabel("String").find("[data-test-field-input]").type("String A");
    getFieldWithLabel("Text").find("[data-test-cypress=text-area]").type("Description A");
    getFieldWithLabel("Integer").find("[data-test-field-input]").type("12");
    getFieldWithLabel("Float").find("[data-test-field-input]").type("5.12");
    getFieldWithLabel("Date").find("[data-test=date-time-date]").clear().type("2025-02-01");
    getFieldWithLabel("Datetime")
        .find("[data-test=date-time-datetime]")
        .clear()
        .type("2025-02-01 02:23");
    getFieldWithLabel("Computed").find("[data-test-field-input]").type("13.5");

    getFieldWithLabel("Attachments").then(($field) => {
        cy.wrap($field)
            .find("[data-test=file-field-file-input]")
            .selectFile("cypress/fixtures/attachment.json");
        cy.wrap($field).find("[data-test=file-field-description-input]").type("My JSON attachment");
    });

    getFieldWithLabel("Permissions").then(($field) => {
        cy.wrap($field).find("[data-test=artifact-permission-enable-checkbox]").check();
        cy.wrap($field)
            .find("[data-test=artifact-permissions-selectbox]")
            .select(["Project members", "Integrators"]);
    });

    getFieldWithLabel("Selectbox static").within(() => {
        cy.searchItemInListPickerDropdown("Dos").click();
    });

    getFieldWithLabel("Selectbox users (members)").within(() => {
        cy.searchItemInListPickerDropdown("ProjectMember").click();
    });

    getFieldWithLabel("Selectbox ugroups").within(() => {
        cy.searchItemInListPickerDropdown("Integrators").click();
    });

    getFieldWithLabel("Radio static").within(() => {
        checkRadioButtonWithLabel("二");
    });

    getFieldWithLabel("Radio users (members)").within(() => {
        checkRadioButtonWithLabel("ProjectMember");
    });

    getFieldWithLabel("Radio ugroups").within(() => {
        checkRadioButtonWithLabel("Integrators");
    });

    getFieldWithLabel("MSB static").within(() => {
        cy.searchItemInListPickerDropdown("Deux").click();
        cy.searchItemInListPickerDropdown("Trois").click();
    });

    getFieldWithLabel("MSB users (members)").within(() => {
        cy.searchItemInListPickerDropdown("ProjectMember").click();
    });

    getFieldWithLabel("MSB ugroups").within(() => {
        cy.searchItemInListPickerDropdown("Integrators").click();
    });

    getFieldWithLabel("Checkbox static").within(() => {
        checkBoxWithLabel("One");
        checkBoxWithLabel("Three");
    });

    getFieldWithLabel("Checkbox users (members)").within(() => {
        checkBoxWithLabel("ProjectMember");
    });

    getFieldWithLabel("Checkbox ugroups").within(() => {
        checkBoxWithLabel("Project administrators");
        checkBoxWithLabel("Contributors");
    });

    cy.get("[data-test=artifact-submit-button]").click();
    cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
}

function updateTrackerReportCriterias(): void {
    cy.log("Update report in order to have a query for every field of artifact");
    cy.log("Update criteria Title");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Title")
        .closest("li")
        .find("[data-test=alphanum-report-criteria]")
        .type("Title A");

    cy.log("Update criteria Description");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Description")
        .closest("li")
        .find("[data-test=alphanum-report-criteria]")
        .type("Description A");

    cy.log("Update criteria String");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "String")
        .closest("li")
        .find("[data-test=alphanum-report-criteria]")
        .type("String A");

    cy.log("Update criteria Text");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Text")
        .closest("li")
        .find("[data-test=alphanum-report-criteria]")
        .type("Description A");

    cy.log("Update criteria Integer");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Integer")
        .closest("li")
        .find("[data-test=integer-report-criteria]")
        .type("12");

    cy.log("Update criteria Float");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Float")
        .closest("li")
        .find("[data-test=float-report-criteria]")
        .type("5.12");

    cy.log("Update criteria Date");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Date")
        .closest("li")
        .find("[data-test=date-time-date]")
        .type("2025-02-01");

    cy.log("Update criteria Datetime");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Datetime")
        .closest("li")
        .find("[data-test=date-time-datetime]")
        .type("2025-02-01");

    cy.log("Update criteria Attachments");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Attachments")
        .closest("li")
        .find("[data-test=file-report-criteria]")
        .type("My JSON attachment");

    cy.log("Update criteria Permissions");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Permissions")
        .closest("li")
        .within(() => {
            cy.get("[data-test=tracker-report-criteria-advanced-toggle]").click();
            cy.get("[data-test=permissions-report-criteria]").select([
                "Project members",
                "Integrators",
            ]);
        });

    cy.log("Update criteria Selectbox static");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Selectbox static")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("Dos");

    cy.log("Update criteria Selectbox users");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Selectbox users")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Selectbox ugroups");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Selectbox ugroups")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("Integrators");

    cy.log("Update criteria Radio static");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Radio static")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("二");

    cy.log("Update criteria Radio users");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Radio users (members)")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Radio ugroups");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Radio ugroups")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("Integrators");

    cy.log("Update criteria MSB static");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "MSB static")
        .closest("li")
        .within(() => {
            cy.get("[data-test=tracker-report-criteria-advanced-toggle]").click();
            cy.get("[data-test=list-report-criteria]").select(["Deux", "Trois"]);
        });

    cy.log("Update criteria MSB users");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "MSB users (members)")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria MSB ugroups");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "MSB ugroups")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("Integrators");

    cy.log("Update criteria Checkbox static");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Checkbox static")
        .closest("li")
        .within(() => {
            cy.get("[data-test=tracker-report-criteria-advanced-toggle]").click();
            cy.get("[data-test=list-report-criteria]").select(["One", "Three"]);
        });

    cy.log("Update criteria Checkbox users");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Checkbox users (members)")
        .closest("li")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Checkbox ugroups");
    cy.get("[data-test=report-criteria-item]")
        .contains("label", "Checkbox ugroups")
        .closest("li")
        .within(() => {
            cy.get("[data-test=tracker-report-criteria-advanced-toggle]").click();
            cy.get("[data-test=list-report-criteria]").select([
                "Project administrators",
                "Contributors",
            ]);
        });

    cy.log("Search ");
    cy.get("[data-test=submit-report-search]").click();
    cy.get("[data-test=number-of-matching-artifacts]").should("contain", 1);
}

describe(`Tracker Report`, () => {
    it(`Can submit an artifact with all fields`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("tracker-report", "Trackers");
        cy.get("[data-test=tracker-link]").click();
        cy.get("[data-test=new-artifact]").click();
        createArtifactWithValues();
        updateTrackerReportCriterias();
    });
});

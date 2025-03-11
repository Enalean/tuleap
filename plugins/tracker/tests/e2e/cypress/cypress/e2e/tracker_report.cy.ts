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

describe(`Tracker Report`, () => {
    it(`Can submit an artifact with all fields`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("tracker-report", "Trackers");
        cy.get("[data-test=tracker-link]").click();
        cy.get('[data-test="new-artifact"]').click();

        cy.log("Create an artifact with all fields");
        getFieldWithLabel("Title").find("[data-test-field-input]").type("Title A");
        getFieldWithLabel("Description")
            .find("[data-test-cypress=text-area]")
            .type("Description A");
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
            cy.wrap($field)
                .find("[data-test=file-field-description-input]")
                .type("My JSON attachment");
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

        cy.get('[data-test="artifact-submit-button"]').click();
        cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
    });
});

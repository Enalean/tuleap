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
function getFieldWithLabel(label: string, form_element_selector: string): CypressWrapper {
    return cy.getContains(form_element_selector, label);
}

function checkBoxWithLabel(label: string): void {
    cy.get("[data-test=checkbox-field-value]")
        .contains(label)
        .within(() => {
            cy.get("[data-test=checkbox-field-input]").check();
        });
}

function checkRadioButtonWithLabel(label: string): void {
    cy.get("[data-test=radiobutton-field-value]")
        .contains(label)
        .within(() => {
            cy.get("[data-test=radiobutton-field-input]").check();
        });
}

describe(`Tracker Report`, () => {
    it(`All fields can be used by user`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("tracker-report", "Trackers");
        cy.get("[data-test=tracker-link]").click();
        cy.get('[data-test="new-artifact"]').click();

        cy.log("Create an artifact with all fields");
        cy.get("[data-test=title]").type("Title A");
        cy.get("[data-test-cypress=text-area]").first().type("Description A");
        cy.get("[data-test=string]").type("String A");
        cy.get("[data-test-cypress=text-area]").last().type("Text A");
        cy.get("[data-test=integer]").type("12");
        cy.get("[data-test=float]").type("5.12");
        cy.get("[data-test=date-time-date]").type("2025-02-01");
        cy.get("[data-test=date-time-datetime]").type("2025-02-01 02:23");
        cy.get("[data-test=computed]").type("13.5");
        cy.get("[data-test=file-field-file-input]").selectFile("cypress/fixtures/attachment.json");
        cy.get("[data-test=file-field-description-input]").type("My JSON attachment");
        cy.get("[data-test=artifact-permission-enable-checkbox]").check();
        cy.get("[data-test=artifact-permissions-selectbox]").select([
            "Project members",
            "Integrators",
        ]);

        getFieldWithLabel("Selectbox static", "[data-test=artifact-form-element]").within(() => {
            cy.searchItemInListPickerDropdown("Dos").click();
        });

        getFieldWithLabel("Selectbox users (members)", "[data-test=artifact-form-element]").within(
            () => {
                cy.searchItemInListPickerDropdown("Site Admin").click();
            },
        );

        getFieldWithLabel("Selectbox ugroups", "[data-test=artifact-form-element]").within(() => {
            cy.searchItemInListPickerDropdown("Integrators").click();
        });

        getFieldWithLabel("Radio static", "[data-test=artifact-form-element]").within(() => {
            checkRadioButtonWithLabel("二");
        });

        getFieldWithLabel("Radio users (members)", "[data-test=artifact-form-element]").within(
            () => {
                checkRadioButtonWithLabel("Site Admin");
            },
        );

        getFieldWithLabel("Radio ugroups", "[data-test=artifact-form-element]").within(() => {
            checkRadioButtonWithLabel("Integrators");
        });

        getFieldWithLabel("MSB static", "[data-test=artifact-form-element]").within(() => {
            cy.searchItemInListPickerDropdown("Deux").click();
            cy.searchItemInListPickerDropdown("Trois").click();
        });

        getFieldWithLabel("MSB users (members)", "[data-test=artifact-form-element]").within(() => {
            cy.searchItemInListPickerDropdown("Site Admin").click();
        });

        getFieldWithLabel("MSB ugroups", "[data-test=artifact-form-element]").within(() => {
            cy.searchItemInListPickerDropdown("Integrators").click();
        });

        getFieldWithLabel("Checkbox static", "[data-test=artifact-form-element]").within(() => {
            checkBoxWithLabel("One");
            checkBoxWithLabel("Three");
        });

        getFieldWithLabel("Checkbox users (members)", "[data-test=artifact-form-element]").within(
            () => {
                checkBoxWithLabel("Site Admin");
            },
        );

        getFieldWithLabel("Checkbox ugroups", "[data-test=artifact-form-element]").within(() => {
            checkBoxWithLabel("Project administrators");
            checkBoxWithLabel("Contributors");
        });

        cy.get('[data-test="artifact-submit-button"]').click();
        cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
    });
});

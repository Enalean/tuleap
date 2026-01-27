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
import { getAntiCollisionNamePart } from "@tuleap/cypress-utilities-support";

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

function getCriterionBlock(label: string): Cypress.Chainable<JQuery<HTMLLIElement>> {
    return cy.getContains("[data-test=report-criteria-item]", label).closest("li");
}

function createArtifactWithValues(title: string): void {
    cy.intercept("api/v1/artifacts/*").as("getArtifact");

    cy.log("Create an artifact with all fields");
    cy.getFieldWithLabel("Title").find("[data-test-field-input]").type(title);
    cy.getFieldWithLabel("Description").find("[data-test-cypress=text-area]").type("Description A");
    cy.getFieldWithLabel("String").find("[data-test-field-input]").type("String A");
    cy.getFieldWithLabel("Text").find("[data-test-cypress=text-area]").type("Description A");
    cy.getFieldWithLabel("Integer").find("[data-test-field-input]").type("12");
    cy.getFieldWithLabel("Float").find("[data-test-field-input]").type("5.12");
    cy.getFieldWithLabel("Date")
        .find("[data-test=date-time-date]")
        .setDatepickerValue("2025-02-01");
    cy.getFieldWithLabel("Datetime")
        .find("[data-test=date-time-datetime]")
        .setDatepickerValue("2025-02-01 02:23");
    cy.getFieldWithLabel("Computed").find("[data-test-field-input]").type("13.5");

    cy.getFieldWithLabel("Attachments").then(($field) => {
        cy.wrap($field)
            .find("[data-test=file-field-file-input]")
            .selectFile("cypress/fixtures/attachment.json");
        cy.wrap($field).find("[data-test=file-field-description-input]").type("My JSON attachment");
    });

    cy.getFieldWithLabel("Permissions").then(($field) => {
        cy.wrap($field).find("[data-test=artifact-permission-enable-checkbox]").check();
        cy.wrap($field)
            .find("[data-test=artifact-permissions-selectbox]")
            .select(["Project members", "Integrators"]);
    });

    cy.get("[data-test=link-type-select]").first().select("is Child of");
    cy.get("[data-test=link-field-add-link-input]").click();
    cy.get("[data-test=lazybox-search-field]", { includeShadowDom: true })
        .focus()
        .type("Linked Artifact");
    cy.get("[data-test=new-item-button]").click();
    cy.get("[data-test=artifact-creator-submit]").click();
    cy.wait("@getArtifact");

    cy.get("[data-test=artifact-submit-button]").click();
    cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
}

function createArtifactWithListValues(title: string): void {
    cy.log("Create an artifact with list fields");
    cy.getFieldWithLabel("Title").find("[data-test-field-input]").type(title);
    cy.getFieldWithLabel("Description").find("[data-test-cypress=text-area]").type("Description A");

    cy.getFieldWithLabel("Selectbox static").within(() => {
        cy.searchItemInListPickerDropdown("Dos").click();
    });

    cy.getFieldWithLabel("Selectbox users (members)").within(() => {
        cy.searchItemInListPickerDropdown("ProjectMember").click();
    });

    cy.getFieldWithLabel("Selectbox ugroups").within(() => {
        cy.searchItemInListPickerDropdown("Integrators").click();
    });

    cy.getFieldWithLabel("Radio static").within(() => {
        checkRadioButtonWithLabel("二");
    });

    cy.getFieldWithLabel("Radio users (members)").within(() => {
        checkRadioButtonWithLabel("ProjectMember");
    });

    cy.getFieldWithLabel("Radio ugroups").within(() => {
        checkRadioButtonWithLabel("Integrators");
    });

    cy.getFieldWithLabel("MSB static").within(() => {
        cy.searchItemInListPickerDropdown("Deux").click();
        cy.searchItemInListPickerDropdown("Trois").click();
    });

    cy.getFieldWithLabel("MSB users (members)").within(() => {
        cy.searchItemInListPickerDropdown("ProjectMember").click();
    });

    cy.getFieldWithLabel("MSB ugroups").within(() => {
        cy.searchItemInListPickerDropdown("Integrators").click();
    });

    cy.getFieldWithLabel("Checkbox static").within(() => {
        checkBoxWithLabel("One");
        checkBoxWithLabel("Three");
    });

    cy.getFieldWithLabel("Checkbox users (members)").within(() => {
        checkBoxWithLabel("ProjectMember");
    });

    cy.getFieldWithLabel("Checkbox ugroups").within(() => {
        checkBoxWithLabel("Project administrators");
        checkBoxWithLabel("Contributors");
    });

    cy.get("[data-test=artifact-submit-button]").click();
    cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
}

function updateTrackerReportCriterias(title: string): void {
    cy.intercept({
        method: "POST",
        url: "/plugins/tracker/*",
    }).as("advancedToggle");

    cy.log("Update report in order to have a query for every field of artifact");
    cy.log("Update criteria Title");
    getCriterionBlock("Title")
        .find("[data-test=alphanum-report-criteria]")
        .type(`{selectAll}${title}`);

    cy.log("Update criteria Description");
    getCriterionBlock("Description")
        .find("[data-test=alphanum-report-criteria]")
        .type("Description A");

    cy.log("Update criteria String");
    getCriterionBlock("String").find("[data-test=alphanum-report-criteria]").type("String A");

    cy.log("Update criteria Text");
    getCriterionBlock("Text").find("[data-test=alphanum-report-criteria]").type("Description A");

    cy.log("Update criteria Integer");
    getCriterionBlock("Integer").find("[data-test=integer-report-criteria]").type("12");

    cy.log("Update criteria Float");
    getCriterionBlock("Float").find("[data-test=float-report-criteria]").type("5.12");

    cy.log("Update criteria Date");
    getCriterionBlock("Date").find("[data-test=date-time-date]").type("2025-02-01");

    cy.log("Update criteria Datetime");
    getCriterionBlock("Datetime").find("[data-test=date-time-datetime]").type("2025-02-01");

    cy.log("Update criteria Attachments");
    getCriterionBlock("Attachments")
        .find("[data-test=file-report-criteria]")
        .type("My JSON attachment");

    cy.log("Update criteria Permissions");
    getCriterionBlock("Permissions")
        .find("[data-test=tracker-report-criteria-advanced-toggle]")
        .click();
    cy.wait("@advancedToggle");
    getCriterionBlock("Permissions")
        .find("[data-test=permissions-report-criteria][multiple]")
        .select(["Project members", "Integrators"]);

    cy.log("Search ");
    cy.get("[data-test=submit-report-search]").click();
    cy.get("[data-test=number-of-matching-artifacts]").should("contain", 1);
}

function updateTrackerReportListCriterias(title: string): void {
    cy.intercept({
        method: "POST",
        url: "/plugins/tracker/*",
    }).as("advancedToggle");

    cy.log("Update report in order to have a query for every field of artifact");
    cy.log("Update criteria Title");
    getCriterionBlock("Title")
        .find("[data-test=alphanum-report-criteria]")
        .type(`{selectAll}${title}`);

    cy.log("Update criteria Selectbox static");
    getCriterionBlock("Selectbox static").find("[data-test=list-report-criteria]").select("Dos");

    cy.log("Update criteria Selectbox users");
    getCriterionBlock("Selectbox users")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Selectbox ugroups");
    getCriterionBlock("Selectbox ugroups")
        .find("[data-test=list-report-criteria]")
        .select("Integrators");

    cy.log("Update criteria Radio static");
    getCriterionBlock("Radio static").find("[data-test=list-report-criteria]").select("二");

    cy.log("Update criteria Radio users");
    getCriterionBlock("Radio users (members)")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Radio ugroups");
    getCriterionBlock("Radio ugroups")
        .find("[data-test=list-report-criteria]")
        .select("Integrators");

    cy.log("Update criteria MSB static");
    getCriterionBlock("MSB static")
        .find("[data-test=tracker-report-criteria-advanced-toggle]")
        .click();
    cy.wait("@advancedToggle");
    getCriterionBlock("MSB static")
        .find("[data-test=list-report-criteria][multiple]")
        .select(["Deux", "Trois"]);

    cy.log("Update criteria MSB users");
    getCriterionBlock("MSB users (members)")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria MSB ugroups");
    getCriterionBlock("MSB ugroups").find("[data-test=list-report-criteria]").select("Integrators");

    cy.log("Update criteria Checkbox static");
    getCriterionBlock("Checkbox static")
        .find("[data-test=tracker-report-criteria-advanced-toggle]")
        .click();
    cy.wait("@advancedToggle");
    getCriterionBlock("Checkbox static")
        .find("[data-test=list-report-criteria][multiple]")
        .select(["One", "Three"]);

    cy.log("Update criteria Checkbox users");
    getCriterionBlock("Checkbox users (members)")
        .find("[data-test=list-report-criteria]")
        .select("ProjectMember (ProjectMember)");

    cy.log("Update criteria Checkbox ugroups");
    getCriterionBlock("Checkbox ugroups")
        .find("[data-test=tracker-report-criteria-advanced-toggle]")
        .click();
    cy.wait("@advancedToggle");
    getCriterionBlock("Checkbox ugroups")
        .find("[data-test=list-report-criteria][multiple]")
        .select(["Project administrators", "Contributors"]);

    cy.log("Search ");
    cy.get("[data-test=submit-report-search]").click();
    cy.get("[data-test=number-of-matching-artifacts]").should("contain", 1);
}

describe(`Tracker Report`, () => {
    it(`Can submit an artifact with some fields`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("tracker-report", "Trackers");
        cy.get("[data-test=tracker-link]").first().click();
        cy.get("[data-test=new-artifact]").click();
        const artifact_title = "Title " + getAntiCollisionNamePart();
        createArtifactWithValues(artifact_title);
        updateTrackerReportCriterias(artifact_title);
    });

    it(`Can submit an artifact with list fields`, function () {
        cy.projectMemberSession();
        cy.visitProjectService("tracker-report", "Trackers");
        cy.get("[data-test=tracker-link]").last().click();
        cy.get("[data-test=new-artifact]").click();
        const artifact_title = "Title " + getAntiCollisionNamePart();
        createArtifactWithListValues(artifact_title);
        updateTrackerReportListCriterias(artifact_title);
    });
});

/*
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

function createTrackerFromXML(tracker_title: string): void {
    cy.get("[data-test=new-tracker-creation]").click();
    cy.get("[data-test=template-xml-description]").click();
    cy.get("[data-test=tracker-creation-xml-file-selector]").selectFile(
        "./_fixtures/Tracker_from_msb_to_sb.xml",
    );
    cy.get("[data-test=button-next]").click();
    cy.get("[data-test=tracker-name-input]").type("{selectAll}" + tracker_title);
    cy.get("[data-test=button-create-my-tracker]").click();
    cy.get("[data-test=start-using-tracker]").click();
}

function createArtifact(artifact_title: string): void {
    cy.get("[data-test=new-artifact]").click();
    cy.intercept("api/v1/artifacts/*").as("getArtifact");
    cy.getFieldWithLabel("Title").find("[data-test-field-input]").type(artifact_title);
    cy.getFieldWithLabel("List").within(() => {
        cy.searchItemInListPickerDropdown("A").click();
        cy.searchItemInListPickerDropdown("C").click();
    });

    cy.get("[data-test=artifact-submit-button]").click();
    cy.get("[data-test=feedback]").contains("Artifact Successfully Created ");
}

function switchFieldToSelectBox(): void {
    cy.get("[data-test=link-to-current-tracker-administration]").click({ force: true });
    // eslint-disable-next-line cypress/no-force -- edit button is shown only on CSS :hover
    cy.getContains("[data-test=tracker-admin-field]", "List")
        .find("[data-test=edit-field]")
        .click({ force: true });
    cy.get("[data-test=list-change-type-sb]").click();
}

function assertListFieldStillDisplayTheTwoValues(artifact_title: string): void {
    cy.contains("[data-test=tracker-report-table-results-artifact]", artifact_title).within(() => {
        cy.contains("[data-column-name=list]", "A, C");
    });
}

function assertArtifactIsNotDuplicated(artifact_title: string): void {
    cy.get("[data-test=tracker-report-table-results-artifact]")
        .filter(`:contains("${artifact_title}")`)
        .should("have.length", 1);
}

describe("Table report oddities", () => {
    it("Table report lists 2 values when an artifact got a conversion from MultiSelectBox to SelectBox", () => {
        cy.projectAdministratorSession();
        cy.visitProjectService("from-msb-to-sb", "Trackers");

        const tracker_title = "Tracker " + getAntiCollisionNamePart();
        createTrackerFromXML(tracker_title);

        const artifact_title = "Title " + getAntiCollisionNamePart();
        createArtifact(artifact_title);

        switchFieldToSelectBox();

        cy.visitProjectService("from-msb-to-sb", "Trackers");
        cy.getContains("[data-test=tracker-link]", tracker_title).click();

        assertListFieldStillDisplayTheTwoValues(artifact_title);
        assertArtifactIsNotDuplicated(artifact_title);
    });
});

/*
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

describe("TTM shortcuts", () => {
    before(() => {
        cy.clearSessionCookie();
        cy.projectMemberLogin();
        cy.visitProjectService("test-management-project", "Test Management");
    });

    beforeEach(() => {
        cy.preserveSessionCookies();
        // eslint-disable-next-line cypress/require-data-selectors
        cy.get("body").as("body");
    });

    context("In test campaigns list page", () => {
        it("focuses search field", () => {
            cy.get("@body").type("f");
            cy.focused().should("have.attr", "data-test", "search-filter");
        });
    });

    context("In test campaign page", () => {
        before(() => {
            cy.contains("A test campaign").click();
            cy.contains("First test case");
        });

        it("allows user to navigate in tests list using its keyboard", () => {
            cy.get("@body").type("l{home}");
            cy.focused().should("contain", "First test case");

            cy.get("@body").type("l{downarrow}{downarrow}");
            cy.focused().should("contain", "Third test case");

            cy.get("@body").type("{end}{downarrow}");
            cy.focused().should("contain", "First test case");
        });

        it("filters remaining tests with `r` key shortcut", () => {
            cy.contains("First test case").click();
            cy.get("[data-test=mark-test-as-passed]").click();
            cy.get("[data-test=current-test").should("have.class", "passed");

            cy.get("@body").type("r");
            cy.get("[data-test=campaign-tests-list]")
                .as("tests-list")
                .should("not.contain", "First test case");

            cy.get("[data-test=mark-test-as-notrun]").click();
            cy.get("@tests-list").should("contain", "First test case");
        });

        it("opens `Select tests` modal", () => {
            cy.get("@body").type("e");
            cy.get("[data-test=close-select-tests-modal]").click();
        });
    });
});

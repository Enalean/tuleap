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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 */

describe("Kanban for the Agile Dashboard service", () => {
    before(function () {
        cy.clearCookie("__Host-TULEAP_session_hash");
    });

    it(`Project administrator can start Kanban`, function () {
        cy.ProjectAdministratorLogin();
        cy.visitProjectService("kanban-project", "Agile Dashboard");

        cy.get("[data-test=start-kanban]").click();
        cy.get("[data-test=feedback]").contains("Kanban successfully activated.", {
            timeout: 20000,
        });

        cy.get("[data-test=go-to-kanban]").first().click();

        cy.get("[data-test=kanban-column-label]").then(($column_label) => {
            expect($column_label).to.contain("To be done");
        });
        // Save kanban URL for later tests
        cy.location()
            .then((location) => location.href)
            .debug()
            .as("kanban_url");
    });

    context("As Project member", function () {
        beforeEach(function () {
            cy.projectMemberLogin();
            cy.visit(this.kanban_url);
            cy.server();
        });

        it(`I can manipulate cards`, function () {
            cy.route("POST", "/api/v1/kanban_items").as("post_kanban_item");

            cy.get("[data-test=kanban-column]")
                .first()
                .within(() => {
                    cy.get("[data-test=add-in-place-button]").click({ force: true });

                    cy.get("[data-test=add-in-place-label-input]")
                        .clear()
                        .type("Think about my revenge");

                    cy.get("[data-test=add-in-place-submit]").first().click({ force: true });
                    cy.wait("@post_kanban_item");

                    cy.get("[data-test=add-in-place-label-input]").clear().type("Still speedin'");

                    cy.get("[data-test=add-in-place-submit]").first().click({ force: true });
                    cy.wait("@post_kanban_item");

                    cy.get("[data-test=add-in-place-label-input]").clear().type("i30 Namyang");

                    cy.get("[data-test=add-in-place-submit]").first().click({ force: true });
                    cy.wait("@post_kanban_item");
                });

            cy.get("body").type("{esc}");

            cy.contains("[data-test=kanban-column-header-wip-count]", "3");

            // Move the second card to the top
            cy.get("[data-test=tuleap-simple-field-name]").spread(
                (first_card, second_card, third_card) => {
                    expect(first_card).to.contain("Think about my revenge");
                    expect(second_card).to.contain("Still speedin'");
                    expect(third_card).to.contain("i30 Namyang");
                }
            );

            cy.get("[data-test=kanban-item]")
                .eq(1)
                .within(() => {
                    cy.get("[data-test=kanban-item-content-move-to-top]").click({ force: true });
                });

            cy.get("[data-test=tuleap-simple-field-name]").spread(
                (first_card, second_card, third_card) => {
                    expect(first_card).to.contain("Still speedin'");
                    expect(second_card).to.contain("Think about my revenge");
                    expect(third_card).to.contain("i30 Namyang");
                }
            );

            // Move the first card to the bottom
            cy.get("[data-test=kanban-item]")
                .first()
                .within(() => {
                    cy.get("[data-test=kanban-item-content-move-to-bottom]").click({ force: true });
                });

            cy.get("[data-test=tuleap-simple-field-name]").spread(
                (first_card, second_card, third_card) => {
                    expect(first_card).to.contain("Think about my revenge");
                    expect(second_card).to.contain("i30 Namyang");
                    expect(third_card).to.contain("Still speedin'");
                }
            );

            // expand the first card
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-down"
            );
            cy.get("[data-test=kanban-item-content-expand-collapse]")
                .first()
                .click({ force: true });
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-up"
            );
            // The single card expand should not be persisted after reload
            cy.reload();
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-down"
            );

            // change the display view
            cy.get("[data-test=kanban-header-detailed-toggler]")
                .check({ force: true })
                .should("be.checked");
            // The display of the cards should be persisted after reload
            cy.reload();
            cy.get("[data-test=kanban-header-detailed-toggler]").should("be.checked");

            // Filter card by name
            cy.get("[data-test=kanban-item]").should("have.length", 3);
            cy.get("[data-test=kanban-header-search]").type("speedin");
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.contains("[data-test=tuleap-simple-field-name]", "Still speedin'");

            cy.get("[data-test=kanban-header-search]").clear();
            cy.get("[data-test=kanban-item]").should("have.length", 3);

            // Collapse the first card
            cy.get("[data-test=kanban-item-content-expand-collapse]")
                .first()
                .click({ force: true });
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-down"
            );
        });
    });
});

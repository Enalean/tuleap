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
    context("As Project Admin", function () {
        beforeEach(function () {
            cy.ProjectAdministratorLogin();
            cy.visit(this.kanban_url);
        });
        it(`can rename the kanban`, function () {
            cy.get("[data-test=kanban-header-title]").contains("Activities");
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.get("[data-test=edit-kanban-label-form]").within(() => {
                cy.get("[data-test=input-kanban-label]").clear().type("Brabus");
                cy.root().submit();
            });
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");
            cy.contains("[data-test=kanban-header-title]", "Brabus");
            // The name of the kanban should be persisted after reload.
            cy.reload();
            cy.contains("[data-test=kanban-header-title]", "Brabus");
        });
        it(`can add columns`, function () {
            cy.get("[data-test=kanban-column-header").should("have.length", 3);
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.get("[data-test=edit-kanban-column]").should("have.length", 3);
            cy.get("[data-test=edit-modal-add-column-button]").click();
            cy.get("[data-test=edit-kanban-add-column-form]").within(() => {
                cy.get("[data-test=edit-kanban-add-column-form-input]").clear().type("B 35 S");
                cy.root().submit();
            });
            cy.get("[data-test=edit-kanban-column").should("have.length", 4);
            cy.get("[data-test=edit-kanban-column-b_35_s").should("contain", "B 35 S");
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");
            // The number of columns should be the same after reload + the name of the last column should not change
            cy.reload();
            cy.get("[data-test=kanban-column-header").should("have.length", 4);
            cy.get("[data-test=kanban-column-b_35_s]").should("contain", "B 35 S");
        });
        it(`can edit the last column of kanban`, function () {
            cy.get("[data-test=kanban-column-header").should("have.length", 4);
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.get("[data-test=edit-kanban-column").should("have.length", 4);
            cy.get("[data-test=edit-kanban-column-button-b_35_s]").click();
            cy.get("[data-test=edit-kanban-edit-column-container-form]").within(() => {
                cy.get("[data-test=edit-kanban-edit-column-container-form-input]")
                    .clear()
                    .type("Bullit");
                cy.root().submit();
            });
            cy.get("[data-test=edit-kanban-column").should("have.length", 4);
            cy.get("[data-test=edit-kanban-column-bullit").should("contain", "Bullit");
            // The number of columns should be the same after reload + the name of the last column should not change
            cy.reload();
            cy.get("[data-test=kanban-column-header").should("have.length", 4);
            cy.get("[data-test=kanban-column-header").last().should("contain", "Bullit");
        });
        it(`can remove the last column of kanban`, function () {
            cy.get("[data-test=kanban-column-header").should("have.length", 4);
            cy.get("[data-test=kanban-header-edit-button]").click();
            cy.get("[data-test=edit-kanban-column").should("have.length", 4);
            cy.get("[data-test=edit-kanban-column-bullit").should("contain", "Bullit");
            cy.get("[data-test=edit-modal-remove-column-button-bullit").click();
            cy.get("[data-test=edit-kanban-column-button-confirm-deletion-bullit").click();
            cy.get("[data-test=edit-kanban-column").should("have.length", 3);
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");
            // The number of columns should be the same after reload + the last column must not be Bullit
            cy.reload();
            cy.get("[data-test=kanban-column-header").should("have.length", 3);
            cy.get("[data-test=kanban-column-review]").should("contain", "Review");
        });
        it(`reorders column`, function () {
            cy.get("[data-test=kanban-column-header").should("have.length", 3);
            cy.get("[data-test=kanban-header-edit-button]").click();

            cy.get("[data-test=edit-kanban-column]").should("have.length", 3);
            cy.get("[data-test=edit-kanban-column-label]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("In progress");
                    expect(third_column).to.contain("Review");
                }
            );
            cy.dragAndDrop(
                "[data-test=edit-kanban-column-review]",
                "[data-test=edit-kanban-column-in_progress]",
                "top"
            );
            cy.get("[data-test=edit-kanban-column]").should("have.length", 3);
            cy.get("[data-test=edit-kanban-column-label]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                }
            );

            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");
            cy.get("[data-test=kanban-column-header]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                }
            );
            // The order of the columns should not change after reload
            cy.reload();
            cy.get("[data-test=kanban-column-header]").spread(
                (first_column, second_column, third_column) => {
                    expect(first_column).to.contain("To be done");
                    expect(second_column).to.contain("Review");
                    expect(third_column).to.contain("In progress");
                }
            );
        });
    });
    context("As Project member", function () {
        beforeEach(function () {
            cy.projectMemberLogin();
            cy.visit(this.kanban_url);
        });

        it(`I can manipulate cards`, function () {
            cy.get("[data-test=kanban-column-to_be_done]")
                .first()
                .within(() => {
                    cy.get("[data-test=add-in-place]").invoke("css", "pointer-events", "all");

                    cy.get("[data-test=add-in-place-button]").click();
                    cy.get("[data-test=add-in-place-label-input]")
                        .clear()
                        .type("Think about my revenge");
                    cy.get("[data-test=add-in-place-submit]").first().click();

                    cy.get("[data-test=add-in-place-label-input]")
                        .should("not.be.disabled")
                        .clear()
                        .type("Still speedin'");

                    cy.get("[data-test=add-in-place-submit]").first().click();

                    cy.get("[data-test=add-in-place-label-input]")
                        .should("not.be.disabled")
                        .clear()
                        .type("i30 Namyang");

                    cy.get("[data-test=add-in-place-submit]").first().click();
                });

            // need to escape for drag and drop only works on body and global body seems erased by angular
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.contains("[data-test=kanban-column-header-wip-count]", "3");
        });

        it(`I can move second card to top`, function () {
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
                    cy.get("[data-test=kanban-item-content-move-to-top]").click();
                });

            cy.get("[data-test=tuleap-simple-field-name]").spread(
                (first_card, second_card, third_card) => {
                    expect(first_card).to.contain("Still speedin'");
                    expect(second_card).to.contain("Think about my revenge");
                    expect(third_card).to.contain("i30 Namyang");
                }
            );
        });

        it(`I can move the first card to the bottom`, function () {
            cy.get("[data-test=kanban-item]")
                .first()
                .within(() => {
                    cy.get("[data-test=kanban-item-content-move-to-bottom]").click();
                });

            cy.get("[data-test=tuleap-simple-field-name]").spread(
                (first_card, second_card, third_card) => {
                    expect(first_card).to.contain("Think about my revenge");
                    expect(second_card).to.contain("i30 Namyang");
                    expect(third_card).to.contain("Still speedin'");
                }
            );
        });

        it(`I can expand the first card`, function () {
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-down"
            );

            // To avoid force click = true
            cy.get("[data-test=kanban-item-content-expand-collapse]").invoke("css", "height", 10);
            cy.get("[data-test=kanban-item-content-expand-collapse]").first().click();
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
        });

        it(`I can change the display view`, function () {
            cy.get("[data-test=kanban-header-detailed-toggler]").invoke(
                "css",
                "visibility",
                "visible"
            );
            cy.get("[data-test=kanban-header-detailed-toggler]").check().should("be.checked");
            // The display of the cards should be persisted after reload
            cy.reload();
            cy.get("[data-test=kanban-header-detailed-toggler]").should("be.checked");
        });

        it(`I can filter cards`, function () {
            cy.get("[data-test=kanban-item]").should("have.length", 3);
            cy.get("[data-test=kanban-header-search]").type("speedin");
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.contains("[data-test=tuleap-simple-field-name]", "Still speedin'");

            cy.get("[data-test=kanban-header-search]").clear();
            cy.get("[data-test=kanban-item]").should("have.length", 3);

            // Collapse the first card
            cy.get("[data-test=kanban-item-content-expand-collapse]").invoke("css", "height", 10);
            cy.get("[data-test=kanban-item-content-expand-collapse]").first().click();
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-down"
            );
        });
    });
});

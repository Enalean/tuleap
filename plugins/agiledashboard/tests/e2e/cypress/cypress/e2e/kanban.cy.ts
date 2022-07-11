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
    context("As Project Admin", function () {
        before(function () {
            cy.clearSessionCookie();
            cy.projectAdministratorLogin();
            cy.preserveSessionCookies();
        });

        it(`kanban administration modal still works`, function () {
            cy.log("administrator can reorder column");
            cy.visitProjectService("kanban-project", "Agile Dashboard");
            cy.get('[data-test="go-to-kanban"]').click();
            cy.get("[data-test=kanban-header-edit-button]").click();
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
        before(function () {
            cy.clearSessionCookie();
            cy.projectMemberLogin();
            cy.preserveSessionCookies();

            cy.getProjectId("kanban-project").as("project_id");
        });

        it(`I can use the kanban`, function () {
            cy.visitProjectService("kanban-project", "Agile Dashboard");
            cy.get('[data-test="go-to-kanban"]').click();

            cy.log("I can move cards");
            cy.get("[data-test=kanban-column-to_be_done]").within(() => {
                cy.get("[data-test=tuleap-simple-field-name]").spread(
                    (first_card, second_card, third_card) => {
                        cy.wrap(first_card.innerText).as("first_title");
                        cy.wrap(second_card.innerText).as("second_title");
                        cy.wrap(third_card.innerText).as("third_title");

                        cy.get("[data-test=kanban-item]")
                            .eq(1)
                            .within(() => {
                                cy.get("[data-test=kanban-item-content-move-to-top]").click();
                            });

                        cy.get("[data-test=tuleap-simple-field-name]").spread(
                            (first_card, second_card, third_card) => {
                                expect(first_card.innerText).to.equal(this.second_title);
                                expect(second_card.innerText).to.equal(this.first_title);
                                expect(third_card.innerText).to.equal(this.third_title);
                            }
                        );
                    }
                );
            });

            cy.log(`I can expand cards`);
            // To avoid force click = true
            cy.get("[data-test=kanban-item-content-expand-collapse]").invoke("css", "height", 10);
            cy.get("[data-test=kanban-item-content-expand-collapse]").first().click();
            cy.get("[data-test=kanban-item-content-expand-collapse-icon]").should(
                "have.class",
                "fa-angle-up"
            );

            cy.log(`I can filter cards`);
            cy.get("[data-test=kanban-item]").its("length").should("be.gte", 4);
            cy.get("[data-test=kanban-header-search]").type("speedin");
            cy.get("[data-test=kanban-item]").should("have.length", 1);
            cy.contains("[data-test=tuleap-simple-field-name]", "Still speedin'");

            cy.get("[data-test=kanban-header-search]").clear();
            cy.get("[data-test=kanban-item]").its("length").should("be.gte", 4);

            cy.log(`I can check that WIP limit is reached`);
            cy.get("[data-test=kanban-column-to_be_done]").within(() => {
                cy.get("[data-test=kanban-column-header-wip-count]").contains("3");
                cy.get("[data-test=kanban-column-header-wip-limit]").should(
                    "have.class",
                    "tlp-badge-warning"
                );
            });

            cy.get("[data-test=kanban-column-in_progress]").within(() => {
                cy.get("[data-test=kanban-column-header-wip-count]").contains("1");
                cy.get("[data-test=kanban-column-header-wip-limit]").should(
                    "not.have.class",
                    "tlp-badge-warning"
                );
            });

            cy.log(`I can drag and drop cards`);

            const now = Date.now();
            const drag_label = `drag${now}`;
            const drop_label = `drop${now}`;
            cy.get("[data-test=kanban-column-backlog]").within(() => {
                cy.get("[data-test=add-in-place]").invoke("css", "pointer-events", "all");

                cy.get("[data-test=add-in-place-button]").click();
                cy.get("[data-test=add-in-place-label-input]").clear().type(drag_label);
                cy.get("[data-test=add-in-place-submit]").first().click();
            });

            cy.get("[data-test=kanban-column-review]").within(() => {
                cy.get("[data-test=add-in-place]").invoke("css", "pointer-events", "all");

                cy.get("[data-test=add-in-place-button]").click();
                cy.get("[data-test=add-in-place-label-input]").clear().type(drop_label);
                cy.get("[data-test=add-in-place-submit]").first().click();
            });

            cy.dragAndDrop(
                `[data-test=kanban-item-content-${drag_label}]`,
                `[data-test=kanban-item-content-${drop_label}]`,
                "top"
            );

            // need to escape for drag and drop only works on body and global body seems erased by angular
            // eslint-disable-next-line cypress/require-data-selectors
            cy.get("body").type("{esc}");

            cy.get("[data-test=kanban-column-review]").within(() => {
                cy.get("[data-test=kanban-item]").its("length").should("be.gte", 1);
            });
        });

        it("can not access to administration page", function () {
            cy.visit("/plugins/agiledashboard/?group_id=" + this.project_id + "&action=admin");
            cy.get("[data-test=main-content]").contains("Kanban");
        });
    });
});

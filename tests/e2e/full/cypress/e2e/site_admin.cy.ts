/*
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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

describe("Site admin", function () {
    context("Platform administrator", function () {
        it("can search user on admin page", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=global-admin-search-user]").type("heisenberg{enter}");
            cy.get("[data-test=user-login]").should("have.value", "Heisenberg");
        });

        it("Can send preview of mass mail", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=mass-mail]").click();

            cy.get("[data-test=massmail-subject]").type("My custom mail");
            cy.window().then((win) => {
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                win.CKEDITOR.instances.mail_message.setData(
                    `Dear User,<br><b>Important information</b><br>Sincerely,<br>Your support team`,
                );
            });
            cy.get("[data-test=massmail-preview-destination-external]").type(
                "external-user@example.com",
            );
            cy.get("[data-test=submit-preview-button]").click();

            cy.assertEmailWithContentReceived(
                "external-user@example.com",
                `<strong>Important information`,
            );
        });

        it("Can send a mass emailing", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=mass-mail]").click();
            cy.get("[data-test=massmail-destination]").select("sfadmin");

            cy.get("[data-test=massmail-subject]").type("A mass mail");
            cy.window().then((win) => {
                // eslint-disable-next-line @typescript-eslint/ban-ts-comment
                // @ts-ignore
                win.CKEDITOR.instances.mail_message.setData("MassMailContent");
            });

            cy.get("[data-test=massmail-send-button]").click();
            cy.get("[data-test=massmail-warning]").contains("users will receive this email.");
        });

        it("Can see statistics frequencies", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=statistics]").click({ force: true });

            cy.log("Check that image is rendered by asserting that its size is > 0");
            cy.get("[data-test=graph-frequencies]")
                .should("be.visible")
                .and(($img) => {
                    // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
                    const image_element: HTMLImageElement = $img[0] as HTMLImageElement;
                    expect(image_element.naturalWidth).to.be.gt(0);
                });
        });
        it("Can see statistics disk usage", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=statistics]").click({ force: true });
            cy.get("[data-test=disk-usage-by-services]").click({ force: true });
            cy.get("[data-test=services-usages]").find("tr").should("have.length.at.least", 2);

            cy.get("[data-test=disk-usage-by-projects]").click({ force: true });
            cy.get("[data-test=disk-usage-project]").find("tr").should("have.length.at.least", 2);

            cy.get("[data-test=global-usage]").click({ force: true });
            cy.get("[data-test=global-usage]").contains("Global usage");
        });
        it("Can export data", function () {
            cy.siteAdministratorSession();
            cy.visit("/");
            cy.get("[data-test=platform-administration-link]").click();
            cy.get("[data-test=statistics]").click({ force: true });
            cy.get("[data-test=data-export]").click();
            cy.intercept("*service_usage*").as("export_csv");

            const download_folder = Cypress.config("downloadsFolder");
            const today = new Date().toISOString().slice(0, 10);
            const last_year_date = new Date();
            last_year_date.setFullYear(last_year_date.getFullYear() - 1);
            const last_year = last_year_date.toISOString().slice(0, 10);

            cy.get("[data-test=export-csv-button]")
                .click()
                .then(() => {
                    cy.get("[data-test=services-usage-start-date]")
                        .invoke("val")
                        .then((last_month) => {
                            cy.readFile(
                                download_folder + `/services_usage_${last_month}_${today}.csv`,
                            ).should("exist");
                        });
                });

            cy.get("[data-test=scm-statistics]").click();
            cy.get("[data-test=scm-export-button]")
                .click()
                .then(() => {
                    cy.readFile(download_folder + `/scm_stats_${last_year}_${today}.csv`).should(
                        "exist",
                    );
                });

            cy.get("[data-test=usage-progress]").click();
            cy.get("[data-test=usage-progress-button]")
                .click()
                .then(() => {
                    cy.readFile(download_folder + "/Tuleap_progress_data.csv").should("exist");
                });
        });
    });
});

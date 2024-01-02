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
    });
});

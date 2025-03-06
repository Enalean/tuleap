/*
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
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

import * as quotedPrintable from "quoted-printable";
import type { ConditionPredicate } from "./commands-type-definitions";

Cypress.Commands.add(
    "assertEmailWithContentReceived",
    (email_address: string, specific_content_of_email: string): void => {
        const reloadCallback = (): void => {
            cy.log("Checking emails again...");
        };
        const conditionCallback: ConditionPredicate = (number_of_attempts, max_attempts) => {
            cy.log(
                `Check that email address "${email_address}" has received an email containing "${specific_content_of_email}" (attempt ${number_of_attempts}/${max_attempts})`,
            );
            return getEmailsReceivedBy(email_address).then((response) => {
                if (response.body.items.length === 0) {
                    return false;
                }
                const last_received_email = response.body.items[0];
                return quotedPrintable
                    .decode(last_received_email.Content.Body)
                    .includes(specific_content_of_email);
            });
        };
        cy.reloadUntilCondition(
            reloadCallback,
            conditionCallback,
            `Email address "${email_address}" did not receive an email containing "${specific_content_of_email}"`,
        );
    },
);

Cypress.Commands.add(
    "assertEmailReceivedWithAttachment",
    (from_email_address: string, attachment_type: string): void => {
        const reloadCallback = (): void => {
            cy.log("Checking emails again...");
        };
        const conditionCallback: ConditionPredicate = (number_of_attempts, max_attempts) => {
            cy.log(
                `Check that email address "${from_email_address}" has sent an attachment (attempt ${number_of_attempts}/${max_attempts})`,
            );
            return getEmailsSentBy(from_email_address).then((response) => {
                if (response.body.items.length === 0) {
                    return false;
                }
                const last_received_email = response.body.items[0];

                for (const mime_part of last_received_email.MIME.Parts) {
                    const content_disposition =
                        (mime_part.Headers["Content-Disposition"] ?? [])[0] ?? "";
                    const content_type = (mime_part.Headers["Content-Type"] ?? [])[0] ?? "";
                    if (
                        content_disposition.startsWith("attachment") &&
                        content_type.startsWith(attachment_type)
                    ) {
                        return true;
                    }
                }

                return false;
            });
        };
        cy.reloadUntilCondition(
            reloadCallback,
            conditionCallback,
            `Email address "${from_email_address}" did not sent an email with an attachment`,
        );
    },
);

Cypress.Commands.add(
    "assertNotEmailWithContentReceived",
    (email_address: string, specific_content_of_email: string): void => {
        const reloadCallback = (): void => {
            cy.log("Checking emails again...");
        };
        const conditionCallback: ConditionPredicate = (number_of_attempts, max_attempts) => {
            cy.log(
                `Check that email address "${email_address}" did not receive email containing "${specific_content_of_email}" (attempt ${number_of_attempts}/${max_attempts})`,
            );
            return getEmailsReceivedBy(email_address).then((response) => {
                if (!response.body.items) {
                    return false;
                }
                for (const email_item of response.body.items) {
                    const decoded_body = quotedPrintable.decode(email_item.Content.Body);
                    if (decoded_body.includes(specific_content_of_email)) {
                        expect(decoded_body).not.contains(specific_content_of_email);
                        return true;
                    }
                }
                return true;
            });
        };
        cy.reloadUntilCondition(
            reloadCallback,
            conditionCallback,
            `Email address "${email_address}" did not receive any email`,
        );
    },
);

Cypress.Commands.add("deleteAllMessagesInMailbox", (): void => {
    cy.request({
        method: "DELETE",
        url: "http://mailhog:8025/api/v1/messages",
        headers: {
            accept: "application/json",
        },
    }).then((response) => {
        expect(response.status).to.eq(200);
    });
});

interface Parts {
    Body: string;
    Headers: {
        "Content-Type"?: Array<string>;
        "Content-Disposition"?: Array<string>;
    };
}
interface EmailItem {
    readonly Content: {
        readonly Body: string;
    };
    readonly MIME: {
        Parts: Array<Parts>;
    };
}

interface MailResponse {
    readonly items: ReadonlyArray<EmailItem>;
}

function getEmailsReceivedBy(
    email_address: string,
): Cypress.Chainable<Cypress.Response<MailResponse>> {
    return cy.request({
        method: "GET",
        url: "http://mailhog:8025/api/v2/search?kind=to&query=" + encodeURIComponent(email_address),
        headers: {
            accept: "application/json",
        },
    });
}

function getEmailsSentBy(email_address: string): Cypress.Chainable<Cypress.Response<MailResponse>> {
    return cy.request({
        method: "GET",
        url:
            "http://mailhog:8025/api/v2/search?kind=from&query=" +
            encodeURIComponent(email_address),
        headers: {
            accept: "application/json",
        },
    });
}

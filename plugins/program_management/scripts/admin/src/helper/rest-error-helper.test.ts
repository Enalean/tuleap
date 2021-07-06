/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

import { resetRestErrorAlert, setRestErrorMessage } from "./rest-error-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("RestErrorHelper", () => {
    describe("resetRestErrorAlert", () => {
        it("Error is thrown When alert element does not exist", () => {
            expect(() => resetRestErrorAlert(createDocument())).toThrowError(
                "Rest Error Alert does not exist"
            );
        });
        it("Text error is reset and hide When alert element exist", () => {
            const alert_element = document.createElement("div");
            alert_element.id = "program-management-add-team-error-rest";
            alert_element.textContent = "Error";

            const doc = getDocumentWithAlertElement(alert_element);
            resetRestErrorAlert(doc);

            expect(alert_element.textContent).toEqual("");
            expect(alert_element.classList).toContain(
                "program-management-add-team-error-rest-not-show"
            );
        });
    });
    describe("setRestErrorMessage", () => {
        it("Error is thrown When alert element does not exist", () => {
            expect(() => setRestErrorMessage(createDocument(), "error")).toThrowError(
                "Rest Error Alert does not exist"
            );
        });
        it("Text error is set and shown When alert element exist", () => {
            const alert_element = document.createElement("div");
            alert_element.id = "program-management-add-team-error-rest";
            alert_element.classList.add("program-management-add-team-error-rest-not-show");

            const doc = getDocumentWithAlertElement(alert_element);
            setRestErrorMessage(doc, "error");

            expect(alert_element.textContent).toEqual("error");
            expect(alert_element.classList).not.toContain(
                "program-management-add-team-error-rest-not-show"
            );
        });
    });
});

function getDocumentWithAlertElement(alert_element: HTMLDivElement): Document {
    const doc = createDocument();

    doc.body.appendChild(alert_element);

    return doc;
}

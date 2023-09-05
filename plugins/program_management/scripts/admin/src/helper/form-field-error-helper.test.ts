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

import { resetErrorOnSelectField, setErrorMessageOnSelectField } from "./form-field-error-helper";

const createDocument = (): Document => document.implementation.createHTMLDocument();

describe("form-field-error-helper", () => {
    describe("resetErrorOnSelectField", () => {
        it("Throw error When there are no parent", () => {
            expect(() => resetErrorOnSelectField(document.createElement("select"))).toThrow(
                "Parent of selector does not exist",
            );
        });
        it("When parent exists, Then class error is removed", () => {
            const doc = createDocument();
            doc.body.classList.add("tlp-form-element-error");
            const selector = document.createElement("select");
            doc.body.appendChild(selector);

            resetErrorOnSelectField(selector);
            expect(doc.body.classList).not.toContain("tlp-form-element-error");
        });
        it("When parent and error message exist, Then class error and error message are removed", () => {
            const doc = createDocument();
            doc.body.classList.add("tlp-form-element-error");
            const error_message = document.createElement("p");
            error_message.textContent = "This field is mandatory";

            const selector = document.createElement("select");
            doc.body.appendChild(selector);
            doc.body.appendChild(error_message);

            resetErrorOnSelectField(selector);

            expect(doc.body.classList).not.toContain("tlp-form-element-error");
            expect(doc.body.childElementCount).toBe(1);
        });
    });
    describe("setErrorMessageOnSelectField", () => {
        it("Throw error When there are no parent", () => {
            expect(() =>
                setErrorMessageOnSelectField(document.createElement("select"), "error message"),
            ).toThrow("Parent of selector does not exist");
        });
        it("When parent exists, Then class error and error message are added", () => {
            const doc = createDocument();
            const selector = document.createElement("select");
            doc.body.appendChild(selector);

            setErrorMessageOnSelectField(selector, "This field is mandatory");

            expect(doc.body.classList).toContain("tlp-form-element-error");
            expect(doc.body.childElementCount).toBe(2);
            expect(doc.body.children[1].textContent).toBe("This field is mandatory");
        });
    });
});

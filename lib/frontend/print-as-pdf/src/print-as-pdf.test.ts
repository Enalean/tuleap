/*
 * Copyright (c) Enalean, 2024 - present. All Rights Reserved.
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

import { describe, it, expect, beforeEach, vi } from "vitest";
import { isFault } from "@tuleap/fault";
import { printAsPdf } from "./print-as-pdf";

const template = {
    id: "abc",
    label: "Alphabet",
    description: "Alphabet template",
    style: "body: { margins: 0; }",
    // eslint-disable-next-line no-template-curly-in-string
    title_page_content: "<h1>${DOCUMENT_TITLE}</h1>",
    // eslint-disable-next-line no-template-curly-in-string
    header_content: "<span>Header content of ${DOCUMENT_TITLE}</span>",
    // eslint-disable-next-line no-template-curly-in-string
    footer_content: "<span>Footer content of ${DOCUMENT_TITLE}</span>",
};

const template_variables = { DOCUMENT_TITLE: "Test document" };

const mocks = vi.hoisted(() => ({ print: vi.fn() }));

vi.mock("print-js", () => ({
    default: mocks.print,
}));

describe("print-as-pdf", () => {
    let printable: HTMLElement;

    beforeEach(() => {
        printable = document.createElement("div");
    });

    it("should print the document with its styles and content", () => {
        printable.insertAdjacentHTML(
            `afterbegin`,
            `
            <div id="document-title-page">Title</div>
            <p>Content</p>
            <div id="document-header"></div>
            <div id="document-footer"></div>
        `,
        );

        const result = printAsPdf(printable, template, template_variables);
        if (!result.isOk()) {
            throw Error("Expected an Ok. Got: " + result.error);
        }

        expect(printable.querySelector("#document-title-page")?.innerHTML).toBe(
            "<h1>Test document</h1>",
        );
        expect(printable.querySelector("#document-header")?.innerHTML).toBe(
            "<span>Header content of Test document</span>",
        );
        expect(printable.querySelector("#document-footer")?.innerHTML).toBe(
            "<span>Footer content of Test document</span>",
        );

        expect(mocks.print).toHaveBeenCalledOnce();

        const print_options = mocks.print.mock.calls[0][0];
        expect(print_options.type).toBe("html");
        expect(print_options.scanStyles).toBe(false);
        expect(print_options.style).toBe(template.style);
    });

    it("Should return a Fault when some header content is defined, but the header container cannot be found", () => {
        printable.insertAdjacentHTML(
            `afterbegin`,
            `
            <h1>Title</h1>
            <p>Content</p>
            <div id="document-footer"></div>
        `,
        );

        const result = printAsPdf(printable, template, template_variables);
        if (!result.isErr()) {
            throw Error("Expected an Err.");
        }

        expect(isFault(result.error)).toBe(true);
        expect(mocks.print).not.toHaveBeenCalled();
    });

    it("Should return a Fault when some footer content is defined, but the footer container cannot be found", () => {
        printable.insertAdjacentHTML(
            `afterbegin`,
            `
            <h1>Title</h1>
            <p>Content</p>
            <div id="document-header"></div>
        `,
        );

        const result = printAsPdf(printable, template, template_variables);
        if (!result.isErr()) {
            throw Error("Expected an Err.");
        }

        expect(isFault(result.error)).toBe(true);
        expect(mocks.print).not.toHaveBeenCalled();
    });

    it("Should return a Fault when the print fails", () => {
        printable.insertAdjacentHTML(
            `afterbegin`,
            `
            <h1>Title</h1>
            <p>Content</p>
            <div id="document-header"></div>
            <div id="document-footer"></div>
        `,
        );

        mocks.print.mockImplementation(() => {
            throw new Error("Nope");
        });

        const result = printAsPdf(printable, template, template_variables);
        if (!result.isErr()) {
            throw Error("Expected an Err.");
        }

        expect(isFault(result.error)).toBe(true);
    });
});

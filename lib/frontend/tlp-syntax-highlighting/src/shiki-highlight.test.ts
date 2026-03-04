/**
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

import type { MockInstance } from "vitest";
import { beforeEach, describe, expect, it, vi } from "vitest";
import { highlighter_promise, syntaxHighlightElement } from "./shiki-highlight";

vi.mock(import("shiki"), async (original_import) => {
    const original = await original_import();
    return {
        ...original,
        createHighlighter: vi.fn().mockResolvedValue({
            codeToHtml: vi.fn(),
            loadLanguage: vi.fn(),
            getLoadedLanguages: vi.fn(),
        }),
    };
});

describe("Shiki", () => {
    let codeToHtml: MockInstance;
    let loadLanguage: MockInstance;
    let getLoadedLanguages: MockInstance;
    let doc: Document;

    beforeEach(async () => {
        vi.resetAllMocks();
        doc = document.implementation.createHTMLDocument();
        const highlighter = await highlighter_promise;
        codeToHtml = highlighter.codeToHtml as unknown as MockInstance;
        loadLanguage = highlighter.loadLanguage as unknown as MockInstance;
        getLoadedLanguages = highlighter.getLoadedLanguages as unknown as MockInstance;

        getLoadedLanguages.mockReturnValueOnce([]);
    });

    it("does the syntax highlighting of an element", async () => {
        const pre_element = doc.createElement("pre");
        const element = doc.createElement("code");
        pre_element.appendChild(element);
        element.classList.add("language-bash");
        const code_content = "echo 'Hello';";
        element.textContent = code_content;

        await syntaxHighlightElement(element);

        expect(loadLanguage).toHaveBeenCalledWith("bash");
        expect(codeToHtml).toHaveBeenCalledWith(
            code_content,
            expect.objectContaining({
                lang: "bash",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
    });

    it("fallback to text if attribute is not present", async () => {
        const pre_element = doc.createElement("pre");
        const element = doc.createElement("code");
        pre_element.appendChild(element);
        const code_content = "Some text";
        element.textContent = code_content;

        await syntaxHighlightElement(element);

        expect(loadLanguage).toHaveBeenCalledWith("text");
        expect(codeToHtml).toHaveBeenCalledWith(
            code_content,
            expect.objectContaining({
                lang: "text",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
    });

    it("fallback to text if language is unknown", async () => {
        const pre_element = doc.createElement("pre");
        const element = doc.createElement("code");
        pre_element.appendChild(element);
        element.classList.add("language-foo");
        const code_content = "bar";
        element.textContent = code_content;

        await syntaxHighlightElement(element);

        expect(loadLanguage).toHaveBeenCalledWith("text");
        expect(codeToHtml).toHaveBeenCalledWith(
            code_content,
            expect.objectContaining({
                lang: "text",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
    });

    it("does not load same language twice", async () => {
        getLoadedLanguages.mockReturnValueOnce(["text"]);

        const pre_element_1 = doc.createElement("pre");
        const element_1 = doc.createElement("code");
        pre_element_1.appendChild(element_1);
        const code_content_1 = "Some text";
        element_1.textContent = code_content_1;

        await syntaxHighlightElement(element_1);

        const pre_element_2 = doc.createElement("pre");
        const element_2 = doc.createElement("code");
        pre_element_2.appendChild(element_2);
        const code_content_2 = "Another text content";
        element_2.textContent = code_content_2;

        await syntaxHighlightElement(element_2);

        expect(loadLanguage).toHaveBeenCalledExactlyOnceWith("text");
        expect(codeToHtml).toHaveBeenCalledTimes(2);
        expect(codeToHtml).toHaveBeenNthCalledWith(
            1,
            code_content_1,
            expect.objectContaining({
                lang: "text",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
        expect(codeToHtml).toHaveBeenNthCalledWith(
            2,
            code_content_2,
            expect.objectContaining({
                lang: "text",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
    });

    it("can highlight tql", async () => {
        const pre_element = doc.createElement("pre");
        const element = doc.createElement("code");
        pre_element.appendChild(element);
        element.classList.add("language-tql");
        const code_content = "SELECT @pretty_title FROM @project = MY_PROJECTS() WHERE @id >= 1";
        element.textContent = code_content;

        await syntaxHighlightElement(element);

        expect(loadLanguage).toHaveBeenCalledWith(
            expect.objectContaining({
                displayName: "Tuleap Query Language",
                name: "tql",
            }),
        );
        expect(codeToHtml).toHaveBeenCalledWith(
            code_content,
            expect.objectContaining({
                lang: "tql",
                themes: { light: "github-light-default", dark: "github-dark-default" },
            }),
        );
    });
});

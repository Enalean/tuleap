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

import { beforeEach, describe, expect, it, vi } from "vitest";
import { syntaxHighlightElement } from "./shiki-highlight";
import * as shiki from "shiki";
import type { BundledLanguage, BundledTheme, HighlighterGeneric } from "shiki";

vi.mock("shiki");

describe("Shiki", () => {
    let doc: Document;
    const codeToHtml = vi.fn();
    const loadLanguage = vi.fn();

    beforeEach(() => {
        vi.resetAllMocks();
        vi.spyOn(shiki, "createHighlighter").mockResolvedValue({
            codeToHtml,
            loadLanguage,
        } as unknown as HighlighterGeneric<BundledLanguage, BundledTheme>);
        doc = document.implementation.createHTMLDocument();
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
});

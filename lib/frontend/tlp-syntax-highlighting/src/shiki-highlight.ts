/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

import type { BundledLanguage, BundledTheme, HighlighterGeneric, SpecialLanguage } from "shiki";
import { bundledLanguages, createHighlighter, createJavaScriptRegexEngine } from "shiki";
import { markPotentiallyDangerousBidirectionalUnicodeText } from "./bidirectional-unicode-text";
import DOMPurify from "dompurify";

const LIGHT_THEME = "github-light-default";
const DARK_THEME = "github-dark-default";

let highlighter: HighlighterGeneric<BundledLanguage, BundledTheme> | null = null;

function isABundledLanguage(language: string): language is BundledLanguage {
    return language in bundledLanguages;
}

export async function syntaxHighlightElement(element: HTMLElement): Promise<void> {
    let language: string | null = null;
    element.classList.forEach((value) => {
        if (value.startsWith("language-")) {
            language = value.slice("language-".length);
        }
    });
    if (language === null) {
        language = "text";
    }

    const parent_pre_tag = element.parentElement;
    if (!(parent_pre_tag instanceof HTMLPreElement)) {
        return;
    }

    if (highlighter === null) {
        highlighter = await createHighlighter({
            themes: [LIGHT_THEME, DARK_THEME],
            langs: [],
            engine: createJavaScriptRegexEngine(),
        });
    }

    const used_language: BundledLanguage | SpecialLanguage = isABundledLanguage(language)
        ? language
        : "text";
    await highlighter.loadLanguage(used_language);

    const host = document.createElement("div");
    host.innerHTML = DOMPurify.sanitize(
        highlighter.codeToHtml(element.textContent, {
            lang: used_language,
            themes: {
                light: LIGHT_THEME,
                dark: DARK_THEME,
            },
            defaultColor: "light-dark()",
            transformers: [{ postprocess: markPotentiallyDangerousBidirectionalUnicodeText }],
        }),
    );
    const pre_tag = host.querySelector("pre");
    const code_tag = host.querySelector("code");
    if (pre_tag === null || code_tag === null) {
        return;
    }

    pre_tag.classList.forEach((value) => parent_pre_tag.classList.add(value));
    element.classList.forEach((value) => code_tag.classList.add(value));
    element.replaceWith(code_tag);
}

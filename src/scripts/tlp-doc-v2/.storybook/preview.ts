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

import type { Preview } from "@storybook/web-components";
import type { HTMLTemplateResult } from "lit";
import { html } from "lit";
import "@tuleap/tlp/src/scss/_reset.scss";
import "@tuleap/tlp-styles/components/typography";
import "@tuleap/tlp/src/scss/tlp.scss";
import { dark_background, grey_background, white_background } from "./backgrounds";

const getTheme = (color_name: string): Promise<string> => {
    /* eslint-disable @typescript-eslint/consistent-type-assertions */
    switch (color_name) {
        case "blue":
            return import("@tuleap/tlp/src/scss/tlp-vars-blue.scss") as unknown as Promise<string>;
        case "green":
            return import("@tuleap/tlp/src/scss/tlp-vars-green.scss") as unknown as Promise<string>;
        case "grey":
            return import("@tuleap/tlp/src/scss/tlp-vars-grey.scss") as unknown as Promise<string>;
        case "orange":
            return import(
                "@tuleap/tlp/src/scss/tlp-vars-orange.scss"
            ) as unknown as Promise<string>;
        case "purple":
            return import(
                "@tuleap/tlp/src/scss/tlp-vars-purple.scss"
            ) as unknown as Promise<string>;
        case "red":
            return import("@tuleap/tlp/src/scss/tlp-vars-red.scss") as unknown as Promise<string>;
        default:
            throw Error("Unknown theme color");
    }
    /* eslint-enable */
};

const preview: Preview = {
    parameters: {
        layout: "centered",
        backgrounds: {
            default: grey_background.name,
            values: [white_background, grey_background, dark_background],
        },
        docs: {
            source: { excludeDecorators: true },
        },
    },
    globalTypes: {
        theme: {
            description: "Theme color for Tuleap",
            defaultValue: "orange",
            toolbar: {
                title: "Theme",
                items: ["orange", "blue", "green", "grey", "purple", "red"],
            },
        },
    },
    decorators: [
        (Story, context): HTMLTemplateResult => {
            const selected_theme = context.globals.theme;
            const defaulted_theme = selected_theme === "" ? "orange" : selected_theme;
            const doc = context.canvasElement.ownerDocument;
            const colors = new CSSStyleSheet();
            getTheme(defaulted_theme).then((theme_style) => {
                colors.replaceSync(theme_style);
                doc.adoptedStyleSheets = [colors];
            });
            return html`${Story()}`;
        },
        (Story, context): HTMLTemplateResult => {
            // Workaround to automatically set the "id" attribute to the MDX doc Canvas, so that the Background addon can function correctly
            // code by lazenyuk-dmitry (https://github.com/lazenyuk-dmitry)
            // See https://github.com/storybookjs/storybook/issues/14322
            const story_anchor = `anchor--${context.id}`;
            const exist_anchor = context.canvasElement.closest(`#${story_anchor}`);
            const story_container = context.canvasElement.closest(".sbdocs");
            if (!exist_anchor && story_container) {
                story_container.id = story_anchor;
            }
            return html`${Story()}`;
        },
    ],
};

export default preview;

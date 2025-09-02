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

import type { Preview } from "@storybook/web-components-vite";
import type { HTMLTemplateResult } from "lit";
import { html } from "lit";
import { initialize } from "msw-storybook-addon";
import "@tuleap/tlp/src/scss/_reset.scss";
import "@tuleap/tlp-styles/components/typography.scss";
import "@tuleap/tlp/src/scss/tlp.scss";
import blue_theme from "@tuleap/tlp/src/scss/tlp-vars-blue.scss?inline";
import green_theme from "@tuleap/tlp/src/scss/tlp-vars-green.scss?inline";
import grey_theme from "@tuleap/tlp/src/scss/tlp-vars-grey.scss?inline";
import orange_theme from "@tuleap/tlp/src/scss/tlp-vars-orange.scss?inline";
import purple_theme from "@tuleap/tlp/src/scss/tlp-vars-purple.scss?inline";
import red_theme from "@tuleap/tlp/src/scss/tlp-vars-red.scss?inline";
import { dark_background, grey_background, white_background } from "./backgrounds";
import {
    blue_theme_entry,
    green_theme_entry,
    grey_theme_entry,
    orange_theme_entry,
    purple_theme_entry,
    red_theme_entry,
} from "./themes";

const getTheme = (color_name: string): string => {
    switch (color_name) {
        case blue_theme_entry.value:
            return blue_theme;
        case green_theme_entry.value:
            return green_theme;
        case grey_theme_entry.value:
            return grey_theme;
        case orange_theme_entry.value:
            return orange_theme;
        case purple_theme_entry.value:
            return purple_theme;
        case red_theme_entry.value:
            return red_theme;
        default:
            throw Error("Unknown theme color");
    }
};

initialize();

const preview: Preview = {
    parameters: {
        layout: "centered",
        backgrounds: {
            options: {
                [white_background.key]: white_background,
                [grey_background.key]: grey_background,
                [dark_background.key]: dark_background,
            },
        },
        docs: {
            source: { excludeDecorators: true },
        },
        options: {
            storySort: {
                order: [
                    "introduction",
                    "TLP",
                    [
                        "Structure & Navigation",
                        ["Layout", "Panes", "Cards", "Alerts", "Tabs", "Wizards", "Pagination"],
                        "Visual assets",
                        [
                            "Colors",
                            "Illustrations",
                            "Avatars",
                            "Typography",
                            "Relative dates",
                            "Iconography",
                            "Badges",
                            "Skeleton screens",
                        ],
                        "Buttons & Switch",
                        ["Buttons", "Button Bars", "Switch"],
                        "Forms",
                        [
                            "Good Practices",
                            "Properties",
                            "Inputs",
                            "Search",
                            "Textarea",
                            "List picker",
                            "Lazybox",
                            "LazyAutocompleter",
                            "Selects",
                            "Checkboxes",
                            "Radios",
                            "Prepends",
                            "Appends",
                            "Date picker",
                        ],
                        "Tables",
                        "Fly Over",
                        ["Tooltips", "Popovers", "Dropdowns", "Modals"],
                    ],
                ],
            },
        },
    },
    globalTypes: {
        theme: {
            description: "Color theme for Tuleap",
            toolbar: {
                title: "Theme",
                icon: "paintbrush",
                items: [
                    orange_theme_entry,
                    blue_theme_entry,
                    green_theme_entry,
                    grey_theme_entry,
                    purple_theme_entry,
                    red_theme_entry,
                ],
            },
        },
    },
    initialGlobals: {
        backgrounds: { value: grey_background.key },
        theme: orange_theme_entry.value,
    },
    decorators: [
        (Story, context): HTMLTemplateResult => {
            const selected_theme = context.globals.theme;
            const defaulted_theme =
                selected_theme === "" ? orange_theme_entry.value : selected_theme;
            const doc = context.canvasElement.ownerDocument;
            const colors_stylesheet = new CSSStyleSheet();
            const theme_style = getTheme(defaulted_theme);
            colors_stylesheet.replaceSync(theme_style);
            doc.adoptedStyleSheets = [colors_stylesheet];
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

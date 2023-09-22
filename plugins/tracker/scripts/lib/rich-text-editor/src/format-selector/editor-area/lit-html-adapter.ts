/*
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

import type { TemplateResult } from "lit-html";
import { html, render } from "lit-html";
import type { GettextProvider } from "@tuleap/gettext";

export interface HTMLOrTextEditorArea {
    readonly mount_point: HTMLDivElement;
    readonly selectbox: TemplateResult;
    readonly textarea: TemplateResult;
}

export interface MarkdownTextEditorArea extends HTMLOrTextEditorArea {
    readonly preview_button: TemplateResult;
    readonly help_button: TemplateResult;
    readonly preview_area: TemplateResult;
    readonly hidden_format_input: TemplateResult | undefined;
}

export const renderHTMLOrTextEditor = (
    presenter: HTMLOrTextEditorArea,
    gettext_provider: GettextProvider,
): void => {
    render(
        html`
            <div class="rte_format">
                ${gettext_provider.gettext("Format:")}${presenter.selectbox}
            </div>
            ${presenter.textarea}
        `,
        presenter.mount_point,
    );
};

export const renderMarkdownEditor = (
    presenter: MarkdownTextEditorArea,
    gettext_provider: GettextProvider,
): void => {
    render(
        html`
            <div class="rte_format">
                ${gettext_provider.gettext(
                    "Format:",
                )}${presenter.selectbox}${presenter.hidden_format_input}${presenter.preview_button}${presenter.help_button}
            </div>
            ${presenter.textarea}${presenter.preview_area}
        `,
        presenter.mount_point,
    );
};

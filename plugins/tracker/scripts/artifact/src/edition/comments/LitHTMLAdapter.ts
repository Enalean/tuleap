/*
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
import { html, nothing, render } from "lit-html";

export type EditableCommentPresenter = {
    readonly mount_point: HTMLElement;
    readonly render_before: HTMLElement;
    readonly is_in_edition: boolean;
    readonly edit_zone: TemplateResult;
};

export type LitHTMLAdapter = {
    render(presenter: EditableCommentPresenter): void;
};

export const LitHTMLAdapter = (): LitHTMLAdapter => ({
    render(presenter): void {
        render(
            html`${presenter.is_in_edition ? presenter.edit_zone : nothing}`,
            presenter.mount_point,
            { renderBefore: presenter.render_before },
        );
    },
});

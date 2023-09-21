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

import type { GettextProvider } from "@tuleap/gettext";
import type { TemplateResult } from "lit-html";
import { html } from "lit-html";
import { getCommonMarkSyntaxPopoverHelperContent } from "../commonmark-syntax-helper";
import "../FlamingParrotPopoverButtonElement";

export interface HelpButtonPresenter {
    readonly is_disabled: boolean;
}

export function createSyntaxHelpButton(
    presenter: HelpButtonPresenter,
    gettext_provider: GettextProvider,
): TemplateResult {
    const helper_popover = getCommonMarkSyntaxPopoverHelperContent(gettext_provider);
    return html`
        <fp-popover-button>
            <button
                type="button"
                class="btn btn-small rte-button"
                data-button
                data-test="help-button"
                ?disabled="${presenter.is_disabled}"
            >
                <i class="fas fa-question-circle" aria-hidden="true"></i>
                ${gettext_provider.gettext("Help")}
            </button>
            ${helper_popover}
        </fp-popover-button>
    `;
}

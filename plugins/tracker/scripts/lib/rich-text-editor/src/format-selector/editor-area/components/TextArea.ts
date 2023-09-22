/*
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
 *
 */

import type { TemplateResult } from "lit-html";
import { html } from "lit-html";

const HIDDEN_TEXTAREA_CLASSNAME = "rte-hide-textarea";

export interface TextAreaPresenter {
    readonly promise_of_preview: Promise<unknown>; // Promise wraps "unknown" because we don't care about its result, we just want to know if it's resolved or rejected
    readonly is_hidden: boolean;
    textarea: HTMLTextAreaElement;
}

export function wrapTextArea(presenter: TextAreaPresenter): TemplateResult {
    presenter.textarea.disabled = true;
    presenter.promise_of_preview.finally(() => {
        presenter.textarea.disabled = false;
        if (presenter.is_hidden) {
            presenter.textarea.classList.add(HIDDEN_TEXTAREA_CLASSNAME);
        } else {
            // Only hide the textarea in Preview mode, otherwise it won't be submitted in the <form>
            presenter.textarea.classList.remove(HIDDEN_TEXTAREA_CLASSNAME);
        }
    });

    return html`${presenter.textarea}`;
}

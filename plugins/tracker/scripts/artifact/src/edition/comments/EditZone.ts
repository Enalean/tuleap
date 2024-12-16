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
import { html } from "lit-html";
import type { GettextProvider } from "@tuleap/gettext";

export type EditZonePresenter = {
    readonly textarea: HTMLTextAreaElement;
    readonly are_notifications_enabled: boolean;
    onSubmit(): void;
    onCancel(): void;
};

export const EditZone = (
    presenter: EditZonePresenter,
    gettext_provider: GettextProvider,
): TemplateResult => {
    return html`<div class="artifact-comment-edit-panel">
        ${presenter.textarea}
        ${presenter.are_notifications_enabled
            ? html`<p class="text-info">
                  ${gettext_provider.gettext(
                      "When you use @ to mention someone, they will get an email notification.",
                  )}
              </p>`
            : html`<p class="text-warning">
                  ${gettext_provider.gettext(
                      "This tracker's notifications are disabled, when you use @ to mention someone, no email will be sent.",
                  )}
              </p>`}
        <div class="artifact-comment-edit-panel-actions">
            <button
                type="button"
                class="btn btn-primary"
                data-test="edit-comment-submit"
                @click="${presenter.onSubmit}"
            >
                ${gettext_provider.gettext("Submit")}
            </button>
            <button
                type="button"
                class="btn"
                data-test="edit-comment-cancel"
                @click="${presenter.onCancel}"
            >
                ${gettext_provider.gettext("Cancel")}
            </button>
        </div>
    </div>`;
};

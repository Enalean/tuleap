/*
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

import { html } from "hybrids";
import type { UpdateFunction } from "hybrids";
import type { GettextProvider } from "@tuleap/gettext";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import { FOCUSABLE_TEXTAREA_CLASSNAME } from "../helpers/textarea-focus-helper";

export const getDescriptionCommentFormTemplate = (
    host: PullRequestDescriptionComment,
    gettext_provider: GettextProvider
): UpdateFunction<PullRequestDescriptionComment> => {
    if (!host.edition_form_presenter) {
        return html``;
    }

    const onClickCancel = (host: PullRequestDescriptionComment): void => {
        host.controller.hideEditionForm(host);
    };

    return html`
        <div class="pull-request-comment-content pull-request-comment-content-main-color" data-test="pull-request-description-write-mode">
            <div class="pull-request-comment-write-mode-header">
                <div class="tlp-tabs pull-request-comment-write-mode-header-tabs">
                    <span class="tlp-tab tlp-tab-active">${gettext_provider.gettext("Write")}</a>
                </div>
            </div>
            <textarea
                data-test="pull-request-description-comment-form-textarea"
                class="${FOCUSABLE_TEXTAREA_CLASSNAME} tlp-textarea"
                rows="10"
                placeholder="${gettext_provider.gettext("Say something…")}"
                contenteditable="true"
            >${host.edition_form_presenter.description_content}</textarea>
            <div class="pull-request-comment-footer" data-test="pull-request-description-comment-footer">
                <button
                    type="button"
                    class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
                    onclick="${onClickCancel}"
                    data-test="button-cancel-edition"
                >
                    ${gettext_provider.gettext("Cancel")}
                </button>
                <button
                    type="button"
                    class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary"
                    data-test="button-save-edition"
                    disabled
                >
                    ${gettext_provider.gettext("Save")}
                </button>
            </div>
        </div>
    `;
};

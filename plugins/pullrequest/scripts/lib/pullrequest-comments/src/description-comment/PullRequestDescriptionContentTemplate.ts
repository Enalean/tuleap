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
import DOMPurify from "dompurify";
import { Option } from "@tuleap/option";
import type { GettextProvider } from "@tuleap/gettext";
import { FORMAT_COMMONMARK } from "@tuleap/plugin-pullrequest-constants";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import { getHeaderTemplate } from "../templates/CommentHeaderTemplate";

const getContent = (
    host: PullRequestDescriptionComment,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestDescriptionComment> => {
    if (host.description.content !== "") {
        const sanitized_content =
            host.description.format === FORMAT_COMMONMARK
                ? DOMPurify.sanitize(host.description.post_processed_content, {
                      ADD_TAGS: ["tlp-syntax-highlighting"],
                  })
                : DOMPurify.sanitize(host.description.content);

        return html`
            <p
                class="pull-request-comment-text"
                data-test="description-content"
                innerHTML="${sanitized_content}"
            ></p>
        `;
    }

    if (host.description.can_user_update_description) {
        return html`
            <p
                class="pull-request-comment-text pull-request-description-comment-empty-state"
                data-test="description-empty-state"
            >
                ${gettext_provider.gettext("No commit description has been provided yet.")}
                <span class="pull-request-description-comment-empty-state-bold">
                    ${gettext_provider.gettext("Please add one.")}
                </span>
            </p>
        `;
    }

    return html`
        <p
            class="pull-request-comment-text pull-request-description-comment-empty-state"
            data-test="description-empty-state"
        >
            ${gettext_provider.gettext("No commit description has been provided yet.")}
        </p>
    `;
};

const getFooter = (
    host: PullRequestDescriptionComment,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestDescriptionComment> => {
    if (!host.description.can_user_update_description) {
        return html``;
    }

    const onClickToggleEditionForm = (host: PullRequestDescriptionComment): void => {
        host.controller.showEditionForm(host);
    };

    return html`
        <div class="pull-request-comment-footer" data-test="description-comment-footer">
            <button
                type="button"
                class="pull-request-comment-footer-action-button tlp-button-small tlp-button-primary tlp-button-outline"
                onclick="${onClickToggleEditionForm}"
                data-test="button-edit-description-comment"
            >
                ${gettext_provider.gettext("Edit")}
            </button>
        </div>
    `;
};

export const getDescriptionContentTemplate = (
    host: PullRequestDescriptionComment,
    gettext_provider: GettextProvider,
): UpdateFunction<PullRequestDescriptionComment> => {
    if (host.edition_form_presenter !== null) {
        return html``;
    }

    return html`
        <div class="pull-request-comment-content" data-test="pull-request-description-read-mode">
            <div class="pull-request-comment-content-info">
                ${getHeaderTemplate(
                    host.description.author,
                    host.controller.getRelativeDateHelper(),
                    gettext_provider,
                    host.description.post_date,
                    Option.nothing(),
                )}
            </div>
            ${getContent(host, gettext_provider)} ${getFooter(host, gettext_provider)}
        </div>
    `;
};

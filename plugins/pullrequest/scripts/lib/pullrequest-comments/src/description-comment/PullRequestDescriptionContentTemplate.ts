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
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import type { GettextProvider } from "@tuleap/gettext";

export const getDescriptionContentTemplate = (
    host: PullRequestDescriptionComment,
    gettext_provider: GettextProvider
): UpdateFunction<PullRequestDescriptionComment> => {
    if (host.description.content !== "") {
        return html`
            <p
                class="pull-request-comment-text"
                data-test="description-content"
                innerHTML="${DOMPurify.sanitize(host.description.content)}"
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

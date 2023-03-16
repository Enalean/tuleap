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

import { PullRequestDescriptionCommentFormPresenter } from "./PullRequestDescriptionCommentFormPresenter";
import { RelativeDatesHelper } from "../helpers/relative-dates-helper";
import type { HelpRelativeDatesDisplay } from "../helpers/relative-dates-helper";
import type { FocusTextArea } from "../helpers/textarea-focus-helper";
import type { PullRequestDescriptionComment } from "./PullRequestDescriptionComment";
import type { CurrentPullRequestUserPresenter } from "../types";

export interface ControlPullRequestDescriptionComment {
    showEditionForm: (host: PullRequestDescriptionComment) => void;
    hideEditionForm: (host: PullRequestDescriptionComment) => void;
    getRelativeDateHelper: () => HelpRelativeDatesDisplay;
}

export const PullRequestDescriptionCommentController = (
    focus_helper: FocusTextArea,
    current_user: CurrentPullRequestUserPresenter
): ControlPullRequestDescriptionComment => ({
    showEditionForm(host: PullRequestDescriptionComment): void {
        host.edition_form_presenter =
            PullRequestDescriptionCommentFormPresenter.fromCurrentDescription(host.description);

        focus_helper.focusTextArea(host.content());
    },
    hideEditionForm(host: PullRequestDescriptionComment): void {
        host.edition_form_presenter = null;
    },
    getRelativeDateHelper: (): HelpRelativeDatesDisplay =>
        RelativeDatesHelper(
            current_user.preferred_date_format,
            current_user.preferred_relative_date_display,
            current_user.user_locale
        ),
});

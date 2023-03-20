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

import type { PullRequestDescriptionCommentPresenter } from "./PullRequestDescriptionCommentPresenter";
import type { WritingZoneState } from "../templates/WritingZoneTemplate";

export interface DescriptionCommentFormPresenter {
    readonly pull_request_id: number;
    readonly pull_request_raw_title: string;
    readonly description_content: string;
    readonly is_being_submitted: boolean;
    readonly writing_zone_state: WritingZoneState;
}

export const PullRequestDescriptionCommentFormPresenter = {
    fromCurrentDescription: (
        current_description: PullRequestDescriptionCommentPresenter
    ): DescriptionCommentFormPresenter => ({
        pull_request_id: current_description.pull_request_id,
        pull_request_raw_title: current_description.pull_request_raw_title,
        description_content: current_description.raw_content,
        is_being_submitted: false,
        writing_zone_state: {
            initial_content: current_description.raw_content,
            is_focused: false,
        },
    }),
    updateDescriptionContent: (
        presenter: DescriptionCommentFormPresenter,
        content: string
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        description_content: content,
    }),
    updateWritingZoneState: (
        presenter: DescriptionCommentFormPresenter,
        is_focused: boolean
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        writing_zone_state: {
            ...presenter.writing_zone_state,
            is_focused,
        },
    }),
    buildSubmitted: (
        presenter: DescriptionCommentFormPresenter
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: true,
    }),
    buildNotSubmitted: (
        presenter: DescriptionCommentFormPresenter
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: false,
    }),
};

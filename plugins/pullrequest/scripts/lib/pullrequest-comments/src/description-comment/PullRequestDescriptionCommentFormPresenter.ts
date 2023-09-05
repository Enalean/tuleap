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

export interface DescriptionCommentFormPresenter {
    readonly pull_request_id: number;
    readonly description_content: string;
    readonly is_being_submitted: boolean;
}

export const PullRequestDescriptionCommentFormPresenter = {
    fromCurrentDescription: (
        current_description: PullRequestDescriptionCommentPresenter,
    ): DescriptionCommentFormPresenter => ({
        pull_request_id: current_description.pull_request_id,
        description_content: current_description.raw_content,
        is_being_submitted: false,
    }),
    updateDescriptionContent: (
        presenter: DescriptionCommentFormPresenter,
        content: string,
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        description_content: content,
    }),
    buildSubmitted: (
        presenter: DescriptionCommentFormPresenter,
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: true,
    }),
    buildNotSubmitted: (
        presenter: DescriptionCommentFormPresenter,
    ): DescriptionCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: false,
    }),
};

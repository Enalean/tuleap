/*
 * Copyright (c) Enalean, 2022 - present. All Rights Reserved.
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

import type { NewCommentFormComponentConfig } from "./NewCommentFormController";

export interface NewCommentFormAuthorPresenter {
    readonly avatar_url: string;
}

export interface NewCommentFormPresenter {
    readonly comment_content: string;
    readonly comment_author: NewCommentFormAuthorPresenter;
    readonly is_cancel_allowed: boolean;
    readonly is_being_submitted: boolean;
    readonly is_submittable: boolean;
}

export const NewCommentFormPresenter = {
    buildFromAuthor: (
        comment_author: NewCommentFormAuthorPresenter,
        config: NewCommentFormComponentConfig,
    ): NewCommentFormPresenter => ({
        comment_author,
        comment_content: "",
        is_cancel_allowed: config.is_cancel_allowed,
        is_being_submitted: false,
        is_submittable: false,
    }),
    updateContent: (
        presenter: NewCommentFormPresenter,
        content: string,
    ): NewCommentFormPresenter => ({
        ...presenter,
        comment_content: content,
        is_submittable: content.length > 0,
    }),
    buildSubmitted: (presenter: NewCommentFormPresenter): NewCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: true,
    }),
    buildNotSubmitted: (presenter: NewCommentFormPresenter): NewCommentFormPresenter => ({
        ...presenter,
        is_being_submitted: false,
    }),
};

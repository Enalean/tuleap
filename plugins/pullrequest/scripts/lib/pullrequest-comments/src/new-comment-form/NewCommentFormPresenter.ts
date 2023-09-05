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

import type { NewCommentFormComponentConfig } from "./NewCommentFormController";

export interface NewCommentFormAuthorPresenter {
    readonly avatar_url: string;
}

export interface NewCommentFormPresenter {
    readonly comment: string;
    readonly is_saving_comment: boolean;
    readonly is_cancel_allowed: boolean;
    readonly author: NewCommentFormAuthorPresenter;
}

export const NewCommentFormPresenter = {
    buildFromAuthor: (
        author: NewCommentFormAuthorPresenter,
        config: NewCommentFormComponentConfig,
    ): NewCommentFormPresenter => ({
        comment: "",
        is_saving_comment: false,
        is_cancel_allowed: config.is_cancel_allowed,
        author,
    }),
    buildWithUpdatedComment: (
        presenter: NewCommentFormPresenter,
        new_comment: string,
    ): NewCommentFormPresenter => ({
        ...presenter,
        comment: new_comment,
    }),
    buildSavingComment: (presenter: NewCommentFormPresenter): NewCommentFormPresenter => ({
        ...presenter,
        is_saving_comment: true,
    }),
    buildNotSavingComment: (presenter: NewCommentFormPresenter): NewCommentFormPresenter => ({
        ...presenter,
        is_saving_comment: false,
    }),
};

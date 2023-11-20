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

import { TYPE_GLOBAL_COMMENT } from "@tuleap/plugin-pullrequest-constants";
import type { EditionFormPresenter } from "../../src/comment/edition/EditionFormPresenter";

const base_presenter: EditionFormPresenter = {
    comment_id: 110,
    comment_type: TYPE_GLOBAL_COMMENT,
    edited_content: "",
    is_being_submitted: false,
    is_submittable: true,
};

export const EditionFormPresenterStub = {
    buildInitial: (edited_content = ""): EditionFormPresenter => ({
        ...base_presenter,
        edited_content,
    }),
    withSubmitForbidden: (): EditionFormPresenter => ({
        ...base_presenter,
        is_submittable: false,
    }),
    withSubmitPossible: (): EditionFormPresenter => ({
        ...base_presenter,
        is_submittable: true,
    }),
    withSubmittedComment: (): EditionFormPresenter => ({
        ...base_presenter,
        is_being_submitted: true,
    }),
};

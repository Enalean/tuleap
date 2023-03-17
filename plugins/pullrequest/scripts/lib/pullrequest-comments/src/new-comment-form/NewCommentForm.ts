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

import { define } from "hybrids";
import type { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { ControlNewCommentForm } from "./NewCommentFormController";
import { getNewCommentFormContent } from "./NewCommentFormTemplate";
import { gettext_provider } from "../gettext-provider";

export const PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME = "tuleap-pullrequest-new-comment-form";
export type HostElement = NewCommentForm & HTMLElement;

export interface NewCommentForm {
    readonly content: () => HTMLElement;
    readonly element_height: number;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly controller: ControlNewCommentForm;
    presenter: NewCommentFormPresenter;
}

export const form_height_descriptor = {
    get: (host: NewCommentForm): number => host.content().getBoundingClientRect().height,
    observe(host: NewCommentForm): void {
        host.post_rendering_callback?.();
    },
};

export const NewInlineCommentFormComponent = define<NewCommentForm>({
    tag: PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    post_rendering_callback: undefined,
    element_height: form_height_descriptor,
    controller: {
        set: (host, controller) => {
            controller.buildInitialPresenter(host);

            return controller;
        },
    },
    presenter: undefined,
    content: (host) => getNewCommentFormContent(host, gettext_provider),
});

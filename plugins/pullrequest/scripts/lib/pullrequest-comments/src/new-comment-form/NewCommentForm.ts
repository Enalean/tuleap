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
import { WritingZoneController } from "../writing-zone/WritingZoneController";
import type { ControlWritingZone } from "../writing-zone/WritingZoneController";
import { gettext_provider } from "../gettext-provider";
import type { InternalWritingZone } from "../writing-zone/WritingZone";
import { getWritingZoneElement } from "../writing-zone/WritingZone";
import type { ElementContainingAWritingZone } from "../types";

export const PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME = "tuleap-pullrequest-new-comment-form";
export type HostElement = NewCommentForm &
    HTMLElement &
    ElementContainingAWritingZone<NewCommentForm>;

export interface NewCommentForm {
    readonly render: () => HTMLElement;
    readonly element_height: number;
    readonly post_rendering_callback: (() => void) | undefined;
    readonly controller: ControlNewCommentForm;
    readonly writing_zone_controller: ControlWritingZone;
    readonly writing_zone: HTMLElement & InternalWritingZone;
    presenter: NewCommentFormPresenter;
}

export const form_height_descriptor = {
    value: (host: NewCommentForm): number => host.render().getBoundingClientRect().height,
    observe(host: NewCommentForm): void {
        setTimeout(() => {
            host.post_rendering_callback?.();
        });
    },
};

define<NewCommentForm>({
    tag: PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME,
    post_rendering_callback: undefined,
    element_height: form_height_descriptor,
    writing_zone_controller: (host, controller: ControlWritingZone | undefined) =>
        controller ??
        WritingZoneController({
            document,
            project_id: Number(host.controller.getProjectId()),
            focus_writing_zone_when_connected: host.controller.shouldFocusWritingZoneOnceRendered(),
        }),
    writing_zone: getWritingZoneElement,
    controller: (host, controller: ControlNewCommentForm) => controller,
    presenter: (host, value) => value ?? host.controller.buildInitialPresenter(),
    render: (host) => getNewCommentFormContent(host, gettext_provider),
});

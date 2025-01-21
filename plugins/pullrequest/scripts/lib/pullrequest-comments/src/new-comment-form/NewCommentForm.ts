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
import { NewCommentFormPresenter } from "./NewCommentFormPresenter";
import type { ControlNewCommentForm } from "./NewCommentFormController";
import { getNewCommentFormContent } from "./NewCommentFormTemplate";
import { WritingZoneController } from "../writing-zone/WritingZoneController";
import type { ControlWritingZone } from "../writing-zone/WritingZoneController";
import { gettext_provider } from "../gettext-provider";
import type { WritingZone } from "../writing-zone/WritingZone";
import { getWritingZoneElement } from "../writing-zone/WritingZone";

export const PULL_REQUEST_NEW_COMMENT_FORM_ELEMENT_TAG_NAME = "tuleap-pullrequest-new-comment-form";

export interface NewCommentForm {
    render(): HTMLElement;
    readonly element_height: number;
    post_rendering_callback: (() => void) | undefined;
    controller: ControlNewCommentForm;
    readonly writing_zone_controller: ControlWritingZone;
    readonly writing_zone: HTMLElement & WritingZone;
    presenter: NewCommentFormPresenter;
}
export type HostElement = NewCommentForm & HTMLElement;

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
    writing_zone(host: HostElement) {
        const element = getWritingZoneElement();
        element.controller = host.writing_zone_controller;
        element.addEventListener("writing-zone-input", (event: Event) => {
            if (!(event instanceof CustomEvent)) {
                return;
            }
            host.presenter = NewCommentFormPresenter.updateContent(
                host.presenter,
                event.detail.content,
            );
        });
        return element;
    },
    controller: (host, controller: ControlNewCommentForm) => controller,
    presenter(host, presenter: NewCommentFormPresenter | undefined) {
        if (!presenter) {
            return host.controller.buildInitialPresenter();
        }
        host.writing_zone.comment_content = presenter.comment_content;
        return presenter;
    },
    render: (host) => getNewCommentFormContent(host, gettext_provider),
});

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

import { define, html } from "hybrids";
import type { ElementContainingAWritingZone } from "../../types";
import type { ControlWritingZone } from "../../writing-zone/WritingZoneController";
import { WritingZoneController } from "../../writing-zone/WritingZoneController";
import type { InternalWritingZone } from "../../writing-zone/WritingZone";
import { getWritingZoneElement } from "../../writing-zone/WritingZone";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { ControlEditionForm } from "./EditionFormController";
import { getEditionForm } from "./EditionFormTemplate";
import { gettext_provider } from "../../gettext-provider";
import { EditionFormPresenter } from "./EditionFormPresenter";

export const TAG = "tuleap-pullrequest-comment-edition-form";

export type EditionForm = {
    readonly controller: ControlEditionForm;
    readonly comment: PullRequestCommentPresenter;
    readonly project_id: number;
};

export type InternalEditionForm = Readonly<EditionForm> & {
    writing_zone_controller: ControlWritingZone;
    writing_zone: HTMLElement & InternalWritingZone;
    after_render_once: unknown;
    content: () => HTMLElement;
    presenter: EditionFormPresenter;
};

export type HostElement = InternalEditionForm &
    ElementContainingAWritingZone<InternalEditionForm> &
    HTMLElement;

export const after_render_once_descriptor = {
    get: (host: InternalEditionForm): unknown => host.content(),
    observe(host: InternalEditionForm): void {
        host.controller.initEditionForm(host);
    },
};
export const EditionForm = define<InternalEditionForm>({
    tag: TAG,
    project_id: undefined,
    comment: {
        set: (host, comment) => {
            host.presenter = EditionFormPresenter.fromComment(comment);
            return comment;
        },
    },
    presenter: undefined,
    controller: undefined,
    writing_zone_controller: {
        get: (host, controller: ControlWritingZone | undefined) =>
            controller ??
            WritingZoneController({
                document,
                project_id: host.project_id,
                focus_writing_zone_when_connected:
                    host.controller.shouldFocusWritingZoneOnceRendered(),
            }),
    },
    writing_zone: {
        get: getWritingZoneElement,
    },
    after_render_once: after_render_once_descriptor,
    content: (host) => html`${getEditionForm(host, gettext_provider)}`,
});

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
import { WritingZoneController } from "../../writing-zone/WritingZoneController";
import type { WritingZone } from "../../writing-zone/WritingZone";
import { getWritingZoneElement } from "../../writing-zone/WritingZone";
import type { PullRequestCommentPresenter } from "../PullRequestCommentPresenter";
import type { ControlEditionForm } from "./EditionFormController";
import { getEditionForm } from "./EditionFormTemplate";
import { gettext_provider } from "../../gettext-provider";
import { EditionFormPresenter } from "./EditionFormPresenter";

export const TAG = "tuleap-pullrequest-comment-edition-form";

export type EditionForm = {
    controller: ControlEditionForm;
    comment: PullRequestCommentPresenter;
    project_id: number;
};
export type InternalEditionForm = Readonly<EditionForm> & {
    writing_zone: HTMLElement & WritingZone;
    presenter: EditionFormPresenter;
};
export type HostElement = InternalEditionForm & HTMLElement;

define<InternalEditionForm>({
    tag: TAG,
    project_id: (host, value) => value,
    comment: (host, comment) => comment,
    presenter: function (
        host: InternalEditionForm,
        current_presenter: EditionFormPresenter | undefined,
    ): EditionFormPresenter {
        const presenter = current_presenter ?? EditionFormPresenter.fromComment(host.comment);
        host.writing_zone.comment_content = presenter.edited_content;
        return presenter;
    },
    controller: (host, value) => value,
    writing_zone(host: HostElement) {
        const element = getWritingZoneElement();
        element.controller = WritingZoneController({
            document,
            project_id: host.project_id,
            focus_writing_zone_when_connected: true,
        });
        element.addEventListener("writing-zone-input", (event: Event) => {
            if (!(event instanceof CustomEvent)) {
                return;
            }
            host.presenter = EditionFormPresenter.buildUpdated(
                host.presenter,
                event.detail.content,
            );
        });
        return element;
    },
    render: (host) => getEditionForm(host, gettext_provider),
});

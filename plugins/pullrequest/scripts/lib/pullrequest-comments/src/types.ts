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

import type {
    CommentType,
    PullRequestEventType,
    TYPE_EVENT_PULLREQUEST_ACTION,
} from "@tuleap/plugin-pullrequest-constants";
import type {
    ActionOnPullRequestEvent,
    CommentOnFile,
    GlobalComment,
} from "@tuleap/plugin-pullrequest-rest-api-types";
import type { RelativeDatesDisplayPreference } from "@tuleap/tlp-relative-date";
import type { Fault } from "@tuleap/fault";
import type { ControlWritingZone } from "./writing-zone/WritingZoneController";

export interface CurrentPullRequestUserPresenter {
    readonly user_id: number;
    readonly avatar_url: string;
    readonly preferred_date_format: string;
    readonly preferred_relative_date_display: RelativeDatesDisplayPreference;
    readonly user_locale: string;
}

export type SupportedTimelineItemTypes =
    | CommentType
    | Extract<PullRequestEventType, typeof TYPE_EVENT_PULLREQUEST_ACTION>;
export type SupportedTimelineItem = GlobalComment | CommentOnFile | ActionOnPullRequestEvent;
export type PullRequestCommentErrorCallback = (fault: Fault) => void;

export type WritingZoneInteractionsHandler<ElementType> = {
    handleWritingZoneContentChange(
        element: ElementContainingAWritingZone<ElementType>,
        content: string
    ): void;
    shouldFocusWritingZoneOnceRendered(): boolean;
};

export type ElementContainingAWritingZone<ElementType> = {
    readonly controller: WritingZoneInteractionsHandler<ElementType>;
    readonly writing_zone_controller: ControlWritingZone;
};

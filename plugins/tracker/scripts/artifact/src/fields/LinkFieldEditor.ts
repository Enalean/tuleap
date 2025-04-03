/*
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

import { getAttributeOrThrow } from "@tuleap/dom";
import { Option } from "@tuleap/option";
import type { LocaleString } from "@tuleap/gettext";
import type { ParentArtifactIdentifier } from "@tuleap/plugin-tracker-artifact-common";
import {
    CurrentArtifactIdentifier,
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    EventDispatcher,
} from "@tuleap/plugin-tracker-artifact-common";
import {
    ArtifactCrossReference,
    LinkFieldCreator,
    LinksMarkedForRemovalStore,
    LinksStore,
    NewLinksStore,
    ParentTrackerIdentifier,
    createLinkField,
    TrackerShortname,
    UserIdentifier,
} from "@tuleap/plugin-tracker-link-field";
import type { ColorName } from "@tuleap/plugin-tracker-constants";

export interface LinkFieldEditor {
    init(mount_point: HTMLElement): void;
}

function parseInt(id: string): number {
    return Number.parseInt(id, 10);
}

function assertColorName(_color: string): _color is ColorName {
    return true;
}

export function initLinkField(user_locale: LocaleString): void {
    const mount_point = document.querySelector("[data-link-field-id]");
    if (mount_point instanceof HTMLElement) {
        LinkFieldEditor(document, user_locale).init(mount_point);
    }
}

export const LinkFieldEditor = (doc: Document, user_locale: LocaleString): LinkFieldEditor => ({
    init(mount_point): void {
        const user_id = UserIdentifier.fromId(
            parseInt(getAttributeOrThrow(doc.body, "data-user-id")),
        );
        const link_field_id = parseInt(getAttributeOrThrow(mount_point, "data-link-field-id"));
        const link_field_label = getAttributeOrThrow(mount_point, "data-link-field-label");
        const current_artifact = Option.fromNullable(
            mount_point.getAttribute("data-current-artifact-id"),
        )
            .map(parseInt)
            .map(CurrentArtifactIdentifier.fromId);
        const parent_artifact = Option.nothing<ParentArtifactIdentifier>();
        const current_tracker_id = CurrentTrackerIdentifier.fromId(
            parseInt(getAttributeOrThrow(mount_point, "data-current-tracker-id")),
        );
        const current_tracker_color = getAttributeOrThrow(
            mount_point,
            "data-current-tracker-color",
        );
        if (!assertColorName(current_tracker_color)) {
            throw Error("Expected a valid color name");
        }
        const current_tracker_short_name = getAttributeOrThrow(
            mount_point,
            "data-current-tracker-short-name",
        );
        const current_project_id = CurrentProjectIdentifier.fromId(
            parseInt(getAttributeOrThrow(mount_point, "data-current-project-id")),
        );
        const allowed_link_types = JSON.parse(
            getAttributeOrThrow(mount_point, "data-allowed-link-types"),
        );
        const parent_tracker = Option.fromNullable(
            mount_point.getAttribute("data-parent-tracker-id"),
        )
            .map(parseInt)
            .map(ParentTrackerIdentifier.fromId);

        const link_field_creator = LinkFieldCreator(
            EventDispatcher(),
            LinksStore(),
            NewLinksStore(),
            LinksMarkedForRemovalStore(),
            current_artifact,
            ArtifactCrossReference.fromCurrentArtifact(
                current_artifact,
                TrackerShortname.fromString(current_tracker_short_name),
                current_tracker_color,
            ),
            current_project_id,
            current_tracker_id,
            parent_artifact,
            parent_tracker,
            user_id,
            user_locale,
        );

        const field = { field_id: link_field_id, label: link_field_label };

        const element = createLinkField(doc);
        element.controller = link_field_creator.createLinkFieldController(
            field,
            allowed_link_types,
        );
        element.autocompleter = link_field_creator.createLinkSelectorAutoCompleter();
        element.creatorController = link_field_creator.createArtifactCreatorController();

        mount_point.replaceWith(element);
    },
});

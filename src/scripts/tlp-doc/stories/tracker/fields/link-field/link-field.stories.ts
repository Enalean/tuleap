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

import "./link-field.scss";
import type { Meta, StoryObj } from "@storybook/web-components";
import { fn } from "@storybook/test";
import type { TemplateResult } from "lit";
import { html } from "lit";
import { mswDecorator } from "msw-storybook-addon";
import {
    CurrentArtifactIdentifier,
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    EventDispatcher,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import {
    ArtifactCrossReference,
    LinkFieldCreator,
    LinksMarkedForRemovalStore,
    LinksStore,
    NewLinksStore,
    ParentTrackerIdentifier,
    TrackerShortname,
    UserIdentifier,
} from "@tuleap/plugin-tracker-link-field";
import { en_US_LOCALE } from "@tuleap/core-constants";
import { Option } from "@tuleap/option";
import type { AllowedLinkTypeRepresentation } from "@tuleap/plugin-tracker-rest-api-types";
import { RequestHandlersBuilder } from "./RequestHandlersBuilder";

type LinkTypes = "Parent / Child" | "Covers" | "Custom Type";
const LinkTypesOptions: LinkTypes[] = ["Parent / Child", "Covers", "Custom Type"];

type LinkFieldProps = {
    label: string;
    is_editing_existing: boolean;
    will_have_parent_artifact: boolean;
    with_parent_tracker: boolean;
    available_link_types: LinkTypes[];
    onChange(): void;
};

function getAllowedLinkTypes(args: LinkFieldProps): ReadonlyArray<AllowedLinkTypeRepresentation> {
    return args.available_link_types.map((link_type) => {
        if (link_type === "Parent / Child") {
            return {
                shortname: "_is_child",
                forward_label: "Child",
                reverse_label: "Parent",
            };
        }
        if (link_type === "Covers") {
            return {
                shortname: "_covered_by",
                forward_label: "is Covered by",
                reverse_label: "Covers",
            };
        }
        return {
            shortname: "written_in",
            forward_label: "Writes",
            reverse_label: "Written In",
        };
    });
}

const CURRENT_TRACKER_SHORTNAME = "story";
const CURRENT_ARTIFACT_ID = 647;
const CURRENT_TRACKER_ID = 36;
const CURRENT_PROJECT_ID = 125;
const handlers_builder = RequestHandlersBuilder(
    CURRENT_PROJECT_ID,
    CURRENT_ARTIFACT_ID,
    CURRENT_TRACKER_ID,
    CURRENT_TRACKER_SHORTNAME,
);

function renderLinkField(args: LinkFieldProps): TemplateResult {
    const current_artifact: Option<CurrentArtifactIdentifier> = args.is_editing_existing
        ? Option.fromValue(CurrentArtifactIdentifier.fromId(CURRENT_ARTIFACT_ID))
        : Option.nothing();
    const parent_artifact: Option<ParentArtifactIdentifier> = args.will_have_parent_artifact
        ? Option.fromValue(ParentArtifactIdentifier.fromId(731))
        : Option.nothing();
    const parent_tracker: Option<ParentTrackerIdentifier> = args.with_parent_tracker
        ? Option.fromValue(ParentTrackerIdentifier.fromId(70))
        : Option.nothing();

    const link_field_creator = LinkFieldCreator(
        EventDispatcher(),
        LinksStore(),
        NewLinksStore(),
        LinksMarkedForRemovalStore(),
        current_artifact,
        ArtifactCrossReference.fromCurrentArtifact(
            current_artifact,
            TrackerShortname.fromString(CURRENT_TRACKER_SHORTNAME),
            "plum-crazy",
        ),
        CurrentProjectIdentifier.fromId(CURRENT_PROJECT_ID),
        CurrentTrackerIdentifier.fromId(CURRENT_TRACKER_ID),
        parent_artifact,
        parent_tracker,
        UserIdentifier.fromId(154),
        en_US_LOCALE,
    );

    const field = { field_id: 832, label: args.label };
    const allowed_link_types = getAllowedLinkTypes(args);

    const controller = link_field_creator.createLinkFieldController(field, allowed_link_types);
    const autocompleter = link_field_creator.createLinkSelectorAutoCompleter();
    const creator_controller = link_field_creator.createArtifactCreatorController();

    return html`<tuleap-tracker-link-field
        .controller="${controller}"
        .autocompleter="${autocompleter}"
        .creatorController="${creator_controller}"
        @change="${args.onChange}"
    ></tuleap-tracker-link-field>`;
}

const meta: Meta<LinkFieldProps> = {
    title: "Tracker/Fields/Link Field",
    render: renderLinkField,
    parameters: {
        controls: { exclude: ["onChange"] },
        msw: { handlers: [...handlers_builder.build()] },
    },
    args: {
        label: "Links",
        is_editing_existing: true,
        will_have_parent_artifact: false,
        with_parent_tracker: false,
        available_link_types: ["Parent / Child", "Covers", "Custom Type"],
        onChange: fn(),
    },
    argTypes: {
        label: {
            name: "Label",
            description: "The label of the field",
        },
        is_editing_existing: {
            name: "Is editing an existing artifact",
            description:
                "Whether we are editing an existing artifact's field. If false, we are creating a new artifact",
        },
        with_parent_tracker: {
            name: "With a parent tracker (hierarchy)",
            description: "Whether the current tracker is child of another tracker in a hierarchy",
        },
        will_have_parent_artifact: {
            name: "Will have a parent artifact",
            description:
                "Whether the artifact under creation is pre-wired to be a child of a parent artifact (for example, in Backlog app)",
            if: { arg: "is_editing_existing", truthy: false },
        },
        available_link_types: {
            name: "Available link types",
            control: "check",
            options: LinkTypesOptions,
        },
    },
    decorators: [
        mswDecorator,
        (story): TemplateResult => html`<div class="link-field-wrapper">${story()}</div>`,
    ],
};
export default meta;
type Story = StoryObj<LinkFieldProps>;

export const LinkField: Story = {};

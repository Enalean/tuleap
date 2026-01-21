<!--
  - Copyright (c) Enalean, 2026-present. All Rights Reserved.
  -
  - This file is a part of Tuleap.
  -
  - Tuleap is free software; you can redistribute it and/or modify
  - it under the terms of the GNU General Public License as published by
  - the Free Software Foundation; either version 2 of the License, or
  - (at your option) any later version.
  -
  - Tuleap is distributed in the hope that it will be useful,
  - but WITHOUT ANY WARRANTY; without even the implied warranty of
  - MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  - GNU General Public License for more details.
  -
  - You should have received a copy of the GNU General Public License
  - along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div class="tlp-form-element">
        <label-for-field v-bind:field="field" />
        <tuleap-tracker-link-field
            class="tracker-admin-fields-link-field"
            v-bind:controller="
                link_field_creator?.createLinkFieldController(
                    labeled_field,
                    allowed_link_types,
                    field_option,
                )
            "
            v-bind:autocompleter="link_field_creator?.createLinkSelectorAutoCompleter()"
            v-bind:creator-controller="link_field_creator?.createArtifactCreatorController()"
            v-bind:on-change="() => {}"
        />
    </div>
</template>

<script setup lang="ts">
import type {
    AllowedLinkTypeRepresentation,
    ArtifactLinkFieldStructure,
} from "@tuleap/plugin-tracker-rest-api-types";
import { onMounted, ref } from "vue";
import type {
    CurrentArtifactIdentifier,
    ParentArtifactIdentifier,
} from "@tuleap/plugin-tracker-artifact-common";
import {
    CurrentProjectIdentifier,
    CurrentTrackerIdentifier,
    EventDispatcher,
} from "@tuleap/plugin-tracker-artifact-common";
import type { ParentTrackerIdentifier } from "@tuleap/plugin-tracker-link-field";
import {
    ArtifactCrossReference,
    LinkFieldCreator,
    LinksMarkedForRemovalStore,
    LinksStore,
    NewLinksStore,
    TrackerShortname,
    UserIdentifier,
} from "@tuleap/plugin-tracker-link-field";
import { Option } from "@tuleap/option";
import { getLocaleWithDefault } from "@tuleap/date-helper";
import {
    CURRENT_USER,
    TRACKER_COLOR,
    TRACKER_ID,
    TRACKER_SHORTNAME,
} from "../../injection-symbols";
import { strictInject } from "@tuleap/vue-strict-inject";
import { PROJECT_ID } from "../../type";
import LabelForField from "./LabelForField.vue";

const props = defineProps<{
    field: ArtifactLinkFieldStructure;
}>();

const link_field_creator = ref<LinkFieldCreator | null>(null);

const labeled_field = { field_id: props.field.field_id, label: props.field.label };
const field_option = {
    can_create_artifact: false,
};
const allowed_link_types: ReadonlyArray<AllowedLinkTypeRepresentation> = props.field.allowed_types;

const current_user = strictInject(CURRENT_USER);

const tracker_id = strictInject(TRACKER_ID);
const tracker_shortname = strictInject(TRACKER_SHORTNAME);
const tracker_color = strictInject(TRACKER_COLOR);

const project_id = strictInject(PROJECT_ID);

onMounted(() => {
    const current_artifact = Option.nothing<CurrentArtifactIdentifier>();
    const parent_artifact: Option<ParentArtifactIdentifier> =
        Option.nothing<ParentArtifactIdentifier>();
    const parent_tracker = Option.nothing<ParentTrackerIdentifier>();

    link_field_creator.value = LinkFieldCreator(
        EventDispatcher(),
        LinksStore(),
        NewLinksStore(),
        LinksMarkedForRemovalStore(),
        current_artifact,
        ArtifactCrossReference.fromCurrentArtifact(
            current_artifact,
            TrackerShortname.fromString(tracker_shortname),
            tracker_color,
        ),
        CurrentProjectIdentifier.fromId(project_id),
        CurrentTrackerIdentifier.fromId(tracker_id),
        parent_artifact,
        parent_tracker,
        UserIdentifier.fromId(
            current_user.match(
                (user) => user.id,
                () => 0,
            ),
        ),
        getLocaleWithDefault(document),
    );
});
</script>

<style lang="scss">
// link field is displaying its own label, we need to hide it
// so that we can do what we want with the label in field usage (e.g edit inline)
// stylelint-disable-next-line selector-class-pattern
.tracker-admin-fields-link-field .tracker_formelement_label {
    display: none;
}
</style>

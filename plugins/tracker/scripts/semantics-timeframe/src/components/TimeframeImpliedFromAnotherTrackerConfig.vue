<!--
  - Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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
    <div id="timeframe-admin-section-implied-from-another-tracker">
        <div v-if="can_semantic_be_implied" class="tlp-form-element">
            <label class="tlp-label" for="timeframe-tracker-selector">
                <translate>Tracker</translate>
                <i class="fa-solid fa-asterisk" aria-hidden="true"></i>
            </label>
            <select
                id="timeframe-tracker-selector"
                name="implied-from-tracker-id"
                data-test="implied-from-tracker-select-box"
                v-model="user_select_implied_from_tracker_id"
                class="tlp-select tlp-select-adjusted"
                required
            >
                <option value="" disabled>{{ $gettext("Choose a tracker...") }}</option>
                <option
                    v-for="tracker in suitable_trackers"
                    v-bind:value="tracker.id"
                    v-bind:key="tracker.id"
                >
                    {{ tracker.name }}
                </option>
            </select>
            <p class="tlp-text-info">
                <i class="fa-solid fa-life-ring"></i>
                {{
                    $gettext(
                        "You can't find the tracker you are looking for? Make sure it has an artifact link field, and that its semantic is not inherited from another tracker.",
                    )
                }}
            </p>
        </div>
        <div
            v-else-if="has_other_trackers_implying_their_timeframes"
            class="tlp-alert-danger"
            data-test="error-message-other-trackers-implying-their-timeframe"
        >
            {{
                $gettext(
                    "You cannot make this semantic inherit from another tracker because some other trackers are inheriting their own semantics timeframe from this one.",
                )
            }}
        </div>
        <div
            v-else
            class="tlp-alert-danger"
            data-test="error-message-no-art-link-field"
            v-dompurify-html="missing_artifact_link_field_error_message"
        ></div>
    </div>
</template>

<script setup lang="ts">
import type { Tracker } from "../type";
import { computed, onMounted, ref } from "vue";
import { useGettext } from "vue3-gettext";

const props = defineProps<{
    suitable_trackers: Tracker[];
    has_artifact_link_field: boolean;
    implied_from_tracker_id: number | "";
    current_tracker_id: number;
    has_other_trackers_implying_their_timeframes: boolean;
}>();

const user_select_implied_from_tracker_id = ref<number | "">("");

const gettext_provider = useGettext();

onMounted((): void => {
    user_select_implied_from_tracker_id.value = props.implied_from_tracker_id;
});

const can_semantic_be_implied = computed((): boolean => {
    return props.has_artifact_link_field && !props.has_other_trackers_implying_their_timeframes;
});

const missing_artifact_link_field_error_message = computed((): string => {
    const tracker_fields_admin_url = `/plugins/tracker/?tracker=${props.current_tracker_id}&func=admin-formElements`;

    return gettext_provider.interpolate(
        gettext_provider.$gettext(
            `Please <a href="%{ tracker_fields_admin_url }">add an artifact link field</a> to your tracker first.`,
        ),
        { tracker_fields_admin_url },
    );
});
</script>

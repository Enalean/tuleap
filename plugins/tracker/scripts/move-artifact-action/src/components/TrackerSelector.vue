<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="move-artifact-tracker-selector-section">
        <label
            data-test="tracker-selector-label"
            for="move-artifact-tracker-selector"
            v-bind:title="selector_title"
        >
            <translate>Destination tracker</translate>
            <span class="highlight">*</span>
        </label>
        <select
            id="move-artifact-tracker-selector"
            name="move-artifact-tracker-selector"
            data-test="move-artifact-tracker-selector"
            v-model="selected_tracker"
            ref="move_artifact_tracker_selector"
        >
            <option
                v-for="tracker of tracker_list_with_disabled_from"
                v-bind:key="tracker.id"
                v-bind:value="tracker.id"
                v-bind:disabled="tracker.disabled"
            >
                {{ tracker.label }}
            </option>
        </select>
    </div>
</template>

<script>
import { mapGetters } from "vuex";
import { createListPicker } from "@tuleap/list-picker";

export default {
    name: "TrackerSelector",
    data() {
        return {
            list_picker: null,
        };
    },
    computed: {
        ...mapGetters(["tracker_list_with_disabled_from"]),
        does_tracker_list_contain_from_tracker() {
            return this.tracker_list_with_disabled_from.some(({ disabled }) => disabled === true);
        },
        selected_tracker: {
            get() {
                return this.$store.state.selected_tracker;
            },
            set(tracker_id) {
                this.$store.commit("saveSelectedTrackerId", tracker_id);
            },
        },
        selector_title() {
            return this.does_tracker_list_contain_from_tracker
                ? this.$gettext("An artifact cannot be moved in the same tracker")
                : "";
        },
    },
    mounted() {
        this.list_picker = createListPicker(this.$refs.move_artifact_tracker_selector, {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: this.$gettext("Choose tracker..."),
        });
    },
    beforeDestroy() {
        this.list_picker.destroy();
    },
};
</script>

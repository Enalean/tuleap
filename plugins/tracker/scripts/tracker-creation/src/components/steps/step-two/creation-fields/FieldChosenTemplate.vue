<!--
  - Copyright (c) Enalean, 2020 - present. All Rights Reserved.
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
    <div class="tlp-property">
        <label class="tlp-label" v-translate>Chosen template</label>
        <p class="tracker-information-selected-template">
            {{ selected_template_name }}
        </p>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { State, Getter } from "vuex-class";
import { Component } from "vue-property-decorator";
import { TrackerToBeCreatedMandatoryData, Tracker } from "../../../../store/type";

@Component
export default class FieldChosenTemplate extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    @State
    readonly selected_tracker_template!: Tracker;

    @Getter
    readonly is_created_from_empty!: boolean;

    @Getter
    readonly is_a_duplication!: boolean;

    @Getter
    readonly is_a_xml_import!: boolean;

    selected_template_name = "";

    mounted(): void {
        if (this.is_a_duplication) {
            this.selected_template_name = this.selected_tracker_template.name;
        } else if (this.is_created_from_empty) {
            this.selected_template_name = this.$gettext("Empty");
        } else if (this.is_a_xml_import) {
            this.selected_template_name = this.tracker_to_be_created.name;
        }
    }
}
</script>

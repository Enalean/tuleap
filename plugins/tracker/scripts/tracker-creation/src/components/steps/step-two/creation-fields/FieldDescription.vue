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
    <div class="tlp-form-element">
        <label class="tlp-label" for="tracker-description" v-translate>Description</label>
        <textarea
            class="tlp-textarea tlp-textarea-large"
            id="tracker-description"
            name="tracker-description"
            v-bind:placeholder="placeholder"
            rows="4"
        ></textarea>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { State } from "vuex-class";
import { Component } from "vue-property-decorator";
import type { TrackerToBeCreatedMandatoryData } from "../../../../store/type";

@Component
export default class FieldDescription extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    get placeholder(): string {
        return this.$gettextInterpolate(
            this.$gettext("My %{ tracker_name } tracker description..."),
            { tracker_name: this.tracker_to_be_created.name },
            true,
        );
    }
}
</script>

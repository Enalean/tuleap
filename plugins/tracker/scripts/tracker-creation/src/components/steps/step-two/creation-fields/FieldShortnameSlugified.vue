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
    <div class="tlp-form-element" v-if="!isTrackerNameEmpty()" v-on:click="toggleManualMode()">
        <div
            data-test="tracker-shortname-slugified-mode"
            id="tracker-shortname-slugified-mode-label"
            for="tracker-shortname-slugified"
        >
            ↳&nbsp;
            <translate>Tracker shortname:</translate>
            <span data-test="tracker-shortname-slugified" class="tracker-shortname-slugified">
                {{ tracker_to_be_created.shortname }}
            </span>
            <i class="fa fa-pencil"></i>
        </div>
        <input
            type="hidden"
            id="tracker-shortname-slugified"
            class="tlp-input"
            data-test="tracker-shortname-slugified-input"
            name="tracker-shortname"
            v-bind:value="tracker_to_be_created.shortname"
        />
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { State, Mutation } from "vuex-class";
import { Component } from "vue-property-decorator";
import { TrackerToBeCreatedMandatoryData } from "../../../../store/type";

@Component({
    components: {},
})
export default class FieldShortnameSlugified extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    @Mutation
    readonly setSlugifyShortnameMode!: (is_active: boolean) => void;

    toggleManualMode(): void {
        this.setSlugifyShortnameMode(false);
    }

    isTrackerNameEmpty(): boolean {
        return this.tracker_to_be_created.name.length === 0;
    }
}
</script>

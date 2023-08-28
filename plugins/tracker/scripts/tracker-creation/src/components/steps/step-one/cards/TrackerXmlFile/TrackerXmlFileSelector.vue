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
    <div
        class="tlp-form-element card-content card-tracker-template-selector"
        v-bind:class="{ 'tlp-form-element-error': has_xml_file_error }"
    >
        <label class="tlp-label card-title" for="tracker-creation-xml-file-selector" v-translate>
            Tracker XML file
        </label>
        <input
            ref="file_input"
            v-if="should_render_fresh_input"
            id="tracker-creation-xml-file-selector"
            class="tlp-input"
            type="file"
            name="tracker-xml-file"
            accept="text/xml"
            data-test="tracker-creation-xml-file-selector"
            v-on:change="setTrackerToBeCreatedFromXml"
        />
        <p v-if="has_xml_file_error" class="tlp-text-danger tracker-creation-xml-file-error">
            <translate>The provided file is not a valid XML file</translate>
            <i class="far fa-frown"></i>
        </p>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Mutation, State } from "vuex-class";
import { Component } from "vue-property-decorator";

@Component
export default class TrackerXmlFileSelector extends Vue {
    @State
    readonly selected_xml_file_input!: HTMLInputElement | null;

    @State
    readonly has_xml_file_error!: boolean;

    @Mutation
    readonly setSelectedTrackerXmlFileInput!: (list: HTMLInputElement) => void;

    @Mutation
    readonly setTrackerToBeCreatedFromXml!: () => void;

    should_render_fresh_input = true;

    mounted(): void {
        if (this.selected_xml_file_input === null) {
            this.initXmlFileInput();

            return;
        }

        this.should_render_fresh_input = false;
        this.$el.insertBefore(this.selected_xml_file_input, this.$el.children[1]);
    }

    initXmlFileInput(): void {
        const input = this.$refs.file_input;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        this.setSelectedTrackerXmlFileInput(input);
    }
}
</script>

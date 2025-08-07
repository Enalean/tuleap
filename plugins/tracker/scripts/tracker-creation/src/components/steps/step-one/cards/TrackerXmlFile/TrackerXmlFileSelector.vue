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
        v-bind:class="{ 'tlp-form-element-error': hasXMLError() }"
        ref="file_wrapper"
    >
        <label class="tlp-label card-title" for="tracker-creation-xml-file-selector">
            {{ $gettext("Tracker XML file") }}
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
            v-on:change="handleFileChange"
        />
        <p v-if="hasXMLError()" class="tlp-text-danger tracker-creation-xml-file-error">
            {{ $gettext("The provided file is not a valid XML file") }}
            <i class="far fa-frown"></i>
        </p>
    </div>
</template>
<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useGettext } from "vue3-gettext";
import { useStore } from "vuex-composition-helpers";

const { $gettext } = useGettext();

const file_wrapper = ref<HTMLElement | null>(null);
const file_input = ref<HTMLInputElement | null>(null);
const should_render_fresh_input = ref(true);

const store = useStore();

const handleFileChange = (): void => {
    store.commit("setTrackerToBeCreatedFromXml");
};

function hasXMLError(): boolean {
    return store.state.has_xml_file_error;
}

function initXmlFileInput(): void {
    if (!(file_input.value instanceof HTMLInputElement)) {
        return;
    }

    store.commit("setSelectedTrackerXmlFileInput", file_input.value);
}

onMounted(() => {
    if (store.state.selected_xml_file_input === null) {
        initXmlFileInput();
        return;
    }

    should_render_fresh_input.value = false;

    if (file_wrapper.value && file_wrapper.value.children[1]) {
        file_wrapper.value.insertBefore(
            store.state.selected_xml_file_input,
            file_wrapper.value.children[1],
        );
    }
});
</script>

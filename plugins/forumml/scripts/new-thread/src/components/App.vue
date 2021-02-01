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
  -
  -->

<template>
    <div class="forumml-post-new-thread-cc-files">
        <p>
            <button
                type="button"
                class="tlp-button-small tlp-button-primary tlp-button-outline"
                v-on:click="addCcRow"
                data-test="add-cc"
            >
                <translate>Add Cc</translate>
            </button>
            <button
                type="button"
                class="tlp-button-small tlp-button-primary tlp-button-outline"
                v-on:click="addFileRow"
                data-test="add-file"
            >
                <translate>Add attachment</translate>
            </button>
        </p>

        <div class="tlp-form-element" v-if="cc_collection.length > 0">
            <translate tag="label" class="tlp-label" for="forumml-cc-0">Cc</translate>
            <cc-input
                v-for="(cc, index) in cc_collection"
                v-bind:key="'cc-' + index"
                v-bind:cc="cc"
                v-bind:index="index"
                v-on:removeCc="cc_collection.splice(index, 1)"
                v-model="cc_collection[index]"
            />
        </div>

        <div class="tlp-form-element" v-if="file_collection.length > 0">
            <translate tag="label" class="tlp-label" for="forumml-file-0">Attachment</translate>
            <file-input
                v-for="(file, index) in file_collection"
                v-bind:key="'file-' + file.id"
                v-bind:index="file.id"
                v-on:removeFile="file_collection.splice(index, 1)"
                v-model="file.name"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import CcInput from "./CcInput.vue";
import FileInput from "./FileInput.vue";

let id = 0;

interface FileEntry {
    id: number;
    name: string;
}

@Component({
    components: { CcInput, FileInput },
})
export default class App extends Vue {
    private cc_collection: string[] = [];
    private file_collection: FileEntry[] = [];

    addCcRow(): void {
        this.cc_collection.push("");
    }

    addFileRow(): void {
        const current_id = ++id;
        this.file_collection.splice(current_id, 0, { id: current_id, name: "" });
    }
}
</script>

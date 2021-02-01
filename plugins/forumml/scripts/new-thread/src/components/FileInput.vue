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
    <div class="tlp-form-element tlp-form-element-append forumml-post-new-thread-file-component">
        <span
            class="tlp-button-secondary forumml-post-new-thread-file-button"
            v-bind:class="file_button_class"
        >
            <template v-if="label">
                {{ label }}
            </template>
            <template v-else>
                <i class="fa fa-upload tlp-button-icon"></i>
                <translate>Browse...</translate>
            </template>

            <input
                type="file"
                class="tlp-input forumml-post-new-thread-file-input"
                v-bind:id="'forumml-file-' + index"
                name="files[]"
                v-on:input="input"
                ref="input"
                required
            />
        </span>
        <button
            type="button"
            class="tlp-append tlp-button-primary tlp-button-outline"
            v-on:click="$emit('removeFile')"
            data-test="delete-file"
        >
            <i class="fas fa-trash" aria-hidden="true"></i>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class FileInput extends Vue {
    @Prop({ required: true })
    readonly index!: number;

    private label = "";

    mounted(): void {
        this.$refs.input.focus();
        this.setLabelAccordingToSelectedFile();
    }

    setLabelAccordingToSelectedFile(): void {
        this.label = this.$refs.input.files.length ? this.$refs.input.files[0].name : "";
    }

    input(): void {
        this.setLabelAccordingToSelectedFile();
        this.$emit("input", this.label);
    }

    get file_button_class(): string[] {
        if (this.label.length === 0) {
            return [];
        }

        return ["forumml-post-new-thread-file-button-with-custom-label"];
    }
}
</script>

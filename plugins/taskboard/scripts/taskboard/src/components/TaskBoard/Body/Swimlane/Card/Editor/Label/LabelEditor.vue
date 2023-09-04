<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="taskboard-card-label-editor">
        <textarea
            class="tlp-textarea taskboard-card-label-input-mirror"
            v-bind:value="value"
            rows="1"
            ref="mirror"
        ></textarea>
        <textarea
            class="tlp-textarea taskboard-card-label-input"
            v-bind:value="value"
            v-on:input="onInputEmit"
            v-on:keydown.enter="enter"
            v-on:keyup="keyup"
            v-bind:rows="rows"
            v-bind:placeholder="$gettext('Card label…')"
            v-bind:aria-label="$gettext('Set card label')"
            v-bind:readonly="readonly"
            ref="textarea"
            data-test="label-editor"
            data-navigation="add-form"
        ></textarea>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop, Ref } from "vue-property-decorator";
import { autoFocusAutoSelect } from "../../../../../../../helpers/autofocus-autoselect";

const LINE_HEIGHT_IN_PX = 18;
const TOP_AND_BOTTOM_PADDING_IN_PX = 16;

@Component
export default class LabelEditor extends Vue {
    @Prop({ required: true })
    readonly value!: string;

    @Prop({ required: false, default: false })
    readonly readonly!: boolean;

    @Ref() textarea!: HTMLTextAreaElement;
    @Ref() mirror!: HTMLTextAreaElement;

    rows = 1;

    mounted(): void {
        setTimeout(this.computeRows, 10);
        autoFocusAutoSelect(this.textarea);
    }

    enter(event: KeyboardEvent): void {
        if (!event.shiftKey) {
            this.$emit("save");
        }
    }

    keyup(): void {
        this.computeRows();
    }

    computeRows(): void {
        this.rows = Math.ceil(
            (this.mirror.scrollHeight - TOP_AND_BOTTOM_PADDING_IN_PX) / LINE_HEIGHT_IN_PX,
        );
    }

    onInputEmit($event: Event): void {
        if (!($event.target instanceof HTMLTextAreaElement)) {
            return;
        }

        this.$emit("input", $event.target.value);
    }
}
</script>

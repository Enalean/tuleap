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
        <pre class="taskboard-card-label-input-mirror" ref="mirror">{{ value }}</pre>
        <textarea class="tlp-textarea taskboard-card-label-input"
                  v-model="value"
                  v-on:keyup="keyup"
                  v-bind:rows="rows"
                  ref="textarea"
        ></textarea>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { Card } from "../../../../../../../type";
import { autoFocusAutoSelect } from "../../../../../../../helpers/autofocus-autoselect";

const LINE_HEIGHT_IN_PX = 22;
const TOP_AND_BOTTOM_PADDING_IN_PX = 16;

@Component
export default class EditLabel extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    value = "";
    rows = 1;

    mounted(): void {
        this.value = this.card.label;
        setTimeout(this.computeRows, 10);

        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        const textarea = this.$refs.textarea as HTMLTextAreaElement;
        autoFocusAutoSelect(textarea);
    }

    keyup(): void {
        this.computeRows();
    }

    computeRows(): void {
        // eslint-disable-next-line @typescript-eslint/consistent-type-assertions
        const mirror = this.$refs.mirror as HTMLElement;
        this.rows = Math.round(
            (mirror.clientHeight - TOP_AND_BOTTOM_PADDING_IN_PX) / LINE_HEIGHT_IN_PX
        );
    }
}
</script>

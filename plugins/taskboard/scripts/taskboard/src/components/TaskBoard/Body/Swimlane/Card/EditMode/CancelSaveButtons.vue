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
    <div class="taskboard-card-cancel-save-buttons" data-not-drag-handle="true">
        <button type="button"
                class="tlp-button tlp-button-primary tlp-button-small taskboard-card-save-button"
                v-on:click="save"
                data-test="save"
        >
            <i class="fa fa-tlp-enter-key tlp-button-icon"></i>
            <translate>Save</translate>
        </button>
        <button type="button"
                class="tlp-button tlp-button-primary tlp-button-outline tlp-button-small taskboard-card-cancel-button"
                v-on:click="cancel"
                data-test="cancel"
        >
            <i class="fa fa-tlp-esc-key tlp-button-icon"></i>
            <translate>Cancel</translate>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { TaskboardEvent } from "../../../../../../type";
import EventBus from "../../../../../../helpers/event-bus";

@Component
export default class CancelSaveButtons extends Vue {
    mounted(): void {
        EventBus.$on(TaskboardEvent.ESC_KEY_PRESSED, this.cancel);
    }

    beforeDestroy(): void {
        EventBus.$off(TaskboardEvent.ESC_KEY_PRESSED, this.cancel);
    }

    cancel(): void {
        this.$emit("cancel");
    }

    save(): void {
        this.$emit("save");
    }
}
</script>

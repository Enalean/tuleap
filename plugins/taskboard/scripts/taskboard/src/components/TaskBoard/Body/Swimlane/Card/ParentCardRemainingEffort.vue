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
    <span
        v-if="has_remaining_effort"
        class="taskboard-card-remaining-effort taskboard-no-text-selection"
        v-bind:class="additional_classes"
        v-on:click="editRemainingEffort"
        v-on:keyup.enter="editRemainingEffort"
        v-bind:tabindex="tabindex"
        v-bind:role="role"
        v-bind:title="$gettext('Remaining effort')"
        data-not-drag-handle="true"
        draggable="true"
        data-shortcut="edit-remaining-effort"
    >
        <edit-remaining-effort
            v-if="is_in_edit_mode"
            v-bind:card="card"
            v-on:editor-closed="$emit('editor-closed')"
            data-test="edit-remaining-effort"
        />
        <template v-else>{{ remaining_effort }}</template>
        <i class="fas fa-long-arrow-alt-right" aria-hidden="true"></i>
        <i class="fa" aria-hidden="true" v-bind:class="icon"></i>
    </span>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import type { Card } from "../../../../../type";
import EditRemainingEffort from "./RemainingEffort/EditRemainingEffort.vue";
@Component({
    components: { EditRemainingEffort },
})
export default class ParentCardRemainingEffort extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get has_remaining_effort(): boolean {
        return (
            this.card &&
            this.card.remaining_effort !== null &&
            this.card.remaining_effort.value !== null
        );
    }

    get remaining_effort(): string {
        if (!this.card.remaining_effort?.value) {
            return "";
        }

        return String(this.card.remaining_effort.value);
    }

    get additional_classes(): string {
        const classes = [`tlp-badge-${this.card.color}`, `tlp-swatch-${this.card.color}`];

        if (this.can_update_remaining_effort) {
            classes.push("taskboard-card-remaining-effort-editable");
        }

        if (this.is_in_edit_mode) {
            classes.push("taskboard-card-remaining-effort-edit-mode");
        }

        return classes.join(" ");
    }

    get can_update_remaining_effort(): boolean {
        if (!this.card.remaining_effort) {
            return false;
        }

        return this.card.remaining_effort.can_update;
    }

    get icon(): string {
        if (this.card.remaining_effort && this.card.remaining_effort.is_being_saved) {
            return "fa-circle-o-notch fa-spin";
        }

        return "fa-flag-checkered";
    }

    get is_in_edit_mode(): boolean {
        if (!this.card.remaining_effort) {
            return false;
        }

        return this.card.remaining_effort.is_in_edit_mode;
    }

    get role(): string {
        return this.can_update_remaining_effort ? "button" : "";
    }

    get tabindex(): number {
        return this.can_update_remaining_effort ? 0 : -1;
    }

    editRemainingEffort(): void {
        if (this.can_update_remaining_effort && this.card.remaining_effort) {
            this.card.remaining_effort.is_in_edit_mode = true;
        }
    }
}
</script>

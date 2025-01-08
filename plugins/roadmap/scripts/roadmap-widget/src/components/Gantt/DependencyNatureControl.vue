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
    <div
        class="tlp-form-element roadmap-gantt-control"
        v-bind:class="{ 'tlp-form-element-disabled': is_form_element_disabled }"
    >
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id">
            {{ $gettext("Links") }}
        </label>
        <select
            class="tlp-select tlp-select-small tlp-select-adjusted"
            v-bind:id="id"
            v-on:change="onchange"
            data-test="select-links"
            v-bind:disabled="is_select_disabled"
            v-bind:title="title"
        >
            <option
                v-bind:value="NONE_SPECIALVALUE"
                v-bind:selected="value === null"
                data-test="option-none"
            >
                {{ $gettext("None") }}
            </option>
            <option
                v-for="nature of sorted_natures"
                v-bind:key="nature"
                v-bind:value="nature"
                v-bind:selected="value === nature"
                v-bind:data-test="'option-' + nature"
            >
                {{ available_natures.get(nature) }}
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { namespace } from "vuex-class";
import { getUniqueId } from "../../helpers/uniq-id-generator";
import type { NaturesLabels } from "../../type";

const tasks = namespace("tasks");

@Component
export default class DependencyNatureControl extends Vue {
    @Prop({ required: true })
    readonly value!: string | null;

    @Prop({ required: true })
    readonly available_natures!: NaturesLabels;

    @tasks.Getter
    private readonly has_at_least_one_row_shown!: boolean;

    readonly NONE_SPECIALVALUE = "-1";

    get id(): string {
        return getUniqueId("roadmap-gantt-links");
    }

    get sorted_natures(): string[] {
        return Array.from(this.available_natures.keys()).sort((a, b) => a.localeCompare(b));
    }

    get is_form_element_disabled(): boolean {
        return !this.has_at_least_one_row_shown;
    }

    get is_select_disabled(): boolean {
        return this.is_form_element_disabled || this.available_natures.size <= 0;
    }

    get title(): string {
        return this.is_select_disabled
            ? this.$gettext("Displayed artifacts don't have any links to each other.")
            : "";
    }

    onchange($event: Event): void {
        if ($event.target instanceof HTMLSelectElement) {
            let value: string | null = $event.target.value;
            if (value === this.NONE_SPECIALVALUE) {
                value = null;
            }

            this.$emit("input", value);
        }
    }
}
</script>

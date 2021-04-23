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
    <div class="tlp-form-element roadmap-gantt-control">
        <label class="tlp-label roadmap-gantt-control-label" v-bind:for="id" v-translate>
            Links
        </label>
        <select
            class="tlp-select tlp-select-small tlp-select-adjusted"
            v-bind:id="id"
            v-on:change="onchange"
            data-test="select-links"
        >
            <option
                v-bind:value="NONE_SPECIALVALUE"
                v-bind:selected="value === null"
                v-translate
                data-test="option-none"
            >
                None
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
import { getUniqueId } from "../../helpers/uniq-id-generator";
import type { NaturesLabels } from "../../type";

@Component
export default class DependencyNatureControl extends Vue {
    @Prop({ required: true })
    readonly value!: string | null;

    @Prop({ required: true })
    readonly available_natures!: NaturesLabels;

    private readonly NONE_SPECIALVALUE = "-1";

    get id(): string {
        return getUniqueId("roadmap-gantt-links");
    }

    get sorted_natures(): string[] {
        return Array.from(this.available_natures.keys()).sort((a, b) => a.localeCompare(b));
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

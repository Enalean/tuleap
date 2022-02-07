<!--
  - Copyright (c) Enalean 2022 -  Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
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
    <div class="tlp-form-element document-search-criterion">
        <label class="tlp-label" for="document-type-search" v-translate>Type</label>
        <select
            class="tlp-select"
            id="document-type-search"
            v-on:change="$emit('input', $event.target.value)"
            data-test="type"
        >
            <option value="" v-bind:selected="isSelected('')" v-translate>Any</option>
            <option
                value="folder"
                v-bind:selected="isSelected('folder')"
                data-test="type-folder"
                v-translate
            >
                Folder
            </option>
            <option value="file" v-bind:selected="isSelected('file')" v-translate>File</option>
            <option value="link" v-bind:selected="isSelected('link')" v-translate>Link</option>
            <option value="embedded" v-bind:selected="isSelected('embedded')" v-translate>
                Embedded file
            </option>
            <option
                value="wiki"
                v-bind:selected="isSelected('wiki')"
                v-if="user_can_create_wiki"
                data-test="type-wiki"
            >
                <translate>Wiki page</translate>
            </option>
            <option value="empty" v-bind:selected="isSelected('empty')" v-translate>
                Empty document
            </option>
        </select>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import Component from "vue-class-component";
import { namespace } from "vuex-class";
import { Prop } from "vue-property-decorator";

const configuration = namespace("configuration");

@Component
export default class CriterionType extends Vue {
    @configuration.State
    readonly user_can_create_wiki!: boolean;

    @Prop({ required: true })
    readonly value!: string;

    isSelected(option: string): boolean {
        return option === this.value;
    }
}
</script>

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
    <field-shortname-slugified v-if="can_display_slugify_mode && !is_shortname_already_used" />
    <div
        class="tlp-form-element"
        v-bind:class="{
            'tlp-form-element-error': !is_shortname_valid || is_shortname_already_used,
        }"
        v-else
    >
        <label class="tlp-label" for="tracker-shortname">
            <translate>Shortname</translate>
            <i class="fa fa-asterisk"></i>
        </label>
        <input
            v-bind:pattern="validation_pattern"
            type="text"
            maxlength="25"
            class="tlp-input tlp-input-large"
            id="tracker-shortname"
            name="tracker-shortname"
            data-test="tracker-shortname-input"
            v-on:keyup="setTrackerShortName($event)"
            v-bind:value="tracker_to_be_created.shortname"
            required
        />
        <p class="tlp-text-info tracker-shortname-input-helper">
            <i class="far fa-fw fa-life-ring"></i>
            <translate>Avoid spaces and punctuation</translate>
        </p>
        <p
            class="tlp-text-danger tracker-shortname-input-helper"
            data-test="shortname-error"
            v-if="!is_shortname_valid"
        >
            <i class="fa fa-fw fa-exclamation-circle"></i>
            <translate>
                The tracker shortname must have a length between 1 and 25 characters. It can only
                contain alphanumerical characters and underscores.
            </translate>
        </p>
        <p
            class="tlp-text-danger"
            data-test="shortname-taken-error"
            v-if="is_shortname_already_used"
        >
            <i class="fa fa-fw fa-exclamation-circle"></i>
            <translate>
                The chosen shortname already exist in this project, please choose another one.
            </translate>
        </p>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Getter, State } from "vuex-class";
import { Component } from "vue-property-decorator";
import type { TrackerToBeCreatedMandatoryData } from "../../../../store/type";
import FieldShortnameSlugified from "./FieldShortnameSlugified.vue";
import { TRACKER_SHORTNAME_FORMAT } from "../../../../constants";

@Component({
    components: {
        FieldShortnameSlugified,
    },
})
export default class FieldShortname extends Vue {
    @State
    readonly tracker_to_be_created!: TrackerToBeCreatedMandatoryData;

    @Getter
    readonly can_display_slugify_mode!: boolean;

    setTrackerShortName(event: Event): void {
        if (!(event.target instanceof HTMLInputElement)) {
            return;
        }
        this.$store.commit("setTrackerShortName", event.target.value);
    }

    @Getter
    readonly is_shortname_valid!: boolean;

    @Getter
    readonly is_shortname_already_used!: boolean;

    get validation_pattern(): string {
        let string_format = TRACKER_SHORTNAME_FORMAT.toString();
        return string_format.substring(1, string_format.length - 1);
    }
}
</script>

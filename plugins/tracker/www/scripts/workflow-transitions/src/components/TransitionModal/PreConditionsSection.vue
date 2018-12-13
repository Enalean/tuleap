<!--
  - Copyright (c) Enalean, 2018. All Rights Reserved.
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
    <section class="tlp-modal-body-section">
        <h2 class="tlp-modal-subtitle" v-translate>Conditions of the transition</h2>
        <div class="tlp-form-element">
            <label
                for="workflow-configuration-permission"
                class="tlp-label"
                v-translate
            >Groups that may make the transition</label>
            <select v-if="is_loading_modal"
                    class="tlp-select tlp-skeleton-select"
                    disabled
            ></select>
            <select v-else
                    id="workflow-configuration-permission"
                    class="tlp-select"
                    multiple
            >
                <option
                    v-for="user_group in user_groups"
                    v-bind:key="user_group.id"
                    v-bind:value="user_group.id"
                >{{ user_group.label }}</option>
            </select>
        </div>
        <div class="tlp-form-element">
            <label
                for="workflow-configuration-not-empty-fields"
                class="tlp-label"
                v-translate
            >Field(s) that must not be empty</label>
            <select
                id="workflow-configuration-not-empty-fields"
                class="tlp-select"
                multiple
                ref="not_empty_fields_select"
                style="width: 100%;"
            >
                <option
                    v-for="field in writable_fields"
                    v-bind:key="field.field_id"
                    v-bind:value="field"
                >{{ field.label }}</option>
            </select>
        </div>
        <div class="tlp-form-element" v-if="!is_transition_from_new_artifact">
            <label class="tlp-label tlp-checkbox">
                <input type="checkbox" name="transition-comment-not-empty" value="1">
                <translate>Comment must not be empty</translate>
            </label>
        </div>
    </section>
</template>
<script>
import { select2 } from "tlp";
import { mapState, mapGetters } from "vuex";
import {
    STRUCTURAL_FIELDS,
    READ_ONLY_FIELDS,
    COMPUTED_FIELD
} from "../../../../constants/fields-constants.js";

const fields_blacklist = [...STRUCTURAL_FIELDS, ...READ_ONLY_FIELDS, COMPUTED_FIELD];

export default {
    name: "PreConditionsSection",
    computed: {
        ...mapState("transitionModal", ["user_groups", "is_loading_modal"]),
        ...mapState({
            writable_fields: state => {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields.filter(
                    field => !fields_blacklist.includes(field.type)
                );
            }
        }),
        ...mapGetters("transitionModal", ["is_transition_from_new_artifact"])
    },
    mounted() {
        select2(this.$refs.not_empty_fields_select, {
            placeholder: this.$gettext("Choose a field")
        });
    }
};
</script>

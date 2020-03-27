<!--
  - Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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
    <post-action v-bind:post_action="post_action">
        <div class="tracker-workflow-transition-modal-action-details-element tlp-form-element">
            <label for="workflow-transition-modal-hidden-fieldsets" class="tlp-label">
                <translate>Fieldsets that will be hidden by default</translate>
                <i class="fa fa-asterisk"></i>
            </label>
            <multi-select
                id="workflow-transition-modal-hidden-fieldsets"
                class="tlp-select"
                required
                v-bind:configuration="{
                    width: '100%',
                    placeholder: hidden_fieldsets_select_placeholder,
                }"
                v-model="hidden_fieldset_ids"
                v-bind:disabled="is_modal_save_running"
            >
                <option
                    v-for="fieldset in writable_fieldsets"
                    v-bind:key="fieldset.field_id"
                    v-bind:value="fieldset.field_id"
                    v-bind:data-test="`fieldset_${fieldset.field_id}`"
                >
                    {{ fieldset.label }}
                </option>
            </multi-select>
            <p class="tlp-text-info" v-translate>
                Selected fieldsets won't be displayed by default but users can make them visible if
                they want to. It's not an Access Control option.
            </p>
        </div>
    </post-action>
</template>
<script>
import PostAction from "./PostAction.vue";
import { mapState, mapGetters } from "vuex";
import MultiSelect from "../MultiSelect.vue";
import { CONTAINER_FIELDSET } from "../../../../../constants/fields-constants.js";
import { compare } from "../../../support/string.js";

export default {
    name: "HiddenFieldsetsAction",
    components: { PostAction, MultiSelect },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
    computed: {
        ...mapState(["current_tracker"]),
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        ...mapGetters(["current_workflow_field"]),
        ...mapState({
            writable_fieldsets(state) {
                if (state.current_tracker === null) {
                    return [];
                }
                return state.current_tracker.fields
                    .filter((field) => field.type === CONTAINER_FIELDSET)
                    .sort((field1, field2) => compare(field1.label, field2.label));
            },
        }),
        hidden_fieldsets_select_placeholder() {
            return this.$gettext("Choose a fieldset");
        },
        hidden_fieldset_ids: {
            get() {
                return this.post_action.fieldset_ids;
            },
            set(fieldset_ids) {
                this.$store.commit("transitionModal/updateHiddenFieldsetsPostActionFieldsetIds", {
                    post_action: this.post_action,
                    fieldset_ids,
                });
            },
        },
    },
};
</script>

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
                {{ $gettext("Fieldsets that will be hidden by default") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <select
                id="workflow-transition-modal-hidden-fieldsets"
                multiple
                required
                v-model="hidden_fieldset_ids"
                v-bind:disabled="is_modal_save_running"
                ref="workflow_transition_modal_hidden_fieldsets"
                v-on:change="updateHiddenFieldsetsPostActionFieldsetIds"
            >
                <option
                    v-for="fieldset in writable_fieldsets"
                    v-bind:key="fieldset.field_id"
                    v-bind:value="fieldset.field_id"
                    v-bind:data-test="`fieldset_${fieldset.field_id}`"
                >
                    {{ fieldset.label }}
                </option>
            </select>
            <p class="tlp-text-info">
                {{
                    $gettext(
                        "Selected fieldsets won't be displayed by default but users can make them visible if they want to. It's not an Access Control option.",
                    )
                }}
            </p>
        </div>
    </post-action>
</template>
<script>
import PostAction from "./PostAction.vue";
import { mapState, mapGetters } from "vuex";
import { CONTAINER_FIELDSET } from "@tuleap/plugin-tracker-constants";
import { compare } from "../../../support/string.js";
import { createListPicker } from "@tuleap/list-picker";

export default {
    name: "HiddenFieldsetsAction",
    components: { PostAction },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
    data() {
        return {
            hidden_fieldset_ids: [],
            list_picker: null,
        };
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
    },
    mounted() {
        this.hidden_fieldset_ids = this.post_action.fieldset_ids;
        this.list_picker = createListPicker(this.$refs.workflow_transition_modal_hidden_fieldsets, {
            locale: document.body.dataset.userLocale,
            is_filterable: true,
            placeholder: this.$gettext("Choose a fieldset"),
        });
    },
    beforeDestroy() {
        this.list_picker.destroy();
    },
    methods: {
        updateHiddenFieldsetsPostActionFieldsetIds() {
            this.$store.commit("transitionModal/updateHiddenFieldsetsPostActionFieldsetIds", {
                post_action: this.post_action,
                fieldset_ids: this.hidden_fieldset_ids,
            });
        },
    },
};
</script>

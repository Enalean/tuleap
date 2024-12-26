<!--
  - Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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
    <div class="tlp-form-element" v-if="!is_transition_from_new_artifact">
        <label
            for="workflow-configuration-not-empty-comment"
            class="tlp-label tlp-checkbox"
            slot="comment-not-empty"
        >
            <input
                id="workflow-configuration-not-empty-comment"
                type="checkbox"
                name="transition-comment-not-empty"
                v-on:change="updateNotEmpty"
                v-bind:disabled="is_modal_save_running"
                data-test="not-empty-comment-checkbox"
                v-bind:checked="transition_comment_not_empty"
            />
            <span>{{ $gettext("Comment must not be empty") }}</span>
        </label>
    </div>
</template>

<script>
import { defineComponent } from "vue";
import { mapGetters, mapState } from "vuex";

export default defineComponent({
    name: "CommentNotEmptyCheckBox",

    computed: {
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        ...mapGetters("transitionModal", ["is_transition_from_new_artifact"]),
        transition_comment_not_empty() {
            if (!this.current_transition) {
                return false;
            }
            return this.current_transition.is_comment_required;
        },
    },
    methods: {
        updateNotEmpty(event) {
            const checkbox = event.target;

            this.$store.commit("transitionModal/updateIsCommentRequired", checkbox.checked);
        },
    },
});
</script>

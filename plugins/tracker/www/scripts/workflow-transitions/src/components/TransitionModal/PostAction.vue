<!--
  - Copyright (c) Enalean, 2019. All Rights Reserved.
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
    <div class="tlp-card tracker-workflow-transition-modal-action-card">
        <div class="tlp-form-element tracker-workflow-transition-modal-action-type">
            <select
                class="tlp-select"
                v-model="post_action_type"
                v-bind:disabled="is_modal_save_running"
            >
                <option v-bind:value="POST_ACTION_TYPE.RUN_JOB" v-translate>Launch a CI job</option>
                <option v-bind:value="POST_ACTION_TYPE.SET_FIELD_VALUE" v-translate>Change the value of a field</option>
            </select>
            <a
                href="javascript:;"
                class="tracker-workflow-transition-modal-action-remove"
                v-on:click.prevent="deletePostAction()"
                v-bind:title="delete_title"
            >
                <i class="fa fa-trash-o"></i>
            </a>
        </div>
        <div class="tracker-workflow-transition-modal-action-details">
            <slot/>
        </div>
    </div>
</template>
<script>
import { POST_ACTION_TYPE } from "../../constants/workflow-constants.js";
import { mapState } from "vuex";

export default {
    name: "PostAction",
    data() {
        return {
            POST_ACTION_TYPE
        };
    },
    props: {
        postAction: {
            type: Object,
            mandatory: true
        }
    },
    computed: {
        ...mapState("transitionModal", ["is_modal_save_running"]),
        delete_title() {
            return this.$gettext("Delete this action");
        },
        post_action_type: {
            get() {
                return this.postAction.type;
            },
            set(type) {
                this.$store.commit("transitionModal/updatePostActionType", {
                    post_action: this.postAction,
                    type
                });
            }
        }
    },
    methods: {
        deletePostAction() {
            if (this.is_modal_save_running) {
                return;
            }
            this.$store.commit("transitionModal/deletePostAction", this.postAction);
        }
    }
};
</script>

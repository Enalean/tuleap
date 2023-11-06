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
            <label v-bind:for="job_url_input_id" class="tlp-label">
                {{ $gettext("Job url") }}
                <i class="fa fa-asterisk"></i>
            </label>
            <input
                v-bind:id="job_url_input_id"
                type="url"
                pattern="^https?://.+"
                class="tlp-input"
                placeholder="https://www.example.com"
                v-model="job_url"
                data-test-type="job-url"
                required
                v-bind:disabled="is_modal_save_running"
            />
            <p class="tlp-text-info">
                {{
                    $gettext("Tuleap will automatically pass the following parameters to the job:")
                }}
            </p>
            <ul class="tlp-text-info">
                <li>
                    {{
                        $gettext(
                            "userId: identifier of Tuleap user who made the transition (integer)",
                        )
                    }}
                </li>
                <li>{{ configure_project_id_message() }}</li>
                <li>{{ configure_tracker_id_message() }}</li>
                <li>
                    {{
                        $gettext(
                            "artifactId: identifier of the artifact where the transition happens (integer)",
                        )
                    }}
                </li>
                <li>{{ configure_transition_id_message() }}</li>
            </ul>
        </div>
    </post-action>
</template>
<script>
import PostAction from "./PostAction.vue";
import { mapState } from "vuex";

export default {
    name: "RunJobAction",
    components: { PostAction },
    props: {
        post_action: {
            type: Object,
            mandatory: true,
        },
    },
    computed: {
        ...mapState(["current_tracker"]),
        ...mapState("transitionModal", ["current_transition", "is_modal_save_running"]),
        job_url_input_id() {
            return `post-action-${this.post_action.unique_id}-job-url`;
        },
        job_url: {
            get() {
                return this.post_action.job_url;
            },
            set(job_url) {
                this.$store.commit("transitionModal/updateRunJobPostActionJobUrl", {
                    post_action: this.post_action,
                    job_url,
                });
            },
        },
    },
    methods: {
        configure_project_id_message() {
            let translated = this.$gettext(
                `projectId: identifier of the current project (ie. %{ project_id }) (integer)`,
            );
            return this.$gettextInterpolate(translated, {
                project_id: this.current_tracker.project.id,
            });
        },
        configure_tracker_id_message() {
            let translated = this.$gettext(
                `trackerId: identifier of the current tracker (ie. %{ tracker_id }) (integer)`,
            );
            return this.$gettextInterpolate(translated, {
                tracker_id: this.current_tracker.id,
            });
        },
        configure_transition_id_message() {
            let translated = this.$gettext(
                `triggerFieldValue: value of current transition target (ie. %{ transition_id }) (string)`,
            );
            return this.$gettextInterpolate(translated, {
                transition_id: this.current_transition.id,
            });
        },
    },
};
</script>

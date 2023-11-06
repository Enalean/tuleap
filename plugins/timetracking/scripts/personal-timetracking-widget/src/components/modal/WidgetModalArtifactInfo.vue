<!--
  - Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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
    <div class="timetracking-details-modal-artifact-infos-container">
        <div class="timetracking-details-modal-artifact-infos">
            <span>{{ $gettext("Submission date") }}</span>
            <span>
                {{ submission_date }}
            </span>
        </div>
        <div class="timetracking-details-modal-artifact-infos">
            <span>
                <svg
                    class="timetracking-details-project-icon"
                    xmlns="http://www.w3.org/2000/svg"
                    width="14"
                    height="9"
                    viewBox="0 0 14 9"
                >
                    <path
                        fill-rule="evenodd"
                        d="M23.5318439,19.3542382 C23.5087512,19.2441277 23.5,19.1247307 23.5,19 C23.5,18.4477153 23.6715729,18 24.5,18 C25.3284271,18 25.5,18.4477153 25.5,19 C25.5,19.1247307 25.4912488,19.2441277 25.4681561,19.3542382 C25.7934549,19.6293899 26,20.0405744 26,20.5 C26,21.3284271 25.3284271,22 24.5,22 C23.6715729,22 23,21.3284271 23,20.5 C23,20.0405744 23.2065451,19.6293899 23.5318439,19.3542382 Z M36,24 L37,24 L37,25 L23,25 L23,24 L27,24 L27,16 L29,16 L30,16 L32,16 L32,20 L35,20 L36,20 L36,21 L36,24 Z M32,21 L32,22 L33,22 L33,21 L32,21 Z M34,21 L34,22 L35,22 L35,21 L34,21 Z M34,23 L34,24 L35,24 L35,23 L34,23 Z M32,23 L32,24 L33,24 L33,23 L32,23 Z M30,23 L30,24 L31,24 L31,23 L30,23 Z M30,21 L30,22 L31,22 L31,21 L30,21 Z M28,23 L28,24 L29,24 L29,23 L28,23 Z M28,21 L28,22 L29,22 L29,21 L28,21 Z M28,19 L28,20 L28.5,20 L29,20 L29,19 L28,19 Z M28,17 L28,18 L28.5,18 L29,18 L29,17 L28,17 Z M30,19 L30,20 L30.5,20 L31,20 L31,19 L30,19 Z M30,17 L30,18 L30.5,18 L31,18 L31,17 L30,17 Z M24,22 L25,22 L25,24 L24,24 L24,22 Z"
                        transform="translate(-23 -16)"
                    />
                </svg>
                {{ $gettext("Project") }}
            </span>
            <span class="timetracking-details-modal-artifact-infos-project-name">
                <a v-bind:href="project_link">
                    {{ current_project.label }}
                </a>
            </span>
        </div>
    </div>
</template>

<script>
import { mapGetters, mapState } from "vuex";
import { formatDateUsingPreferredUserFormat } from "@tuleap/plugin-timetracking-time-formatters";
export default {
    name: "WidgetModalArtifactInfo",
    computed: {
        ...mapGetters(["current_artifact", "current_project"]),
        ...mapState(["user_locale"]),
        project_link() {
            return "/projects/" + this.current_project.shortname;
        },
        submission_date() {
            return formatDateUsingPreferredUserFormat(
                this.current_artifact.submission_date,
                this.user_locale,
            );
        },
    },
};
</script>

<!--
  - Copyright Enalean (c) 2018 - Present. All rights reserved.
  -
  - Tuleap and Enalean names and logos are registrated trademarks owned by
  - Enalean SAS. All other trademarks or names are properties of their respective
  - owners.
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
    <tr>
        <td class="timetracking-widget-artifact-cell">
            <widget-link-to-artifact v-bind:artifact="artifact" />
        </td>
        <td>
            <a v-bind:href="/projects/ + project.shortname">{{ project.label }}</a>
        </td>
        <td class="tlp-table-cell-numeric">
            {{ get_formatted_aggregated_time(timeData) }}
        </td>
        <td class="tlp-table-cell-actions timetracking-details-link-to-open-modal">
            <a
                v-on:click.prevent="show_modal"
                v-bind:href="link_to_artifact_timetracking"
                data-test="timetracking-details"
            >
                {{ $gettext("Details") }}
            </a>
        </td>
        <widget-modal-times ref="timetracking_modal" />
    </tr>
</template>
<script>
import { mapGetters, mapMutations } from "vuex";
import { createModal } from "tlp";
import WidgetLinkToArtifact from "./WidgetLinkToArtifact.vue";
import WidgetModalTimes from "./modal/WidgetModalTimes.vue";

export default {
    name: "WidgetArtifactTableRow",
    components: {
        WidgetLinkToArtifact,
        WidgetModalTimes,
    },
    props: {
        timeData: Array,
    },
    data() {
        return {
            artifact: this.timeData[0].artifact,
            project: this.timeData[0].project,
            modal_simple_content: null,
        };
    },
    computed: {
        ...mapGetters(["get_formatted_aggregated_time"]),
        link_to_artifact_timetracking() {
            return this.artifact.html_url + "&view=timetracking";
        },
    },
    mounted() {
        const modal = this.$refs.timetracking_modal.$el;
        this.modal_simple_content = createModal(modal);
        this.modal_simple_content.addEventListener("tlp-modal-hidden", () => {
            this.setAddMode(false);
            this.$store.dispatch("reloadTimes");
        });
    },
    methods: {
        ...mapMutations(["setAddMode"]),
        show_modal() {
            this.$store.commit("setCurrentTimes", this.timeData);
            this.modal_simple_content.toggle();
        },
    },
};
</script>

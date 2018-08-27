/**
* Copyright (c) Enalean, 2018. All Rights Reserved.
*
* This file is a part of Tuleap.
*
* Tuleap is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* Tuleap is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
*/

(<template>
    <div class="tlp-modal-body timetracking-details-modal-content">
        <div class="tlp-pane-section timetracking-details-modal-artifact-title">
            <widget-link-to-artifact
                v-bind:artifact="current_artifact"
            />
        </div>
        <div class="timetracking-details-modal-artifact-details">
            <widget-modal-artifact-info/>
            <div class="timetracking-details-modal-artefact-link-top-bottom-spacer">
                <button class="tlp-button-primary"
                        v-on:click="setAddMode(!is_add_mode)">
                    <i class="fa fa-plus tlp-button-icon"></i>
                    {{ add_time }}
                </button>
            </div>
            <div v-if="rest_feedback.type"
                 v-bind:class="feedback_class">
                {{ rest_feedback.message }}
            </div>
            <widget-modal-table/>
        </div>
    </div>
</template>)
(<script>
import { gettext_provider } from "../../gettext-provider.js";
import { mapState, mapMutations, mapGetters } from "vuex";
import WidgetModalArtifactInfo from "./WidgetModalArtifactInfo.vue";
import WidgetModalTable from "./WidgetModalTable.vue";
import WidgetLinkToArtifact from "../WidgetLinkToArtifact.vue";
export default {
    name: "WidgetModalContent",
    components: { WidgetLinkToArtifact, WidgetModalTable, WidgetModalArtifactInfo },
    computed: {
        ...mapState(["is_add_mode", "rest_feedback", "current_times"]),
        ...mapGetters(["current_artifact"]),
        edit_time: () => gettext_provider.gettext("Detailed times"),
        add_time: () => gettext_provider.gettext("Add"),
        feedback_class() {
            return "tlp-alert-" + this.rest_feedback.type;
        },
        timetracking_url() {
            return this.current_artifact.html_url + "&view=timetracking";
        }
    },
    methods: {
        ...mapMutations(["setAddMode"])
    }
};
</script>)

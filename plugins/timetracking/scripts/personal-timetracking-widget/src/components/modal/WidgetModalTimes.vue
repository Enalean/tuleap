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
    <div class="tlp-modal" role="dialog">
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">
                <i class="fa fa-pencil tlp-modal-title-icon"></i>
                <translate>Detailed times</translate>
            </h1>
            <div
                class="tlp-modal-close"
                tabindex="0"
                role="button"
                data-dismiss="modal"
                aria-label="Close"
            >
                &times;
            </div>
        </div>
        <widget-modal-content v-if="current_artifact" data-test="modal-content" />
        <div class="tlp-modal-footer tlp-modal-footer-large">
            <button
                type="button"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                data-dismiss="modal"
            >
                <translate>Close</translate>
            </button>
        </div>
    </div>
</template>
<script>
import { mapGetters, mapActions } from "vuex";
import WidgetModalContent from "./WidgetModalContent.vue";
export default {
    name: "WidgetModalTimes",
    components: { WidgetModalContent },
    computed: {
        ...mapGetters(["current_artifact"]),
    },
    methods: {
        ...mapActions(["reloadTimes"]),
    },
};
</script>

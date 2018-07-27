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
    <div class="modal fade" id="move-artifact-modal" tabindex="-1" role="dialog"
         aria-labelledby="modal-move-artifact-choose-trackers" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="tuleap-modal-close close">×</i>
                    <h3 class="modal-title" id="modal-move-artifact-choose-trackers">
                        <i class="icon-share-alt"></i>
                        <translate>Move</translate>
                        <span v-bind:class="getArtifactBadgeClass">
                            {{ getArtifactBadgeTrackerName }} #{{ getArtifactBadgeArtifactId }}
                        <span> -</span></span>
                    </h3>
                </div>
                <div class="modal-body">
                    <div class="alert alert-primary" role="alert">
                        <translate>Under implementation, there is nothing here !</translate>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><translate>Close</translate></button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { getTrackerName, getTrackerColor, getArtifactId } from "../from-tracker-presenter.js";
export default {
    name: "MoveModal",
    computed: {
        getArtifactBadgeClass() {
            return getTrackerColor() + " xref-in-title";
        },
        getArtifactBadgeTrackerName() {
            return getTrackerName();
        },
        getArtifactBadgeArtifactId() {
            return getArtifactId();
        }
    }
};
</script>

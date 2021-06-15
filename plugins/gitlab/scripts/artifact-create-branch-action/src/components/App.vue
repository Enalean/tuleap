<!--
  - Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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
    <div
        class="modal fade hide"
        id="create-gitlab-branch-artifact-modal"
        role="dialog"
        aria-labelledby="modal-artifact-create-gitlab-branch-choose-integrations"
        aria-hidden="true"
    >
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <i class="tuleap-modal-close close" data-dismiss="modal">×</i>
                    <h3
                        class="modal-title"
                        id="modal-artifact-create-gitlab-branch-choose-integrations"
                    >
                        <i class="fas fa-code-branch" aria-hidden="true"></i>
                        <translate class="modal-move-artifact-icon-title">
                            Create a GitLab branch
                        </translate>
                    </h3>
                </div>
                <div class="modal-body artifact-create-gitlab-branch-modal-body">
                    <integrations-list />
                </div>
                <div class="modal-footer">
                    <button type="reset" class="btn btn-secondary" data-dismiss="modal">
                        <translate>Close</translate>
                    </button>
                    <button type="button" class="btn btn-primary" disabled>
                        <i class="fas fa-check"></i>
                        <translate>Confirm</translate>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import jquery from "jquery";
import IntegrationsList from "./IntegrationsList.vue";

@Component({
    components: {
        IntegrationsList,
    },
})
export default class App extends Vue {
    mounted(): void {
        const jquery_element = jquery(this.$el);
        jquery_element.on("hidden", () => {
            this.$root.$destroy();
            this.$root.$el.parentNode?.removeChild(this.$root.$el);
        });
        jquery_element.modal();
    }
}
</script>

<!--
  - Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
    <div>
        <div v-if="shouldDisplayErrorBanner" class="tlp-alert-danger" v-translate="{ error_message }">
            An error occurred: %{ error_message }
        </div>
        <div v-if="message === ''" class="project-admin-banner-message">
            <p v-translate>No banner has been defined</p>
        </div>
        <div v-else>
            <banner-presenter v-bind:message="message"/>
            <banner-deleter v-on:delete-banner="deleteBanner()"/>
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import BannerDeleter from "./BannerDeleter.vue";
import BannerPresenter from "./BannerPresenter.vue";
import { deleteBannerForProject } from "../api/rest-querier";

@Component({
    components: {
        BannerDeleter,
        BannerPresenter
    }
})
export default class App extends Vue {
    @Prop({ required: true, type: String })
    readonly message!: string;

    @Prop({ required: true, type: Number })
    readonly project_id!: number;

    error_message: string | null = null;

    public deleteBanner(): void {
        deleteBannerForProject(this.project_id)
            .then(() => {
                location.reload();
            })
            .catch(error => {
                this.error_message = error.message;
            });
    }

    get shouldDisplayErrorBanner(): boolean {
        return this.error_message !== null;
    }
}
</script>

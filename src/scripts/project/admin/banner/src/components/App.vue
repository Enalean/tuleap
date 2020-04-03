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
        <div
            v-if="shouldDisplayErrorBanner"
            class="tlp-alert-danger"
            v-translate="{ error_message }"
        >
            An error occurred: %{ error_message }
        </div>
        <div>
            <banner-presenter
                v-bind:message="message"
                v-bind:loading="banner_presenter_is_loading"
                v-on:delete-banner="deleteBanner"
                v-on:save-banner="saveBanner(...arguments)"
            />
        </div>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import BannerPresenter from "./BannerPresenter.vue";
import { deleteBannerForProject, saveBannerForProject } from "../api/rest-querier";
import { BannerState } from "../type";

@Component({
    components: {
        BannerPresenter,
    },
})
export default class App extends Vue {
    @Prop({ required: true, type: String })
    readonly message!: string;

    @Prop({ required: true, type: Number })
    readonly project_id!: number;

    error_message: string | null = null;
    banner_presenter_is_loading = false;

    public saveBanner(bannerState: BannerState): void {
        this.banner_presenter_is_loading = true;

        if (!bannerState.activated) {
            this.deleteBanner();
            return;
        }

        this.saveBannerMessage(bannerState.message);
    }

    private saveBannerMessage(message: string): void {
        saveBannerForProject(this.project_id, message)
            .then(() => {
                location.reload();
            })
            .catch((error) => {
                this.error_message = error.message;
                this.banner_presenter_is_loading = false;
            });
    }

    private deleteBanner(): void {
        deleteBannerForProject(this.project_id)
            .then(() => {
                location.reload();
            })
            .catch((error) => {
                this.error_message = error.message;
                this.banner_presenter_is_loading = false;
            });
    }

    get shouldDisplayErrorBanner(): boolean {
        return this.error_message !== null;
    }
}
</script>

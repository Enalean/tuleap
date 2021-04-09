<!--
  - Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
    <div class="empty-page taskboard-error">
        <div class="empty-page-illustration">
            <global-app-error-illustration />
        </div>

        <div class="empty-page-text-with-small-text">
            <translate>Oops, there's an issue</translate>
            <div class="empty-page-small-text" v-translate>
                It seems an action you tried to perform can't be done
            </div>
            <template v-if="has_more_details">
                <div class="taskboard-error-link">
                    <a
                        v-if="!is_more_shown"
                        v-on:click="is_more_shown = true"
                        data-test="show-details"
                        v-translate
                    >
                        Show error details
                    </a>
                </div>
                <pre v-if="is_more_shown" class="taskboard-error-details" data-test="details">{{
                    global_error_message
                }}</pre>
            </template>
        </div>

        <button type="button" class="tlp-button-primary tlp-button-large" v-on:click="reloadPage">
            <i class="fas fa-sync tlp-button-icon" aria-hidden="true"></i>
            <translate>Reload the page</translate>
        </button>
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component } from "vue-property-decorator";
import { namespace } from "vuex-class";

const error = namespace("error");
@Component({
    components: {
        "global-app-error-illustration": (): Promise<Record<string, unknown>> =>
            import(
                /* webpackChunkName: "taskboard-global-app-error-illustration" */ "./GlobalAppErrorIllustration.vue"
            ),
    },
})
export default class GlobalAppError extends Vue {
    @error.State
    readonly global_error_message!: string;

    is_more_shown = false;

    reloadPage(): void {
        window.location.reload();
    }

    get has_more_details(): boolean {
        return this.global_error_message.length > 0;
    }
}
</script>

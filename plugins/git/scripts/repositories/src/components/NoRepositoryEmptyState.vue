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
    <div class="empty-page" v-if="show_empty_state">
        <div class="empty-page-text">
            <svg
                class="empty-page-icon git-repository-list-empty-image"
                xmlns="http://www.w3.org/2000/svg"
                width="70"
                height="80"
                viewBox="0 0 70 80"
            >
                <g fill="none" fill-rule="evenodd">
                    <rect
                        width="64.72"
                        height="74.72"
                        x="2.64"
                        y="2.64"
                        stroke-width="5.28"
                        rx="3.96"
                    />
                    <rect
                        class="git-repository-list-empty-rect"
                        width="7.292"
                        height="7.273"
                        x="10.208"
                        y="11.636"
                    />
                    <rect
                        class="git-repository-list-empty-rect"
                        width="7.292"
                        height="7.273"
                        x="10.208"
                        y="27.636"
                    />
                    <rect
                        class="git-repository-list-empty-rect"
                        width="7.292"
                        height="7.273"
                        x="10.208"
                        y="43.636"
                    />
                    <rect
                        class="git-repository-list-empty-rect"
                        width="7.292"
                        height="7.273"
                        x="10.208"
                        y="59.636"
                    />
                    <g stroke-width="3.024" transform="translate(26 15)">
                        <path
                            stroke-linecap="square"
                            d="M6.28571429 11.2060041L6.28571429 39.1304348M26.2857143 19.5652174C25.8215873 24.5967868 22.3930159 27.8576564 16 29.3478261 9.93503221 31.2749039 6.88741316 34.5357734 6.85714286 39.1304348"
                        />
                        <ellipse cx="6.286" cy="5.978" rx="4.774" ry="4.466" />
                        <ellipse cx="25.714" cy="14.674" rx="4.774" ry="4.466" />
                        <ellipse cx="6.286" cy="44.022" rx="4.774" ry="4.466" />
                    </g>
                </g>
            </svg>
            <p class="empty-page-text" v-translate>There are no repositories in this project</p>
            <button
                type="button"
                class="tlp-button-primary tlp-button-large"
                v-if="is_admin"
                v-on:click="showAddRepositoryModal()"
                data-test="empty_state_create_repository"
            >
                <i class="fa fa-plus tlp-button-icon"></i>
                <translate>Add project repository</translate>
            </button>
        </div>
    </div>
</template>
<script>
import { mapGetters, mapActions } from "vuex";
import { getUserIsAdmin } from "../repository-list-presenter.js";

export default {
    name: "NoRepositoryEmptyState",
    computed: {
        is_admin() {
            return getUserIsAdmin();
        },
        show_empty_state() {
            return this.isCurrentRepositoryListEmpty && this.isInitialLoadingDoneWithoutError;
        },
        ...mapGetters(["isCurrentRepositoryListEmpty", "isInitialLoadingDoneWithoutError"]),
    },
    methods: {
        ...mapActions(["showAddRepositoryModal"]),
    },
};
</script>

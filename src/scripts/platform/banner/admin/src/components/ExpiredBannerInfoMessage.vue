<!--
  - Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
  -
  -  This file is a part of Tuleap.
  -
  -  Tuleap is free software; you can redistribute it and/or modify
  -  it under the terms of the GNU General Public License as published by
  -  the Free Software Foundation; either version 2 of the License, or
  -  (at your option) any later version.
  -
  -  Tuleap is distributed in the hope that it will be useful,
  -  but WITHOUT ANY WARRANTY; without even the implied warranty of
  -  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  -  GNU General Public License for more details.
  -
  -  You should have received a copy of the GNU General Public License
  -  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
  -->

<template>
    <div
        v-if="is_expired"
        class="tlp-alert-info"
        v-translate="{ expiration_date: localized_expiration_date }"
    >
        This banner is expired since %{ expiration_date } and, as such, not displayed on the
        platform
    </div>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";

@Component
export default class ExpiredBannerInfoMessage extends Vue {
    @Prop({ required: true, type: String })
    readonly expiration_date!: string;
    @Prop({ required: true, type: String })
    readonly message!: string;

    get is_expired(): boolean {
        if (this.message === "" || this.expiration_date === "") {
            return false;
        }

        return new Date() >= new Date(this.expiration_date);
    }

    get localized_expiration_date(): string {
        const locale = Vue.config.language ?? "en_US";
        return new Date(this.expiration_date).toLocaleString(locale.replace("_", "-"));
    }
}
</script>

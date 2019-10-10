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
    <p class="tlp-text-warning taskboard-no-mapping">
        <i class="fa fa-warning"></i>
        <span v-dompurify-html="message"></span>
        <translate>Please edit the card to change the status, or add children if possible.</translate>
    </p>
</template>

<script lang="ts">
import Vue from "vue";
import { Component, Prop } from "vue-property-decorator";
import { sprintf } from "sprintf-js";
import { Card } from "../../../../../type";

@Component
export default class NoMappingMessage extends Vue {
    @Prop({ required: true })
    readonly card!: Card;

    get message(): string {
        if (!this.card.mapped_list_value) {
            return this.$gettext("This card does not have any status.");
        }

        return sprintf(
            this.$gettext(
                "This card has status <strong>%s</strong> that does not map to current taskboard columns."
            ),
            this.card.mapped_list_value.label
        );
    }
}
</script>

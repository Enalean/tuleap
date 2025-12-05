<!--
  - Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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
        id="review-modal"
        role="dialog"
        aria-labelledby="review-modal-label"
        class="tlp-modal"
        ref="modal_div"
    >
        <div class="tlp-modal-header">
            <h1 class="tlp-modal-title">{{ $gettext("Review") }}</h1>
            <button
                class="tlp-modal-close"
                type="button"
                data-dismiss="modal"
                v-bind:aria-label="$gettext('Close')"
            >
                <i class="fa-solid fa-xmark tlp-modal-close-icon" aria-hidden="true"></i>
            </button>
        </div>
        <div class="tlp-modal-feedback">
            <div
                v-if="error_message !== ''"
                class="tlp-alert-danger"
                data-test="review-modal-error"
            >
                <p class="tlp-alert-title">{{ $gettext("Error while saving your review") }}</p>
                {{ error_message }}
            </div>
        </div>
        <div class="tlp-modal-body tlp-modal-body-with-sections">
            <div class="tlp-modal-body-section">
                <h2 class="tlp-modal-subtitle">{{ $gettext("Document under review") }}</h2>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Document name") }}</label>
                    <p>{{ item.title }}</p>
                </div>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Document version") }}</label>
                    <router-link
                        v-if="table.version_id !== null"
                        v-bind:to="{
                            name: 'item_version',
                            params: {
                                folder_id: item.parent_id,
                                item_id: item.id,
                                version_id: table.version_id,
                            },
                        }"
                    >
                        {{ table.version_label }}
                    </router-link>
                    <template v-else>
                        <p>{{ table.version_label }}</p>
                    </template>
                </div>
            </div>
            <div class="tlp-modal-body-section">
                <h2 class="tlp-modal-subtitle">{{ $gettext("Approval cycle details") }}</h2>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Approval requester") }}</label>
                    <user-badge v-bind:user="table.table_owner" />
                </div>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Notification type") }}</label>
                    <p>{{ table.notification_type }}</p>
                </div>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Approval cycle start date") }}</label>
                    <document-relative-date
                        v-bind:date="table.approval_request_date"
                        relative_placement="right"
                    />
                </div>
                <div class="tlp-property">
                    <label class="tlp-label">{{ $gettext("Requester comment") }}</label>
                    <p v-if="table.description !== ''">
                        {{ table.description }}
                    </p>
                    <p v-else class="tlp-property-empty">{{ $gettext("No comment") }}</p>
                </div>
            </div>
        </div>
        <div class="tlp-modal-body modal-form-section">
            <div class="tlp-form-element">
                <label class="tlp-label" for="review">
                    {{ $gettext("Review:") }}
                    <i
                        class="fa-solid fa-circle-question"
                        ref="popover_trigger"
                        data-placement="right"
                        data-trigger="hover"
                    ></i>
                </label>
                <select
                    id="review"
                    name="review"
                    class="tlp-select tlp-select-adjusted"
                    v-model="review_value"
                    data-test="review-select-state"
                >
                    <option value="not_yet">{{ $gettext("Not yet") }}</option>
                    <option value="approved">{{ $gettext("Approved") }}</option>
                    <option value="rejected">{{ $gettext("Rejected") }}</option>
                    <option value="comment_only">{{ $gettext("Comment only") }}</option>
                    <option value="will_not_review">{{ $gettext("Will not review") }}</option>
                </select>
                <approval-table-review-popover
                    v-if="popover_trigger"
                    v-bind:trigger="popover_trigger"
                />
            </div>
            <div class="tlp-property" v-if="reviewer.review_date">
                <label class="tlp-label">{{ $gettext("Review date") }}</label>
                <p>{{ formatter.format(reviewer.review_date) }}</p>
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label" for="comment">{{ $gettext("Add a comment") }}</label>
                <textarea
                    id="comment"
                    name="comment"
                    class="tlp-textarea comment-input"
                    v-model="comment_value"
                    data-test="review-comment"
                ></textarea>
            </div>
            <div class="tlp-form-element">
                <label class="tlp-label tlp-checkbox">
                    <input type="checkbox" name="notification" v-model="notification_value" />
                    {{ $gettext("Send me an email whenever this item is updated.") }}
                </label>
            </div>
        </div>
        <div class="tlp-modal-footer">
            <button
                type="button"
                data-dismiss="modal"
                class="tlp-button-primary tlp-button-outline tlp-modal-action"
                v-bind:disabled="is_reviewing"
            >
                {{ $gettext("Cancel") }}
            </button>
            <button
                type="button"
                class="tlp-button-primary tlp-modal-action"
                v-on:click="onReview"
                data-test="send-review-button"
                v-bind:disabled="is_reviewing"
            >
                <i
                    v-if="is_reviewing"
                    class="tlp-button-icon fa-solid fa-spin fa-circle-notch"
                    aria-hidden="true"
                ></i>
                {{ $gettext("Send my review") }}
            </button>
        </div>
    </div>
</template>

<script setup lang="ts">
import type { ApprovalTable, ApprovalTableReviewer, Item } from "../../../type";
import { onMounted, onUnmounted, ref } from "vue";
import type { Modal } from "@tuleap/tlp-modal";
import { createModal } from "@tuleap/tlp-modal";
import { putReview } from "../../../api/approval-table-rest-querier";
import UserBadge from "../../User/UserBadge.vue";
import DocumentRelativeDate from "../../Date/DocumentRelativeDate.vue";
import { USER_LOCALE, USER_TIMEZONE } from "../../../configuration-keys";
import { strictInject } from "@tuleap/vue-strict-inject";
import { IntlFormatter } from "@tuleap/date-helper";
import ApprovalTableReviewPopover from "./ApprovalTableReviewPopover.vue";

const props = defineProps<{
    item: Item;
    trigger: HTMLButtonElement;
    reviewer: ApprovalTableReviewer;
    table: ApprovalTable;
}>();

const emit = defineEmits<{ (e: "refresh-data"): void }>();

const user_locale = strictInject(USER_LOCALE);
const user_timezone = strictInject(USER_TIMEZONE);
const formatter = IntlFormatter(user_locale, user_timezone, "short-month");

const modal_div = ref<HTMLDivElement>();
const is_reviewing = ref<boolean>(false);
const error_message = ref<string>("");
const modal = ref<Modal | null>(null);
const review_value = ref<string>(props.reviewer.state);
const comment_value = ref<string>(props.reviewer.comment);
const notification_value = ref<boolean>(props.reviewer.notification);
const popover_trigger = ref<HTMLElement>();

onMounted(() => {
    if (modal_div.value === undefined || popover_trigger.value === undefined) {
        throw Error("Failed to create review modal");
    }

    modal.value = createModal(modal_div.value, {
        destroy_on_hide: false,
        keyboard: true,
        dismiss_on_backdrop_click: true,
    });
    props.trigger.addEventListener("click", () => modal.value?.show());
});

onUnmounted(() => {
    modal.value?.destroy();
});

function onReview(): void {
    is_reviewing.value = true;
    putReview(
        props.item.id,
        review_value.value,
        comment_value.value,
        notification_value.value,
    ).match(
        () => {
            emit("refresh-data");
            is_reviewing.value = false;
            modal.value?.hide();
        },
        (fault) => {
            is_reviewing.value = false;
            error_message.value = fault.toString();
        },
    );
}
</script>

<style scoped lang="scss">
.modal-form-section {
    border-top: 1px solid var(--tlp-neutral-light-color);
}

.comment-input {
    resize: vertical;
}
</style>

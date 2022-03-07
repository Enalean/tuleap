<template>
    <form
        class="tlp-modal"
        role="dialog"
        v-bind:aria-labelled-by="aria_labelled_by"
        v-on:submit.prevent="createNewFile"
    >
        <modal-header
            v-bind:modal-title="$gettext('Create a new file')"
            v-bind:aria-labelled-by="aria_labelled_by"
            v-bind:icon-header-class="'fa-plus'"
        />
        <modal-feedback />
        <div class="tlp-modal-body">
            <document-global-property-for-create
                v-bind:currently-updated-item="item"
                v-bind:parent="parent"
            />
        </div>
        <modal-footer
            v-bind:is-loading="is_loading"
            v-bind:submit-button-label="$gettext('Create document')"
            v-bind:aria-labelled-by="aria_labelled_by"
        />
    </form>
</template>

<script lang="ts">
import type { Modal } from "tlp";
import { createModal } from "tlp";
import DocumentGlobalPropertyForCreate from "../NewDocument/PropertiesForCreate/DocumentGlobalPropertyForCreate.vue";
import Component from "vue-class-component";
import Vue from "vue";
import { Prop } from "vue-property-decorator";
import type { DefaultFileItem, Folder } from "../../../../type";
import { TYPE_FILE } from "../../../../constants";
import { namespace, State } from "vuex-class";
import ModalHeader from "../../ModalCommon/ModalHeader.vue";
import ModalFeedback from "../../ModalCommon/ModalFeedback.vue";
import ModalFooter from "../../ModalCommon/ModalFooter.vue";

const error = namespace("error");

@Component({
    components: {
        ModalFooter,
        ModalFeedback,
        ModalHeader,
        DocumentGlobalPropertyForCreate,
    },
})
export default class FileCreationModal extends Vue {
    @Prop({ required: true })
    readonly parent!: Folder;

    @Prop({ required: true })
    readonly droppedFile!: File;

    @State
    readonly current_folder!: Folder;

    @error.State
    readonly has_modal_error!: boolean;

    private modal: Modal | null = null;
    private is_loading = false;
    private item = this.getDefaultItem();

    readonly aria_labelled_by = "document-file-creation-modal";

    getDefaultItem(): DefaultFileItem {
        return {
            title: "",
            description: "",
            type: TYPE_FILE,
            file_properties: {
                file: {},
            },
        };
    }

    mounted(): void {
        this.modal = createModal(this.$el, { destroy_on_hide: true });
        this.modal.addEventListener("tlp-modal-hidden", this.close);
        this.modal.show();
    }

    close(): void {
        if (this.modal !== null) {
            this.modal.removeBackdrop();
            this.$emit("close-file-creation-modal");
            this.item = this.getDefaultItem();
        }
    }

    async createNewFile(): Promise<void> {
        this.is_loading = true;
        this.item.file_properties.file = this.droppedFile;
        this.$store.commit("error/resetModalError");
        await this.$store.dispatch("createNewItem", [this.item, this.parent, this.current_folder]);
        this.is_loading = false;

        if (!this.has_modal_error) {
            this.item = this.getDefaultItem();
            this.close();
        }
    }
}
</script>

import { extend } from "lodash-es";

export default ArtifactLinksGraphCtrl;

ArtifactLinksGraphCtrl.$inject = [
    "modal_instance",
    "ArtifactLinksGraphModalLoading",
    "modal_model",
];

function ArtifactLinksGraphCtrl(modal_instance, ArtifactLinksGraphModalLoading, modal_model) {
    var self = this;

    extend(self, {
        graph: modal_model.graph,
        errors: modal_model.errors,
        title: modal_model.title,
    });

    ArtifactLinksGraphModalLoading.loading.is_loading = false;
}

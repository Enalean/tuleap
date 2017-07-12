import _ from 'lodash';

export default ArtifactLinksGraphCtrl;

ArtifactLinksGraphCtrl.$inject = ['$modalInstance', 'ArtifactLinksGraphModalLoading', 'modal_model'];

function ArtifactLinksGraphCtrl($modalInstance, ArtifactLinksGraphModalLoading, modal_model) {
    var self = this;

    _.extend(self, {
        graph : modal_model.graph,
        errors: modal_model.errors,
        cancel: $modalInstance.dismiss,
        title:  modal_model.title
    });

    $modalInstance.opened.then(function() {
        ArtifactLinksGraphModalLoading.loading.is_loading = false;
    });
}

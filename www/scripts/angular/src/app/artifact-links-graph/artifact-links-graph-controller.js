angular
    .module('tuleap.artifact-links-graph')
    .controller('ArtifactLinksGraphCtrl', ArtifactLinksGraphCtrl);

ArtifactLinksGraphCtrl.$inject = ['$modalInstance', 'ArtifactLinksGraphModalLoading', 'modal_model', 'title'];

function ArtifactLinksGraphCtrl($modalInstance, ArtifactLinksGraphModalLoading, modal_model, title) {
    var self = this;

    _.extend(self, {
        graph : modal_model.graph,
        errors: modal_model.errors,
        cancel: $modalInstance.dismiss,
        title:  title
    });

    $modalInstance.opened.then(function() {
        ArtifactLinksGraphModalLoading.loading.is_loading = false;
    });
}
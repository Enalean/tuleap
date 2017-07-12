import _ from 'lodash';

export default ExecutionPresencesCtrl;

ExecutionPresencesCtrl.$inject = [
    '$scope',
    '$modalInstance',
    'modal_model'
];

function ExecutionPresencesCtrl(
    $scope,
    $modalInstance,
    modal_model
) {
    var self = this;

    _.extend(self, {
        presences: modal_model.presences,
        cancel   : $modalInstance.dismiss,
        title    : modal_model.title
    });

    $scope.getWidth = function(presence) {
        var max_presence_score = _.max(self.presences, function(presence) {
            return presence.score;
        });
        var current_scale_max    = max_presence_score.score;
        var percentage_scale_max = 85;
        var percentage_scale_min = 30;

        return ((percentage_scale_max - percentage_scale_min) * presence.score / current_scale_max) + percentage_scale_min;
    };
}


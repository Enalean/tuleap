import _ from 'lodash';

export default ExecutionPresencesCtrl;

ExecutionPresencesCtrl.$inject = [
    'modal_model'
];

function ExecutionPresencesCtrl(
    modal_model
) {

    modal_model.presences.forEach(function(presence) {
        presence.score = presence.score || 0;
        presence.scoreView = Math.max(presence.score, 0);
    });
    var ranking = _.sortBy(modal_model.presences, 'score').reverse();

    _.extend(this, {
        title      : modal_model.title,
        topRanking : _.take(ranking, 3),
        restRanking: _.drop(ranking, 3)
    });
}

var model_module = angular
    .module('tuleap-artifact-modal-model');

var TuleapArtifactModalAwkwardCreationFields = [
    'aid',
    'atid',
    'lud',
    'burndown',
    'priority',
    'subby',
    'luby',
    'subon',
    'cross'
];

model_module.constant('TuleapArtifactModalAwkwardCreationFields', TuleapArtifactModalAwkwardCreationFields);

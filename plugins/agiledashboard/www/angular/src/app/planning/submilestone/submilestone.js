var submilestone = angular.module('tuleap.planning.submilestone', ['ngResource']);

submilestone.service('SubmilestoneService', ['$resource', submilestoneService]);

submilestone.controller('SubmilestoneCtrl', ['$scope', 'SubmilestoneService', 'Artifact', submilestoneController]);

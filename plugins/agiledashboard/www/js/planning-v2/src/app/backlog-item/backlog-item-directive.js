import './backlog-item.tpl.html';
import BacklogItemController from './backlog-item-controller.js';

export default function BacklogItem() {
    return {
        restrict        : 'E',
        scope           : false,
        replace         : false,
        controller      : BacklogItemController,
        controllerAs    :'backlogItemController',
        bindToController: true,
        templateUrl     : 'backlog-item.tpl.html',
    };
}

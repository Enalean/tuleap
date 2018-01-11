import './link-field.tpl.html';
import LinkFieldController from './link-field-controller.js';

export default function linkFieldDirective() {
    return {
        restrict: 'EA',
        replace : false,
        scope   : {
            field          : '=tuleapArtifactModalLinkField',
            isDisabled     : '&isDisabled',
            value_model    : '=valueModel',
            tracker        : '=tracker',
            linked_artifact: '=linkedArtifact'
        },
        controller      : LinkFieldController,
        controllerAs    : 'link_field',
        bindToController: true,
        templateUrl     : 'link-field.tpl.html'
    };
}

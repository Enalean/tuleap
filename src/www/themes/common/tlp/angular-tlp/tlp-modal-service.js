import { modal } from 'tlp';
import {
    forEach,
    isObject,
    isString,
    isUndefined
} from 'angular';

export default TlpModalService;

TlpModalService.$inject = [
    '$compile',
    '$controller',
    '$document',
    '$q',
    '$rootScope',
    '$templateCache'
];

function TlpModalService(
    $compile,
    $controller,
    $document,
    $q,
    $rootScope,
    $templateCache
) {
    var self = this;

    self.open = open;

    var usage = "Options must be defined. Options must follow this format: \n"
        + "{\n"
        + "    templateUrl: (string) relative URL to the template of the modal,\n"
        + "    controller: (function) Controller function for the modal. modal_instance will be injected in it \n"
        + "                 and will have a tlp_modal property that holds the TLP modal object.\n"
        + "                 Warning: modal_instance will NOT have a tlp_modal property at controller init. It is added just after.\n"
        + "                 You should use setTimeout() if you need it at init\n"
        + "    controllerAs: (string) The scope property name that will hold the controller instance,\n"
        + "    tlpModalOptions: (optional)(object) Options object to be passed down to TLP modal(),\n"
        + "    resolve: (optional)(object) Each key of this object is a function that will be injected in the controller. \n"
        + "             Each of these function can return a promise that will be resolved before opening the modal.\n"
        + "}\n";

    function isTemplateUrlOptionValid(options) {
        return ! isUndefined(options.templateUrl) && isString(options.templateUrl);
    }

    function isControllerOptionValid(options) {
        return ! isUndefined(options.controller);
    }

    function isControllerAsOptionValid(options) {
        return ! isUndefined(options.controllerAs) && isString(options.controllerAs);
    }

    function isTlpModalOptionValid(options) {
        return (isUndefined(options.tlpModalOptions) || isObject(options.tlpModalOptions));
    }

    function isResolveOptionValid(options) {
        return (isUndefined(options.resolve) || isObject(options.resolve));
    }

    function open(options) {
        if (isUndefined(options)
            || ! isTemplateUrlOptionValid(options)
            || ! isControllerOptionValid(options)
            || ! isControllerAsOptionValid(options)
            || ! isTlpModalOptionValid(options)
            || ! isResolveOptionValid(options)
        ) {
            throw new Error(usage);
        }

        var template_promise  = getTemplatePromise(options.templateUrl);
        var resolved_promises = getResolvePromises(options.resolve);
        resolved_promises.unshift(template_promise);

        var promise = $q.all(resolved_promises).then(function(template_and_resolved_dependencies) {
            var template = template_and_resolved_dependencies.shift();
            var resolved_dependencies = template_and_resolved_dependencies;
            return createAndOpenModal(options, template, resolved_dependencies);
        });

        return promise;
    }

    function createAndOpenModal(options, template, resolved_dependencies) {
        var tlp_modal_options = options.tlpModalOptions || {};

        var scope            = $rootScope.$new();
        var body             = $document.find('body').eq(0);
        var modal_instance = {};

        instanciateController(
            scope,
            modal_instance,
            options,
            resolved_dependencies
        );

        var compiled_modal    = $compile(template)(scope);
        var dom_modal_element = compiled_modal[0];
        body.append(dom_modal_element);
        modal_instance.tlp_modal = modal(dom_modal_element, tlp_modal_options);

        modal_instance.tlp_modal.show();

        modal_instance.tlp_modal.addEventListener('tlp-modal-hidden', function() {
            dom_modal_element.remove();
        });

        return modal_instance.tlp_modal;
    }

    function instanciateController(
        scope,
        modal_instance,
        options,
        resolved_dependencies
    ) {
        var controller    = options.controller;
        var controller_as = options.controllerAs;

        var controller_injected_variables = {
            $scope        : scope,
            modal_instance: modal_instance
        };

        var i = 0;
        forEach(options.resolve, function(value, key) {
            controller_injected_variables[key] = resolved_dependencies[i];
            i++;
        });

        var instanciated_controller = $controller(controller, controller_injected_variables);
        scope[controller_as] = instanciated_controller;
    }

    function getTemplatePromise(template_url) {
        var template = $templateCache.get(template_url);

        if (isUndefined(template)) {
            throw new Error('templateUrl was not stored in templateCache. Did you import it ?');
        }

        return $q.when(template);
    }

    function getResolvePromises(resolve_options) {
        if (isUndefined(resolve_options)) {
            return [$q.when()];
        }

        var promises = [];
        forEach(resolve_options, function(value) {
            promises.push($q.when(value));
        });

        return promises;
    }
}

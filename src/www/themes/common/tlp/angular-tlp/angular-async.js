import angular from "angular";

export default angular.module("angularAsync", []).config(config).run(run).name;

export let template,
    service,
    factory,
    provider,
    constant,
    value,
    filter,
    directive,
    component,
    controller;

config.$inject = ["$filterProvider", "$controllerProvider", "$compileProvider", "$provide"];

// Crazy trick to register angular injectables after bootstrap
// see https://github.com/mikeromano38/angular-async
function config($filterProvider, $controllerProvider, $compileProvider, $provide) {
    service = $provide.service;
    factory = $provide.factory;
    provider = $provide.provider;
    constant = $provide.constant;
    value = $provide.value;

    filter = $filterProvider.register;
    directive = $compileProvider.directive;
    component = $compileProvider.component;
    controller = $controllerProvider.register;
}

run.$inject = ["$templateCache"];

function run($templateCache) {
    template = $templateCache.put;
}

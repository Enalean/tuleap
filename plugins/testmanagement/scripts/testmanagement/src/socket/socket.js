import angular from "angular";
import angular_locker from "angular-locker";

import "angular-socket-io";
import jwt_module from "../jwt/jwt.js";
import shared_properties_module from "../shared-properties/shared-properties.js";
import execution_collection_module from "../execution-collection/execution-collection.js";

import SocketConfig from "./socket-config.js";
import SocketService from "./socket-service.js";
import SocketFactory from "./socket-factory.js";
import SocketDisconnectDirective from "./socket-disconnect-directive.js";

export default angular
    .module("socket", [
        angular_locker,
        "btford.socket-io",
        execution_collection_module,
        jwt_module,
        shared_properties_module,
    ])
    .config(SocketConfig)
    .service("SocketService", SocketService)
    .service("SocketFactory", SocketFactory)
    .directive("socketDisconnect", SocketDisconnectDirective).name;

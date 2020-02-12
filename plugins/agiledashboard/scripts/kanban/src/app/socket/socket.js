import angular from "angular";
import "angular-socket-io"; // provides btford.socket-io
import "angular-locker";
import jwt from "../jwt/jwt.js";
import "angular-gettext";

import SocketConfig from "./socket-config.js";
import SocketFactory from "./socket-factory.js";
import SocketService from "./socket-service.js";

angular
    .module("socket", ["btford.socket-io", "angular-locker", jwt, "gettext"])
    .config(SocketConfig)
    .service("SocketFactory", SocketFactory)
    .service("SocketService", SocketService);

export default "socket";

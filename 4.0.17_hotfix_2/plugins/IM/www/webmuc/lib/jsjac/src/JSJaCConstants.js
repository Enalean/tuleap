var NS_DISCO_ITEMS =  "http://jabber.org/protocol/disco#items";
var NS_DISCO_INFO =   "http://jabber.org/protocol/disco#info";
var NS_VCARD =        "vcard-temp";
var NS_AUTH =         "jabber:iq:auth";
var NS_AUTH_ERROR =   "jabber:iq:auth:error";
var NS_REGISTER =     "jabber:iq:register";
var NS_SEARCH =       "jabber:iq:search";
var NS_ROSTER =       "jabber:iq:roster";
var NS_PRIVACY =      "jabber:iq:privacy";
var NS_PRIVATE =      "jabber:iq:private";
var NS_VERSION =      "jabber:iq:version";
var NS_TIME =         "jabber:iq:time";
var NS_LAST =         "jabber:iq:last";
var NS_XDATA =        "jabber:x:data";
var NS_IQDATA =       "jabber:iq:data";
var NS_DELAY =        "jabber:x:delay";
var NS_EXPIRE =       "jabber:x:expire";
var NS_EVENT =        "jabber:x:event";
var NS_XCONFERENCE =  "jabber:x:conference";
var NS_STATS =        "http://jabber.org/protocol/stats";
var NS_MUC =          "http://jabber.org/protocol/muc";
var NS_MUC_USER =     "http://jabber.org/protocol/muc#user";
var NS_MUC_ADMIN =    "http://jabber.org/protocol/muc#admin";
var NS_MUC_OWNER =    "http://jabber.org/protocol/muc#owner";
var NS_PUBSUB =       "http://jabber.org/protocol/pubsub";
var NS_PUBSUB_EVENT = "http://jabber.org/protocol/pubsub#event";
var NS_PUBSUB_OWNER = "http://jabber.org/protocol/pubsub#owner";
var NS_PUBSUB_NMI =   "http://jabber.org/protocol/pubsub#node-meta-info";
var NS_COMMANDS =     "http://jabber.org/protocol/commands";
var NS_STREAM =       "http://etherx.jabber.org/streams";

var NS_STANZAS =      "urn:ietf:params:xml:ns:xmpp-stanzas";
var NS_STREAMS =      "urn:ietf:params:xml:ns:xmpp-streams";

var NS_TLS =          "urn:ietf:params:xml:ns:xmpp-tls";
var NS_SASL =         "urn:ietf:params:xml:ns:xmpp-sasl";
var NS_SESSION =      "urn:ietf:params:xml:ns:xmpp-session";
var NS_BIND =         "urn:ietf:params:xml:ns:xmpp-bind";

var NS_FEATURE_IQAUTH = "http://jabber.org/features/iq-auth";
var NS_FEATURE_IQREGISTER = "http://jabber.org/features/iq-register";
var NS_FEATURE_COMPRESS = "http://jabber.org/features/compress";

var NS_COMPRESS =     "http://jabber.org/protocol/compress";

function STANZA_ERROR(code, type, cond) {
  if (window == this)
    return new STANZA_ERROR(code, type, cond);

  this.code = code;
  this.type = type;
  this.cond = cond;
}

var ERR_BAD_REQUEST =
        STANZA_ERROR("400", "modify", "bad-request");
var ERR_CONFLICT =
        STANZA_ERROR("409", "cancel", "conflict");
var ERR_FEATURE_NOT_IMPLEMENTED =
        STANZA_ERROR("501", "cancel", "feature-not-implemented");
var ERR_FORBIDDEN =
        STANZA_ERROR("403", "auth",   "forbidden");
var ERR_GONE =
        STANZA_ERROR("302", "modify", "gone");
var ERR_INTERNAL_SERVER_ERROR =
        STANZA_ERROR("500", "wait",   "internal-server-error");
var ERR_ITEM_NOT_FOUND =
        STANZA_ERROR("404", "cancel", "item-not-found");
var ERR_JID_MALFORMED =
        STANZA_ERROR("400", "modify", "jid-malformed");
var ERR_NOT_ACCEPTABLE =
        STANZA_ERROR("406", "modify", "not-acceptable");
var ERR_NOT_ALLOWED =
        STANZA_ERROR("405", "cancel", "not-allowed");
var ERR_NOT_AUTHORIZED =
        STANZA_ERROR("401", "auth",   "not-authorized");
var ERR_PAYMENT_REQUIRED =
        STANZA_ERROR("402", "auth",   "payment-required");
var ERR_RECIPIENT_UNAVAILABLE =
        STANZA_ERROR("404", "wait",   "recipient-unavailable");
var ERR_REDIRECT =
        STANZA_ERROR("302", "modify", "redirect");
var ERR_REGISTRATION_REQUIRED =
        STANZA_ERROR("407", "auth",   "registration-required");
var ERR_REMOTE_SERVER_NOT_FOUND =
        STANZA_ERROR("404", "cancel", "remote-server-not-found");
var ERR_REMOTE_SERVER_TIMEOUT =
        STANZA_ERROR("504", "wait",   "remote-server-timeout");
var ERR_RESOURCE_CONSTRAINT =
        STANZA_ERROR("500", "wait",   "resource-constraint");
var ERR_SERVICE_UNAVAILABLE =
        STANZA_ERROR("503", "cancel", "service-unavailable");
var ERR_SUBSCRIPTION_REQUIRED =
        STANZA_ERROR("407", "auth",   "subscription-required");
var ERR_UNEXPECTED_REQUEST =
        STANZA_ERROR("400", "wait",   "unexpected-request");


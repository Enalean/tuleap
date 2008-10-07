/**
 * @fileoverview This file contains all things that make life easier when
 * dealing with JIDs
 * @author Stefan Strigler
 * @version $Revision: 437 $
 */

/**
 * list of forbidden chars for nodenames
 * @private
 */
var JSJACJID_FORBIDDEN = ['"',' ','&','\'','/',':','<','>','@'];

/**
 * Creates a new JSJaCJID object
 * @class JSJaCJID models xmpp jid objects
 * @constructor
 * @param {Object} jid jid may be either of type String or a JID represented
 * by JSON with fields 'node', 'domain' and 'resource'
 * @throws JSJaCJIDInvalidException Thrown if jid is not valid
 * @return a new JSJaCJID object
 */
function JSJaCJID(jid) {
  /**
   *@private
   */
  this._node = '';
  /**
   *@private
   */
  this._domain = '';
  /**
   *@private
   */
  this._resource = '';

  if (typeof(jid) == 'string') {
    if (jid.indexOf('@') != -1) {
        this.setNode(jid.substring(0,jid.indexOf('@')));
        jid = jid.substring(jid.indexOf('@')+1);
    }
    if (jid.indexOf('/') != -1) {
      this.setResource(jid.substring(jid.indexOf('/')+1));
      jid = jid.substring(0,jid.indexOf('/'));
    }
    this.setDomain(jid);
  } else {
    this.setNode(jid.node);
    this.setDomain(jid.domain);
    this.setResource(jid.resource);
  }
}


/**
 * Gets the node part of the jid
 * @return A string representing the node name
 * @type String
 */
JSJaCJID.prototype.getNode = function() { return this._node; };

/**
 * Gets the domain part of the jid
 * @return A string representing the domain name
 * @type String
 */
JSJaCJID.prototype.getDomain = function() { return this._domain; };

/**
 * Gets the resource part of the jid
 * @return A string representing the resource
 * @type String
 */
JSJaCJID.prototype.getResource = function() { return this._resource; };


/**
 * Sets the node part of the jid
 * @param {String} node Name of the node
 * @throws JSJaCJIDInvalidException Thrown if node name contains invalid chars
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setNode = function(node) {
  JSJaCJID._checkNodeName(node);
  this._node = node || '';
  return this;
};

/**
 * Sets the domain part of the jid
 * @param {String} domain Name of the domain
 * @throws JSJaCJIDInvalidException Thrown if domain name contains invalid
 * chars or is empty
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setDomain = function(domain) {
  if (!domain || domain == '')
    throw new JSJaCJIDInvalidException("domain name missing");
  // chars forbidden for a node are not allowed in domain names
  // anyway, so let's check
  JSJaCJID._checkNodeName(domain);
  this._domain = domain;
  return this;
};

/**
 * Sets the resource part of the jid
 * @param {String} resource Name of the resource
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setResource = function(resource) {
  this._resource = resource || '';
  return this;
};

/**
 * The string representation of the full jid
 * @return A string representing the jid
 * @type String
 */
JSJaCJID.prototype.toString = function() {
  var jid = '';
  if (this.getNode() && this.getNode() != '')
    jid = this.getNode() + '@';
  jid += this.getDomain(); // we always have a domain
  if (this.getResource() && this.getResource() != "")
    jid += '/' + this.getResource();
  return jid;
};

/**
 * Removes the resource part of the jid
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.removeResource = function() {
  return this.setResource();
};

/**
 * creates a copy of this JSJaCJID object
 * @return A copy of this
 * @type JSJaCJID
 */
JSJaCJID.prototype.clone = function() {
  return new JSJaCJID(this.toString());
};

/**
 * Compares two jids if they belong to the same entity (i.e. w/o resource)
 * @param {String} jid a jid as string or JSJaCJID object
 * @return 'true' if jid is same entity as this
 * @type Boolean
 */
JSJaCJID.prototype.isEntity = function(jid) {
  if (typeof jid == 'string')
	  jid = (new JSJaCJID(jid));
  jid.removeResource();
  return (this.clone().removeResource().toString() === jid.toString());
};

/**
 * Check if node name is valid
 * @private
 * @param {String} node A name for a node
 * @throws JSJaCJIDInvalidException Thrown if name for node is not allowed
 */
JSJaCJID._checkNodeName = function(nodeprep) {
    if (!nodeprep || nodeprep == '')
      return;
    for (var i=0; i< JSJACJID_FORBIDDEN.length; i++) {
      if (nodeprep.indexOf(JSJACJID_FORBIDDEN[i]) != -1) {
        throw new JSJaCJIDInvalidException("forbidden char in nodename: "+JSJACJID_FORBIDDEN[i]);
      }
    }
};

/**
 * Creates a new Exception of type JSJaCJIDInvalidException
 * @class Exception to indicate invalid values for a jid
 * @constructor
 * @param {String} message The message associated with this Exception
 */
function JSJaCJIDInvalidException(message) {
  /**
   * The exceptions associated message
   * @type String
   */
  this.message = message;
  /**
   * The name of the exception
   * @type String
   */
  this.name = "JSJaCJIDInvalidException";
}

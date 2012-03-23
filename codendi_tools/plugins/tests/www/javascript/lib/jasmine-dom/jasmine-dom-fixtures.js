/*jsl:declare jasmine*/
function readFixtures()
{
  return jasmine.getFixtures()._proxyCallTo('read', arguments);
}

function loadFixtures()
{
  jasmine.getFixtures()._proxyCallTo('load', arguments);
}

function setFixtures(html)
{
  jasmine.getFixtures().set(html);
}

function sandbox(attributes)
{
  return jasmine.getFixtures().sandbox(attributes);
}


jasmine.getFixtures = function()
{
  return jasmine._currentFixtures = jasmine._currentFixtures || new jasmine.Fixtures();
}

jasmine.Fixtures = function()
{
  this.containerId = 'jasmine-fixtures';
  this._fixturesCache = {};
}

jasmine.Fixtures.XHR= window.XMLHttpRequest || (function(){
  var progIdCandidates= ['Msxml2.XMLHTTP.4.0', 'Microsoft.XMLHTTP', 'Msxml2.XMLHTTP'];
  var len= progIdCandidates.length;

  var progId;
  var xhr;
  
  function ConstructXhr()
  {
    return new window.ActiveXObject(ConstructXhr.progId);
  }
  
  while (len--)
  {
    try
    {
      progId= progIdCandidates[len];
      xhr= new window.ActiveXObject(progId);
      //  ActiveXObject constructor throws an exception
      //  if the component isn't available.
      xhr= null;
      ConstructXhr.progId= progId;
      return ConstructXhr;
    }
    catch (e)
    {
      //  Ignore the error
    }
  }
  throw new Error('No XMLHttpRequest implementation found');
})();

jasmine.Fixtures.prototype= {

  set: function(html)
  {
    this.cleanUp();
    this._createContainer(html);
  },

  load: function()
  {
    this.cleanUp();
    this._createContainer(this.read.apply(this, arguments));
  },

  read: function()
  {
    var htmlChunks = [];

    var fixtureUrls = arguments;
    for (var urlCount = fixtureUrls.length, urlIndex = 0; urlIndex < urlCount; urlIndex++)
      htmlChunks.push(this._getFixtureHtml(fixtureUrls[urlIndex]));

    return htmlChunks.join('');
  },

  clearCache: function()
  {
    this._fixturesCache = {};
  },

  cleanUp: function()
  {
    var container= document.getElementById(this.containerId);
    if (container)
      container.parentNode.removeChild(container);
  },

  sandbox: function(attributes)
  {
    var attributesToSet = attributes || {};
    var sandbox= document.createElement('div');
    sandbox.id= 'sandbox';

    if ("string"===typeof(attributes))
    {
      sandbox.innerHTML= attributes;
      if (1===sandbox.childNodes.length && 1===sandbox.firstChild.nodeType)
      {
        sandbox= sandbox.firstChild;
        if (!sandbox.id)
          sandbox.id= 'sandbox';
      }
      return sandbox;
    }
    
    for (var attr in attributesToSet)
      sandbox.setAttribute(attr, attributesToSet[attr]);

    return sandbox;
  },

  _createContainer: function(html)
  {
    var container = document.createElement('div');
    container.id= this.containerId;
    
    if (html && html.nodeType===1)
      container.appendChild(html);
    else
      container.innerHTML= html;
  
    document.body.appendChild(container);
  },

  _getFixtureHtml: function(url)
  { 
    if (void(0)===this._fixturesCache[url])
      this._loadFixtureIntoCache(url);
    return this._fixturesCache[url];
  },

  _loadFixtureIntoCache: function(url)
  {
    var self= this;
    var xhr= new jasmine.Fixtures.XHR();
    xhr.open('GET', url, false);
    xhr.send(null);
    var status= xhr.status;
    var succeeded= 0===status || (status>=200 && status<300) || 304==status;
    
    if (!succeeded)
      throw new Error('Failed to load resource: status=' + status + ' url=' + url);
    this._fixturesCache[url]= xhr.responseText;
  },

  _proxyCallTo: function(methodName, passedArguments)
  {
    return this[methodName].apply(this, passedArguments);
  }
  
};
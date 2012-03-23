/*jsl:declare jasmine*/
/*jsl:declare Sizzle*/
/*jsl:declare Prototype*/
/*jsl:declare jQuery*/

jasmine.DOM = {};

jasmine.DOM.browserTagCaseIndependentHtml = function(html)
{
  var div= document.createElement('div');
  div.innerHTML= html;
  return div.innerHTML;
}

jasmine.DOM.elementToString = function(element)
{
  var div= document.createElement('div');
  div.appendChild(element.cloneNode(true));
  return div.innerHTML;
}

jasmine.DOM.trim= function(string)
{
  var str= string.replace(/^\s+/, '');
  for (var i = str.length - 1; i > 0; --i)
    if (/\S/.test(str.charAt(i)))
    {
      str = str.substring(0, i + 1);
      break;
    }
  return str;
}

jasmine.DOM.slice= function(arrayLike, startIndex)
{
  return [].slice.call(arrayLike, startIndex||0);
}

jasmine.DOM.uniqueId= 1;
jasmine.DOM.assignId= function(element)
{
  return element.id || (element.id=('jasmine_id_' + jasmine.DOM.uniqueId++));
};

/**
  jasmine.DOM.queryAll(selector[, scope]) -> array
 */ 
jasmine.DOM.queryAll= (function(){
  if ('undefined'!==typeof(Sizzle))
    return Sizzle;
  if ('undefined'!==typeof(Prototype))
    return function(selector, node)
    {
      return Element.getElementsBySelector(node||document, selector);
    };
  if ('undefined'!==typeof(jQuery))
    return function(selector, node)
    {
      var result= jQuery(selector, node);
      var nodes= [];
      var len= result.length;

      for (var i=0; i<len; ++i)
        nodes.push(result[i]);
      return nodes;
    };
  if (document.querySelectorAll)
    return function(selector, node)
    {
      if (!node)
        node= document;
      else if (node!==document)
        selector = ['#', jasmine.DOM.assignId(node), ' ', selector].join('');
      return jasmine.DOM.slice(node.querySelectorAll(selector));
    };
    
  throw new Error("Can't determine selector engine...");
})();



jasmine.DOM.matchers = {};


(function(){
  var matchers = {
  
    toHaveClass: function(className)
    {
      var classes= jasmine.DOM.trim(this.actual.className).split(" ");
      return -1!==classes.indexOf(className);
    },

    toBeVisible: function()
    {
      return (this.actual.offsetWidth!==0 || this.actual.offsetHeight!==0);
    },

    toBeHidden: function()
    {
      return (0===this.actual.offsetWidth && 0===this.actual.offsetHeight);
    },

    toBeSelected: function()
    {
      return this.actual.selected;
    },

    toBeChecked: function()
    {
      return this.actual.checked;
    },

    toBeEmpty: function()
    {
      return !this.actual.firstChild;
    },

    toExist: function()
    {
      return !!this.actual;
    },

    toHaveAttr: function(attributeName, expectedAttributeValue)
    {
      if (!this.actual.hasAttribute(attributeName))
        return false;
      return comparePropertyValues(this.actual.getAttribute(attributeName), expectedAttributeValue);
    },

    toHaveId: function(id)
    {
      return this.actual.id===id;
    },

    toHaveHtml: function(html)
    {
      return this.actual.innerHTML === jasmine.DOM.browserTagCaseIndependentHtml(html);
    },

    toHaveText: function(text)
    {
      return (this.actual.textContent||this.actual.innerText) === text;
    },

    toHaveValue: function(value)
    {
      return this.actual.value === value;
    },

    toMatchSelector: function(selector)
    {
      // This isn't efficient
      var nodes= jasmine.DOM.queryAll(selector);
      return -1!==nodes.indexOf(this.actual);
    },

    toContain: function(selector)
    {
      var nodes= jasmine.DOM.queryAll(selector, this.actual);
      return nodes.length > 0;
    }
  };

  function comparePropertyValues(actualValue, expectedValue)
  {
    if (void(0) === expectedValue)
      return void(0) !== actualValue;
    return actualValue == expectedValue;
  }

  function bindMatcher(methodName)
  {
    var originalMatcher = jasmine.Matchers.prototype[methodName];

    jasmine.DOM.matchers[methodName] = function()
    {
      //  If the actual value is a DOM node...
      if (this.actual && this.actual.nodeType)
      {
        var result = matchers[methodName].apply(this, arguments);
        this.actual = jasmine.DOM.elementToString(this.actual);
        return result;
      }

      if (originalMatcher)
        return originalMatcher.apply(this, arguments);

      return false;
    }
    
  }

  for (var methodName in matchers)
    bindMatcher(methodName);
    
})();

beforeEach(function() {
  this.addMatchers(jasmine.DOM.matchers);
});

afterEach(function() {
  jasmine.getFixtures().cleanUp();
});

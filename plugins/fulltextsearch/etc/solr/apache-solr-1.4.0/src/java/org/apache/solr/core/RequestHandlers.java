/**
 * Licensed to the Apache Software Foundation (ASF) under one or more
 * contributor license agreements.  See the NOTICE file distributed with
 * this work for additional information regarding copyright ownership.
 * The ASF licenses this file to You under the Apache License, Version 2.0
 * (the "License"); you may not use this file except in compliance with
 * the License.  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

package org.apache.solr.core;

import org.apache.solr.common.SolrException;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.request.SolrRequestHandler;
import org.apache.solr.util.plugin.SolrCoreAware;
import org.apache.solr.util.plugin.PluginInfoInitialized;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import java.net.URL;
import java.util.Collections;
import java.util.HashMap;
import java.util.Map;
import java.util.concurrent.ConcurrentHashMap;

/**
 */
final class RequestHandlers {
  public static Logger log = LoggerFactory.getLogger(RequestHandlers.class);

  public static final String DEFAULT_HANDLER_NAME="standard";
  protected final SolrCore core;
  // Use a synchronized map - since the handlers can be changed at runtime, 
  // the map implementation should be thread safe
  private final Map<String, SolrRequestHandler> handlers =
      new ConcurrentHashMap<String,SolrRequestHandler>() ;

  /**
   * Trim the trailing '/' if its there, and convert null to empty string.
   * 
   * we want:
   *  /update/csv   and
   *  /update/csv/
   * to map to the same handler 
   * 
   */
  private static String normalize( String p )
  {
    if(p == null) return "";
    if( p.endsWith( "/" ) && p.length() > 1 )
      return p.substring( 0, p.length()-1 );
    
    return p;
  }
  
  public RequestHandlers(SolrCore core) {
      this.core = core;
  }

  /**
   * @return the RequestHandler registered at the given name 
   */
  public SolrRequestHandler get(String handlerName) {
    return handlers.get(normalize(handlerName));
  }

  /**
   * @return a Map of all registered handlers of the specified type.
   */
  public Map<String,SolrRequestHandler> getAll(Class clazz) {
    Map<String,SolrRequestHandler> result 
      = new HashMap<String,SolrRequestHandler>(7);
    for (Map.Entry<String,SolrRequestHandler> e : handlers.entrySet()) {
      if(clazz.isInstance(e.getValue())) result.put(e.getKey(), e.getValue());
    }
    return result;
  }

  /**
   * Handlers must be initialized before calling this function.  As soon as this is
   * called, the handler can immediately accept requests.
   * 
   * This call is thread safe.
   * 
   * @return the previous handler at the given path or null
   */
  public SolrRequestHandler register( String handlerName, SolrRequestHandler handler ) {
    String norm = normalize( handlerName );
    if( handler == null ) {
      return handlers.remove( norm );
    }
    SolrRequestHandler old = handlers.put(norm, handler);
    if (0 != norm.length() && handler instanceof SolrInfoMBean) {
      core.getInfoRegistry().put(handlerName, handler);
    }
    return old;
  }

  /**
   * Returns an unmodifiable Map containing the registered handlers
   */
  public Map<String,SolrRequestHandler> getRequestHandlers() {
    return Collections.unmodifiableMap( handlers );
  }


  /**
   * Read solrconfig.xml and register the appropriate handlers
   * 
   * This function should <b>only</b> be called from the SolrCore constructor.  It is
   * not intended as a public API.
   * 
   * While the normal runtime registration contract is that handlers MUST be initialized
   * before they are registered, this function does not do that exactly.
   *
   * This function registers all handlers first and then calls init() for each one.
   *
   * This is OK because this function is only called at startup and there is no chance that
   * a handler could be asked to handle a request before it is initialized.
   * 
   * The advantage to this approach is that handlers can know what path they are registered
   * to and what other handlers are available at startup.
   * 
   * Handlers will be registered and initialized in the order they appear in solrconfig.xml
   */

  void initHandlersFromConfig(SolrConfig config ){
    Map<PluginInfo,SolrRequestHandler> handlers = new HashMap<PluginInfo,SolrRequestHandler>();
    for (PluginInfo info : config.getPluginInfos(SolrRequestHandler.class.getName())) {
      try {
        SolrRequestHandler requestHandler;
        String startup = info.attributes.get("startup") ;
        if( startup != null ) {
          if( "lazy".equals(startup) ) {
            log.info("adding lazy requestHandler: " + info.className);
            requestHandler = new LazyRequestHandlerWrapper( core, info.className, info.initArgs );
          } else {
            throw new Exception( "Unknown startup value: '"+startup+"' for: "+info.className );
          }
        } else {
          requestHandler = core.createRequestHandler(info.className);
        }
        handlers.put(info,requestHandler);
        if (requestHandler instanceof PluginInfoInitialized) {
          ((PluginInfoInitialized) requestHandler).init(info);
        } else{
          requestHandler.init(info.initArgs);
        }
        SolrRequestHandler old = register(info.name, requestHandler);
        if(old != null) {
          log.warn("Multiple requestHandler registered to the same name: " + info.name + " ignoring: " + old.getClass().getName());
        }
        if(info.isDefault()){
          old = register("",requestHandler);
          if(old != null)
            log.warn("Multiple default requestHandler registered" + " ignoring: " + old.getClass().getName()); 
        }
        log.info("created "+info.name+": " + info.className);
      } catch (Exception e) {
          SolrConfig.severeErrors.add( e );
          SolrException.logOnce(log,null,e);
      }
    }
    for (Map.Entry<PluginInfo,SolrRequestHandler> entry : handlers.entrySet()) {
      entry.getValue().init(entry.getKey().initArgs);
    }

    if(get("") == null) register("", get(DEFAULT_HANDLER_NAME));
  }
    

  /**
   * The <code>LazyRequestHandlerWrapper</core> wraps any {@link SolrRequestHandler}.  
   * Rather then instanciate and initalize the handler on startup, this wrapper waits
   * until it is actually called.  This should only be used for handlers that are
   * unlikely to be used in the normal lifecycle.
   * 
   * You can enable lazy loading in solrconfig.xml using:
   * 
   * <pre>
   *  &lt;requestHandler name="..." class="..." startup="lazy"&gt;
   *    ...
   *  &lt;/requestHandler&gt;
   * </pre>
   * 
   * This is a private class - if there is a real need for it to be public, it could
   * move
   * 
   * @version $Id: RequestHandlers.java 817165 2009-09-21 05:58:02Z noble $
   * @since solr 1.2
   */
  private static final class LazyRequestHandlerWrapper implements SolrRequestHandler, SolrInfoMBean
  {
    private final SolrCore core;
    private String _className;
    private NamedList _args;
    private SolrRequestHandler _handler;
    
    public LazyRequestHandlerWrapper( SolrCore core, String className, NamedList args )
    {
      this.core = core;
      _className = className;
      _args = args;
      _handler = null; // don't initialize
    }
    
    /**
     * In normal use, this function will not be called
     */
    public void init(NamedList args) {
      // do nothing
    }
    
    /**
     * Wait for the first request before initializing the wrapped handler 
     */
    public void handleRequest(SolrQueryRequest req, SolrQueryResponse rsp)  {
      SolrRequestHandler handler = _handler;
      if (handler == null) {
        handler = getWrappedHandler();
      }
      handler.handleRequest( req, rsp );
    }

    public synchronized SolrRequestHandler getWrappedHandler()
    {
      if( _handler == null ) {
        try {
          SolrRequestHandler handler = core.createRequestHandler(_className);
          handler.init( _args );

          if( handler instanceof SolrCoreAware ) {
            ((SolrCoreAware)handler).inform( core );
          }
          _handler = handler;
        }
        catch( Exception ex ) {
          throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, "lazy loading error", ex );
        }
      }
      return _handler;
    }

    public String getHandlerClass()
    {
      return _className;
    }
    
    //////////////////////// SolrInfoMBeans methods //////////////////////

    public String getName() {
      return "Lazy["+_className+"]";
    }

    public String getDescription()
    {
      if( _handler == null ) {
        return getName();
      }
      return _handler.getDescription();
    }
    
    public String getVersion() {
        String rev = "$Revision: 817165 $";
        if( _handler != null ) {
          rev += " :: " + _handler.getVersion();
        }
        return rev;
    }

    public String getSourceId() {
      String rev = "$Id: RequestHandlers.java 817165 2009-09-21 05:58:02Z noble $";
      if( _handler != null ) {
        rev += " :: " + _handler.getSourceId();
      }
      return rev;
    }

    public String getSource() {
      String rev = "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/core/RequestHandlers.java $";
      if( _handler != null ) {
        rev += "\n" + _handler.getSource();
      }
      return rev;
    }
      
    public URL[] getDocs() {
      if( _handler == null ) {
        return null;
      }
      return _handler.getDocs();
    }

    public Category getCategory()
    {
      return Category.QUERYHANDLER;
    }

    public NamedList getStatistics() {
      if( _handler != null ) {
        return _handler.getStatistics();
      }
      NamedList<String> lst = new SimpleOrderedMap<String>();
      lst.add("note", "not initialized yet" );
      return lst;
    }
  }
}








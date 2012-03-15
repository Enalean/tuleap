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

package org.apache.solr.handler.component;

import org.apache.solr.handler.RequestHandlerBase;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.RTimer;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.ModifiableSolrParams;
import org.apache.solr.common.params.ShardParams;
import org.apache.solr.common.SolrException;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.client.solrj.SolrServer;
import org.apache.solr.client.solrj.SolrRequest;
import org.apache.solr.client.solrj.SolrResponse;
import org.apache.solr.client.solrj.request.QueryRequest;
import org.apache.solr.client.solrj.impl.CommonsHttpSolrServer;
import org.apache.solr.client.solrj.impl.BinaryResponseParser;
import org.apache.solr.util.plugin.SolrCoreAware;
import org.apache.solr.core.SolrCore;
import org.apache.lucene.queryParser.ParseException;
import org.apache.commons.httpclient.MultiThreadedHttpConnectionManager;
import org.apache.commons.httpclient.HttpClient;

import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import java.util.*;
import java.util.concurrent.*;

/**
 *
 * Refer SOLR-281
 *
 */
public class SearchHandler extends RequestHandlerBase implements SolrCoreAware
{
  static final String INIT_COMPONENTS = "components";
  static final String INIT_FIRST_COMPONENTS = "first-components";
  static final String INIT_LAST_COMPONENTS = "last-components";

  // socket timeout measured in ms, closes a socket if read
  // takes longer than x ms to complete. throws
  // java.net.SocketTimeoutException: Read timed out exception
  static final String INIT_SO_TIMEOUT = "shard-socket-timeout";

  // connection timeout measures in ms, closes a socket if connection
  // cannot be established within x ms. with a
  // java.net.SocketTimeoutException: Connection timed out
  static final String INIT_CONNECTION_TIMEOUT = "shard-connection-timeout";
  static int soTimeout = 0; //current default values
  static int connectionTimeout = 0; //current default values

  protected static Logger log = LoggerFactory.getLogger(SearchHandler.class);

  protected List<SearchComponent> components = null;

  protected List<String> getDefaultComponents()
  {
    ArrayList<String> names = new ArrayList<String>(6);
    names.add( QueryComponent.COMPONENT_NAME );
    names.add( FacetComponent.COMPONENT_NAME );
    names.add( MoreLikeThisComponent.COMPONENT_NAME );
    names.add( HighlightComponent.COMPONENT_NAME );
    names.add( StatsComponent.COMPONENT_NAME );
    names.add( DebugComponent.COMPONENT_NAME );
    return names;
  }

  /**
   * Initialize the components based on name.  Note, if using {@link #INIT_FIRST_COMPONENTS} or {@link #INIT_LAST_COMPONENTS},
   * then the {@link DebugComponent} will always occur last.  If this is not desired, then one must explicitly declare all components using
   * the {@link #INIT_COMPONENTS} syntax.
   */
  @SuppressWarnings("unchecked")
  public void inform(SolrCore core)
  {
    Object declaredComponents = initArgs.get(INIT_COMPONENTS);
    List<String> first = (List<String>) initArgs.get(INIT_FIRST_COMPONENTS);
    List<String> last  = (List<String>) initArgs.get(INIT_LAST_COMPONENTS);

    List<String> list = null;
    boolean makeDebugLast = true;
    if( declaredComponents == null ) {
      // Use the default component list
      list = getDefaultComponents();

      if( first != null ) {
        List<String> clist = first;
        clist.addAll( list );
        list = clist;
      }

      if( last != null ) {
        list.addAll( last );
      }
    }
    else {
      list = (List<String>)declaredComponents;
      if( first != null || last != null ) {
        throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
            "First/Last components only valid if you do not declare 'components'");
      }
      makeDebugLast = false;
    }

    // Build the component list
    components = new ArrayList<SearchComponent>( list.size() );
    DebugComponent dbgCmp = null;
    for(String c : list){
      SearchComponent comp = core.getSearchComponent( c );
      if (comp instanceof DebugComponent && makeDebugLast == true){
        dbgCmp = (DebugComponent) comp;
      } else {
        components.add(comp);
        log.info("Adding  component:"+comp);
      }
    }
    if (makeDebugLast == true && dbgCmp != null){
      components.add(dbgCmp);
      log.info("Adding  debug component:" + dbgCmp);
    }

    Object co = initArgs.get(INIT_CONNECTION_TIMEOUT);
    if (co != null) {
      connectionTimeout = (Integer) co;
      log.info("Setting shard-connection-timeout to: " + connectionTimeout);
    }

    Object so = initArgs.get(INIT_SO_TIMEOUT);
    if (so != null) {
      soTimeout = (Integer) so;
      log.info("Setting shard-socket-timeout to: " + soTimeout);
    }
  }

  public List<SearchComponent> getComponents() {
    return components;
  }
  

  @Override
  public void handleRequestBody(SolrQueryRequest req, SolrQueryResponse rsp) throws Exception, ParseException, InstantiationException, IllegalAccessException
  {
    // int sleep = req.getParams().getInt("sleep",0);
    // if (sleep > 0) {log.error("SLEEPING for " + sleep);  Thread.sleep(sleep);}
    ResponseBuilder rb = new ResponseBuilder();
    rb.req = req;
    rb.rsp = rsp;
    rb.components = components;
    rb.setDebug(req.getParams().getBool(CommonParams.DEBUG_QUERY, false));

    final RTimer timer = rb.isDebug() ? new RTimer() : null;

    if (timer == null) {
      // non-debugging prepare phase
      for( SearchComponent c : components ) {
        c.prepare(rb);
      }
    } else {
      // debugging prepare phase
      RTimer subt = timer.sub( "prepare" );
      for( SearchComponent c : components ) {
        rb.setTimer( subt.sub( c.getName() ) );
        c.prepare(rb);
        rb.getTimer().stop();
      }
      subt.stop();
    }

    if (rb.shards == null) {
      // a normal non-distributed request

      // The semantics of debugging vs not debugging are different enough that
      // it makes sense to have two control loops
      if(!rb.isDebug()) {
        // Process
        for( SearchComponent c : components ) {
          c.process(rb);
        }
      }
      else {
        // Process
        RTimer subt = timer.sub( "process" );
        for( SearchComponent c : components ) {
          rb.setTimer( subt.sub( c.getName() ) );
          c.process(rb);
          rb.getTimer().stop();
        }
        subt.stop();
        timer.stop();

        // add the timing info
        if( rb.getDebugInfo() == null ) {
          rb.setDebugInfo( new SimpleOrderedMap<Object>() );
        }
        rb.getDebugInfo().add( "timing", timer.asNamedList() );
      }

    } else {
      // a distributed request

      HttpCommComponent comm = new HttpCommComponent();

      if (rb.outgoing == null) {
        rb.outgoing = new LinkedList<ShardRequest>();
      }
      rb.finished = new ArrayList<ShardRequest>();

      int nextStage = 0;
      do {
        rb.stage = nextStage;
        nextStage = ResponseBuilder.STAGE_DONE;

        // call all components
        for( SearchComponent c : components ) {
          // the next stage is the minimum of what all components report
          nextStage = Math.min(nextStage, c.distributedProcess(rb));
        }


        // check the outgoing queue and send requests
        while (rb.outgoing.size() > 0) {

          // submit all current request tasks at once
          while (rb.outgoing.size() > 0) {
            ShardRequest sreq = rb.outgoing.remove(0);
            sreq.actualShards = sreq.shards;
            if (sreq.actualShards==ShardRequest.ALL_SHARDS) {
              sreq.actualShards = rb.shards;
            }
            sreq.responses = new ArrayList<ShardResponse>();

            // TODO: map from shard to address[]
            for (String shard : sreq.actualShards) {
              ModifiableSolrParams params = new ModifiableSolrParams(sreq.params);
              params.remove(ShardParams.SHARDS);      // not a top-level request
              params.remove("indent");
              params.remove(CommonParams.HEADER_ECHO_PARAMS);
              params.set(ShardParams.IS_SHARD, true);  // a sub (shard) request
              String shardHandler = req.getParams().get(ShardParams.SHARDS_QT);
              if (shardHandler == null) {
                params.remove(CommonParams.QT);
              } else {
                params.set(CommonParams.QT, shardHandler);
              }
              comm.submit(sreq, shard, params);
            }
          }


          // now wait for replies, but if anyone puts more requests on
          // the outgoing queue, send them out immediately (by exiting
          // this loop)
          while (rb.outgoing.size() == 0) {
            ShardResponse srsp = comm.takeCompletedOrError();
            if (srsp == null) break;  // no more requests to wait for

            // Was there an exception?  If so, abort everything and
            // rethrow
            if (srsp.getException() != null) {
              comm.cancelAll();
              if (srsp.getException() instanceof SolrException) {
                throw (SolrException)srsp.getException();
              } else {
                throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, srsp.getException());
              }
            }

            rb.finished.add(srsp.getShardRequest());

            // let the components see the responses to the request
            for(SearchComponent c : components) {
              c.handleResponses(rb, srsp.getShardRequest());
            }
          }
        }

        for(SearchComponent c : components) {
            c.finishStage(rb);
         }

        // we are done when the next stage is MAX_VALUE
      } while (nextStage != Integer.MAX_VALUE);
    }
  }

  //////////////////////// SolrInfoMBeans methods //////////////////////

  @Override
  public String getDescription() {
    StringBuilder sb = new StringBuilder();
    sb.append("Search using components: ");
    if( components != null ) {
      for(SearchComponent c : components){
        sb.append(c.getName());
        sb.append(",");
      }
    }
    return sb.toString();
  }

  @Override
  public String getVersion() {
    return "$Revision: 766412 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: SearchHandler.java 766412 2009-04-19 01:31:02Z koji $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/component/SearchHandler.java $";
  }
}


// TODO: generalize how a comm component can fit into search component framework
// TODO: statics should be per-core singletons

class HttpCommComponent {

  // We want an executor that doesn't take up any resources if
  // it's not used, so it could be created statically for
  // the distributed search component if desired.
  //
  // Consider CallerRuns policy and a lower max threads to throttle
  // requests at some point (or should we simply return failure?)
  static Executor commExecutor = new ThreadPoolExecutor(
          0,
          Integer.MAX_VALUE,
          5, TimeUnit.SECONDS, // terminate idle threads after 5 sec
          new SynchronousQueue<Runnable>()  // directly hand off tasks
  );


  static HttpClient client;

  static {
    MultiThreadedHttpConnectionManager mgr = new MultiThreadedHttpConnectionManager();
    mgr.getParams().setDefaultMaxConnectionsPerHost(20);
    mgr.getParams().setMaxTotalConnections(10000);
    mgr.getParams().setConnectionTimeout(SearchHandler.connectionTimeout);
    mgr.getParams().setSoTimeout(SearchHandler.soTimeout);
    // mgr.getParams().setStaleCheckingEnabled(false);
    client = new HttpClient(mgr);    
  }

  CompletionService<ShardResponse> completionService = new ExecutorCompletionService<ShardResponse>(commExecutor);
  Set<Future<ShardResponse>> pending = new HashSet<Future<ShardResponse>>();

  HttpCommComponent() {
  }

  private static class SimpleSolrResponse extends SolrResponse {
    long elapsedTime;
    NamedList<Object> nl;
    
    @Override
    public long getElapsedTime() {
      return elapsedTime;
    }

    @Override
    public NamedList<Object> getResponse() {
      return nl;
    }

    @Override
    public void setResponse(NamedList<Object> rsp) {
      nl = rsp;
    }
  }

  void submit(final ShardRequest sreq, final String shard, final ModifiableSolrParams params) {
    Callable<ShardResponse> task = new Callable<ShardResponse>() {
      public ShardResponse call() throws Exception {

        ShardResponse srsp = new ShardResponse();
        srsp.setShardRequest(sreq);
        srsp.setShard(shard);
        SimpleSolrResponse ssr = new SimpleSolrResponse();
        srsp.setSolrResponse(ssr);
        long startTime = System.currentTimeMillis();

        try {
          // String url = "http://" + shard + "/select";
          String url = "http://" + shard;

          params.remove(CommonParams.WT); // use default (currently javabin)
          params.remove(CommonParams.VERSION);

          SolrServer server = new CommonsHttpSolrServer(url, client);
          // SolrRequest req = new QueryRequest(SolrRequest.METHOD.POST, "/select");
          // use generic request to avoid extra processing of queries
          QueryRequest req = new QueryRequest(params);
          req.setMethod(SolrRequest.METHOD.POST);

          // no need to set the response parser as binary is the default
          // req.setResponseParser(new BinaryResponseParser());
          // srsp.rsp = server.request(req);
          // srsp.rsp = server.query(sreq.params);

          ssr.nl = server.request(req);
        } catch (Throwable th) {
          srsp.setException(th);
          if (th instanceof SolrException) {
            srsp.setResponseCode(((SolrException)th).code());
          } else {
            srsp.setResponseCode(-1);
          }
        }

        ssr.elapsedTime = System.currentTimeMillis() - startTime;

        return srsp;
      }
    };

    pending.add( completionService.submit(task) );
  }

  /** returns a ShardResponse of the last response correlated with a ShardRequest */
  ShardResponse take() {
    while (pending.size() > 0) {
      try {
        Future<ShardResponse> future = completionService.take();
        pending.remove(future);
        ShardResponse rsp = future.get();
        rsp.getShardRequest().responses.add(rsp);
        if (rsp.getShardRequest().responses.size() == rsp.getShardRequest().actualShards.length) {
          return rsp;
        }
      } catch (InterruptedException e) {
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, e);
      } catch (ExecutionException e) {
        // should be impossible... the problem with catching the exception
        // at this level is we don't know what ShardRequest it applied to
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, "Impossible Exception",e);
      }
    }
    return null;
  }


  /** returns a ShardResponse of the last response correlated with a ShardRequest,
   * or immediately returns a ShardResponse if there was an error detected
   */
  ShardResponse takeCompletedOrError() {
    while (pending.size() > 0) {
      try {
        Future<ShardResponse> future = completionService.take();
        pending.remove(future);
        ShardResponse rsp = future.get();
        if (rsp.getException() != null) return rsp; // if exception, return immediately
        // add response to the response list... we do this after the take() and
        // not after the completion of "call" so we know when the last response
        // for a request was received.  Otherwise we might return the same
        // request more than once.
        rsp.getShardRequest().responses.add(rsp);
        if (rsp.getShardRequest().responses.size() == rsp.getShardRequest().actualShards.length) {
          return rsp;
        }
      } catch (InterruptedException e) {
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, e);
      } catch (ExecutionException e) {
        // should be impossible... the problem with catching the exception
        // at this level is we don't know what ShardRequest it applied to
        throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, "Impossible Exception",e);
      }
    }
    return null;
  }


  void cancelAll() {
    for (Future<ShardResponse> future : pending) {
      // TODO: any issues with interrupting?  shouldn't be if
      // there are finally blocks to release connections.
      future.cancel(true);
    }
  }

}

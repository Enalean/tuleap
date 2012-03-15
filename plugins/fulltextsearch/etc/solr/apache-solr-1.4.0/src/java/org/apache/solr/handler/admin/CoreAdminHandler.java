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

package org.apache.solr.handler.admin;

import org.apache.solr.common.SolrException;
import org.apache.solr.common.params.CoreAdminParams;
import org.apache.solr.common.params.CoreAdminParams.CoreAdminAction;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.params.UpdateParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.core.CoreContainer;
import org.apache.solr.core.CoreDescriptor;
import org.apache.solr.core.SolrCore;
import org.apache.solr.core.DirectoryFactory;
import org.apache.solr.handler.RequestHandlerBase;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.request.LocalSolrQueryRequest;
import org.apache.solr.search.SolrIndexSearcher;
import org.apache.solr.util.RefCounted;
import org.apache.solr.update.MergeIndexesCommand;
import org.apache.solr.update.processor.UpdateRequestProcessor;
import org.apache.solr.update.processor.UpdateRequestProcessorChain;
import org.apache.lucene.store.Directory;

import java.io.File;
import java.io.IOException;
import java.util.Date;

/**
 * @version $Id: CoreAdminHandler.java 823646 2009-10-09 18:07:50Z hossman $
 * @since solr 1.3
 */
public class CoreAdminHandler extends RequestHandlerBase {
  protected final CoreContainer coreContainer;

  public CoreAdminHandler() {
    super();
    // Unlike most request handlers, CoreContainer initialization 
    // should happen in the constructor...  
    this.coreContainer = null;
  }


  /**
   * Overloaded ctor to inject CoreContainer into the handler.
   *
   * @param coreContainer Core Container of the solr webapp installed.
   */
  public CoreAdminHandler(final CoreContainer coreContainer) {
    this.coreContainer = coreContainer;
  }


  @Override
  final public void init(NamedList args) {
    throw new SolrException(SolrException.ErrorCode.SERVER_ERROR,
            "CoreAdminHandler should not be configured in solrconf.xml\n" +
                    "it is a special Handler configured directly by the RequestDispatcher");
  }

  /**
   * The instance of CoreContainer this handler handles. This should be the CoreContainer instance that created this
   * handler.
   *
   * @return a CoreContainer instance
   */
  public CoreContainer getCoreContainer() {
    return this.coreContainer;
  }

  @Override
  public void handleRequestBody(SolrQueryRequest req, SolrQueryResponse rsp) throws Exception {
    // Make sure the cores is enabled
    CoreContainer cores = getCoreContainer();
    if (cores == null) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "Core container instance missing");
    }
    boolean doPersist = false;

    // Pick the action
    SolrParams params = req.getParams();
    CoreAdminAction action = CoreAdminAction.STATUS;
    String a = params.get(CoreAdminParams.ACTION);
    if (a != null) {
      action = CoreAdminAction.get(a);
      if (action == null) {
        doPersist = this.handleCustomAction(req, rsp);
      }
    }
    if (action != null) {
      switch (action) {
        case CREATE: {
          doPersist = this.handleCreateAction(req, rsp);
          break;
        }

        case RENAME: {
          doPersist = this.handleRenameAction(req, rsp);
          break;
        }

        case ALIAS: {
          doPersist = this.handleAliasAction(req, rsp);
          break;
        }

        case UNLOAD: {
          doPersist = this.handleUnloadAction(req, rsp);
          break;
        }

        case STATUS: {
          doPersist = this.handleStatusAction(req, rsp);
          break;

        }

        case PERSIST: {
          doPersist = this.handlePersistAction(req, rsp);
          break;
        }

        case RELOAD: {
          doPersist = this.handleReloadAction(req, rsp);
          break;
        }

        case SWAP: {
          doPersist = this.handleSwapAction(req, rsp);
          break;
        }

        case MERGEINDEXES: {
          doPersist = this.handleMergeAction(req, rsp);
          break;
        }

        default: {
          doPersist = this.handleCustomAction(req, rsp);
          break;
        }
        case LOAD:
          break;
      }
    }
    // Should we persist the changes?
    if (doPersist) {
      cores.persist();
      rsp.add("saved", cores.getConfigFile().getAbsolutePath());
    }
    rsp.setHttpCaching(false);
  }

  protected boolean handleMergeAction(SolrQueryRequest req, SolrQueryResponse rsp) throws IOException {
    boolean doPersist = false;
    SolrParams params = req.getParams();
    SolrParams required = params.required();
    String cname = required.get(CoreAdminParams.CORE);
    SolrCore core = coreContainer.getCore(cname);
    if (core != null) {
      try {
        doPersist = coreContainer.isPersistent();

        String[] dirNames = required.getParams(CoreAdminParams.INDEX_DIR);

        DirectoryFactory dirFactory = core.getDirectoryFactory();
        Directory[] dirs = new Directory[dirNames.length];
        for (int i = 0; i < dirNames.length; i++) {
          dirs[i] = dirFactory.open(dirNames[i]);
        }

        UpdateRequestProcessorChain processorChain =
                core.getUpdateProcessingChain(params.get(UpdateParams.UPDATE_PROCESSOR));
        SolrQueryRequest wrappedReq = new LocalSolrQueryRequest(core, req.getParams());
        UpdateRequestProcessor processor =
                processorChain.createProcessor(wrappedReq, rsp);
        processor.processMergeIndexes(new MergeIndexesCommand(dirs));
      } finally {
        core.close();
      }
    }
    return doPersist;
  }

  /**
   * Handle Custom Action.
   * <p/>
   * This method could be overridden by derived classes to handle custom actions. <br> By default - this method throws a
   * solr exception. Derived classes are free to write their derivation if necessary.
   */
  protected boolean handleCustomAction(SolrQueryRequest req, SolrQueryResponse rsp) {
    throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, "Unsupported operation: " +
            req.getParams().get(CoreAdminParams.ACTION));
  }

  /**
   * Handle 'CREATE' action.
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   *
   * @throws SolrException in case of a configuration error.
   */
  protected boolean handleCreateAction(SolrQueryRequest req, SolrQueryResponse rsp) throws SolrException {
    try {
      SolrParams params = req.getParams();
      String name = params.get(CoreAdminParams.NAME);
      CoreDescriptor dcore = new CoreDescriptor(coreContainer, name, params.get(CoreAdminParams.INSTANCE_DIR));

      //  fillup optional parameters
      String opts = params.get(CoreAdminParams.CONFIG);
      if (opts != null)
        dcore.setConfigName(opts);

      opts = params.get(CoreAdminParams.SCHEMA);
      if (opts != null)
        dcore.setSchemaName(opts);

      opts = params.get(CoreAdminParams.DATA_DIR);
      if (opts != null)
        dcore.setDataDir(opts);

      dcore.setCoreProperties(null);
      SolrCore core = coreContainer.create(dcore);
      coreContainer.register(name, core, false);
      rsp.add("core", core.getName());
      return coreContainer.isPersistent();
    } catch (Exception ex) {
      throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "Error executing default implementation of CREATE", ex);
    }
  }

  /**
   * Handle "RENAME" Action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   *
   * @throws SolrException
   */
  protected boolean handleRenameAction(SolrQueryRequest req, SolrQueryResponse rsp) throws SolrException {
    SolrParams params = req.getParams();

    String name = params.get(CoreAdminParams.OTHER);
    String cname = params.get(CoreAdminParams.CORE);
    boolean doPersist = false;

    if (cname.equals(name)) return doPersist;

    SolrCore core = coreContainer.getCore(cname);
    if (core != null) {
      doPersist = coreContainer.isPersistent();
      coreContainer.register(name, core, false);
      coreContainer.remove(cname);
      core.close();
    }
    return doPersist;
  }

  /**
   * Handle "ALIAS" action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   */
  protected boolean handleAliasAction(SolrQueryRequest req, SolrQueryResponse rsp) {
    SolrParams params = req.getParams();

    String name = params.get(CoreAdminParams.OTHER);
    String cname = params.get(CoreAdminParams.CORE);
    boolean doPersist = false;
    if (cname.equals(name)) return doPersist;

    SolrCore core = coreContainer.getCore(cname);
    if (core != null) {
      doPersist = coreContainer.isPersistent();
      coreContainer.register(name, core, false);
      // no core.close() since each entry in the cores map should increase the ref
    }
    return doPersist;
  }


  /**
   * Handle "UNLOAD" Action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   */
  protected boolean handleUnloadAction(SolrQueryRequest req, SolrQueryResponse rsp) throws SolrException {
    SolrParams params = req.getParams();
    String cname = params.get(CoreAdminParams.CORE);
    SolrCore core = coreContainer.remove(cname);
    if(core == null){
       throw new SolrException(SolrException.ErrorCode.BAD_REQUEST,
              "No such core exists '"+cname+"'");
    }
    core.close();
    return coreContainer.isPersistent();

  }

  /**
   * Handle "STATUS" action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   */
  protected boolean handleStatusAction(SolrQueryRequest req, SolrQueryResponse rsp)
          throws SolrException {
    SolrParams params = req.getParams();

    String cname = params.get(CoreAdminParams.CORE);
    boolean doPersist = false;
    NamedList<Object> status = new SimpleOrderedMap<Object>();
    try {
      if (cname == null) {
        for (String name : coreContainer.getCoreNames()) {
          status.add(name, getCoreStatus(coreContainer, name));
        }
      } else {
        status.add(cname, getCoreStatus(coreContainer, cname));
      }
      rsp.add("status", status);
      doPersist = false; // no state change
      return doPersist;
    } catch (Exception ex) {
      throw new SolrException(SolrException.ErrorCode.SERVER_ERROR,
              "Error handling 'status' action ", ex);
    }
  }

  /**
   * Handler "PERSIST" action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   *
   * @throws SolrException
   */
  protected boolean handlePersistAction(SolrQueryRequest req, SolrQueryResponse rsp)
          throws SolrException {
    SolrParams params = req.getParams();
    boolean doPersist = false;
    String fileName = params.get(CoreAdminParams.FILE);
    if (fileName != null) {
      File file = new File(coreContainer.getConfigFile().getParentFile(), fileName);
      coreContainer.persistFile(file);
      rsp.add("saved", file.getAbsolutePath());
      doPersist = false;
    } else if (!coreContainer.isPersistent()) {
      throw new SolrException(SolrException.ErrorCode.FORBIDDEN, "Persistence is not enabled");
    } else
      doPersist = true;

    return doPersist;
  }

  /**
   * Handler "RELOAD" action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   */
  protected boolean handleReloadAction(SolrQueryRequest req, SolrQueryResponse rsp) {
    SolrParams params = req.getParams();
    String cname = params.get(CoreAdminParams.CORE);
    try {
      coreContainer.reload(cname);
      return false; // no change on reload
    } catch (Exception ex) {
      throw new SolrException(SolrException.ErrorCode.SERVER_ERROR, "Error handling 'reload' action", ex);
    }
  }

  /**
   * Handle "SWAP" action
   *
   * @param req
   * @param rsp
   *
   * @return true if a modification has resulted that requires persistance 
   *         of the CoreContainer configuration.
   */
  protected boolean handleSwapAction(SolrQueryRequest req, SolrQueryResponse rsp) {
    final SolrParams params = req.getParams();
    final SolrParams required = params.required();

    final String cname = params.get(CoreAdminParams.CORE);
    boolean doPersist = params.getBool(CoreAdminParams.PERSISTENT, coreContainer.isPersistent());
    String other = required.get(CoreAdminParams.OTHER);
    coreContainer.swap(cname, other);
    return doPersist;

  }

  protected NamedList<Object> getCoreStatus(CoreContainer cores, String cname) throws IOException {
    NamedList<Object> info = new SimpleOrderedMap<Object>();
    SolrCore core = cores.getCore(cname);
    if (core != null) {
      try {
        info.add("name", core.getName());
        info.add("instanceDir", normalizePath(core.getResourceLoader().getInstanceDir()));
        info.add("dataDir", normalizePath(core.getDataDir()));
        info.add("startTime", new Date(core.getStartTime()));
        info.add("uptime", System.currentTimeMillis() - core.getStartTime());
        RefCounted<SolrIndexSearcher> searcher = core.getSearcher();
        try {
          info.add("index", LukeRequestHandler.getIndexInfo(searcher.get().getReader(), false));
        } finally {
          searcher.decref();
        }
      } finally {
        core.close();
      }
    }
    return info;
  }

  protected static String normalizePath(String path) {
    if (path == null)
      return null;
    path = path.replace('/', File.separatorChar);
    path = path.replace('\\', File.separatorChar);
    return path;
  }


  //////////////////////// SolrInfoMBeans methods //////////////////////

  @Override
  public String getDescription() {
    return "Manage Multiple Solr Cores";
  }

  @Override
  public String getVersion() {
    return "$Revision: 823646 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: CoreAdminHandler.java 823646 2009-10-09 18:07:50Z hossman $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/admin/CoreAdminHandler.java $";
  }
}

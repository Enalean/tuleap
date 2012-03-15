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
package org.apache.solr.handler.dataimport;

import org.apache.solr.core.SolrCore;
import org.apache.solr.request.LocalSolrQueryRequest;
import org.apache.solr.util.AbstractSolrTestCase;
import org.apache.solr.common.util.NamedList;

import java.io.IOException;
import java.io.File;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

/**
 * <p>
 * Abstract base class for DataImportHandler tests
 * </p>
 * <p/>
 * <b>This API is experimental and subject to change</b>
 *
 * @version $Id: AbstractDataImportHandlerTest.java 824910 2009-10-13 20:28:04Z ehatcher $
 * @since solr 1.3
 */
public abstract class AbstractDataImportHandlerTest extends
        AbstractSolrTestCase {

  @Override
  public void setUp() throws Exception {
    super.setUp();
  }

  @Override
  public void tearDown() throws Exception {
    // remove dataimport.properties
    File f = new File("solr/conf/dataimport.properties");
    log.info("Looking for dataimport.properties at: " + f.getAbsolutePath());
    if (f.exists()) {
      log.info("Deleting dataimport.properties");
      if (!f.delete())
        log.warn("Could not delete dataimport.properties");
    }
    super.tearDown();
  }

  protected String loadDataConfig(String dataConfigFileName) {
    try {
      SolrCore core = h.getCore();
      return SolrWriter.getResourceAsString(core.getResourceLoader()
              .openResource(dataConfigFileName));
    } catch (IOException e) {
      e.printStackTrace();
      return null;
    }
  }

  protected void runFullImport(String dataConfig) throws Exception {
    LocalSolrQueryRequest request = lrf.makeRequest("command", "full-import",
            "debug", "on", "clean", "true", "commit", "true", "dataConfig",
            dataConfig);
    h.query("/dataimport", request);
  }

  protected void runDeltaImport(String dataConfig) throws Exception {
    LocalSolrQueryRequest request = lrf.makeRequest("command", "delta-import",
            "debug", "on", "clean", "false", "commit", "true", "dataConfig",
            dataConfig);
    h.query("/dataimport", request);
  }

  /**
   * Runs a full-import using the given dataConfig and the provided request parameters.
   *
   * By default, debug=on, clean=true and commit=true are passed which can be overridden.
   *
   * @param dataConfig the data-config xml as a string
   * @param extraParams any extra request parameters needed to be passed to DataImportHandler
   * @throws Exception in case of any error
   */
  protected void runFullImport(String dataConfig, Map<String, String> extraParams) throws Exception {
    HashMap<String, String> params = new HashMap<String, String>();
    params.put("command", "full-import");
    params.put("debug", "on");
    params.put("dataConfig", dataConfig);
    params.put("clean", "true");
    params.put("commit", "true");
    params.putAll(extraParams);
    NamedList l = new NamedList();
    for (Map.Entry<String, String> e : params.entrySet()) {
      l.add(e.getKey(),e.getValue());
    }
    LocalSolrQueryRequest request = new LocalSolrQueryRequest(h.getCore(), l);  
    h.query("/dataimport", request);
  }

  /**
   * Helper for creating a Context instance. Useful for testing Transformers
   */
  @SuppressWarnings("unchecked")
  public static TestContext getContext(DataConfig.Entity parentEntity,
                                   VariableResolverImpl resolver, DataSource parentDataSource,
                                   String currProcess, final List<Map<String, String>> entityFields,
                                   final Map<String, String> entityAttrs) {
    if (resolver == null) resolver = new VariableResolverImpl();
    final Context delegate = new ContextImpl(parentEntity, resolver,
            parentDataSource, currProcess,
            new HashMap<String, Object>(), null, null);
    return new TestContext(entityAttrs, delegate, entityFields, parentEntity == null);
  }

  /**
   * Strings at even index are keys, odd-index strings are values in the
   * returned map
   */
  @SuppressWarnings("unchecked")
  public static Map createMap(Object... args) {
    Map result = new HashMap();

    if (args == null || args.length == 0)
      return result;

    for (int i = 0; i < args.length - 1; i += 2)
      result.put(args[i], args[i + 1]);

    return result;
  }

  static class TestContext extends Context {
    private final Map<String, String> entityAttrs;
    private final Context delegate;
    private final List<Map<String, String>> entityFields;
    private final boolean root;
    String script,scriptlang;

    public TestContext(Map<String, String> entityAttrs, Context delegate,
                       List<Map<String, String>> entityFields, boolean root) {
      this.entityAttrs = entityAttrs;
      this.delegate = delegate;
      this.entityFields = entityFields;
      this.root = root;
    }

    public String getEntityAttribute(String name) {
      return entityAttrs == null ? delegate.getEntityAttribute(name) : entityAttrs.get(name);
    }

    public String getResolvedEntityAttribute(String name) {
      return entityAttrs == null ? delegate.getResolvedEntityAttribute(name) :
              delegate.getVariableResolver().replaceTokens(entityAttrs.get(name));
    }

    public List<Map<String, String>> getAllEntityFields() {
      return entityFields == null ? delegate.getAllEntityFields()
              : entityFields;
    }

    public VariableResolver getVariableResolver() {
      return delegate.getVariableResolver();
    }

    public DataSource getDataSource() {
      return delegate.getDataSource();
    }

    public boolean isRootEntity() {
      return root;
    }

    public String currentProcess() {
      return delegate.currentProcess();
    }

    public Map<String, Object> getRequestParameters() {
      return delegate.getRequestParameters();
    }

    public EntityProcessor getEntityProcessor() {
      return null;
    }

    public void setSessionAttribute(String name, Object val, String scope) {
      delegate.setSessionAttribute(name, val, scope);
    }

    public Object getSessionAttribute(String name, String scope) {
      return delegate.getSessionAttribute(name, scope);
    }

    public Context getParentContext() {
      return delegate.getParentContext();
    }

    public DataSource getDataSource(String name) {
      return delegate.getDataSource(name);
    }

    public SolrCore getSolrCore() {
      return delegate.getSolrCore();
    }

    public Map<String, Object> getStats() {
      return delegate.getStats();
    }


    public String getScript() {
      return script == null ? delegate.getScript() : script;
    }

    public String getScriptLanguage() {
      return scriptlang == null ? delegate.getScriptLanguage() : scriptlang;
    }

    public void deleteDoc(String id) {

    }

    public void deleteDocByQuery(String query) {

    }
  }
}

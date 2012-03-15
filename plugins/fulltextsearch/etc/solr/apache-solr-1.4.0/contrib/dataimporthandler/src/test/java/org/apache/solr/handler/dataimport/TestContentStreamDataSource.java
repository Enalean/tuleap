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

import junit.framework.TestCase;
import org.apache.commons.io.FileUtils;
import org.apache.solr.client.solrj.embedded.JettySolrRunner;
import org.apache.solr.client.solrj.impl.CommonsHttpSolrServer;
import org.apache.solr.client.solrj.request.DirectXmlRequest;
import org.apache.solr.client.solrj.response.QueryResponse;
import org.apache.solr.common.SolrDocument;
import org.apache.solr.common.SolrDocumentList;
import org.apache.solr.common.params.ModifiableSolrParams;
import org.apache.solr.util.AbstractSolrTestCase;

import java.io.File;

/**
 * Test for ContentStreamDataSource
 *
 * @version $Id: TestContentStreamDataSource.java 755141 2009-03-17 07:50:09Z shalin $
 * @since solr 1.4
 */
public class TestContentStreamDataSource extends TestCase {
  private static final String CONF_DIR = "." + File.separator + "solr" + File.separator + "conf" + File.separator;
  SolrInstance instance = null;
  JettySolrRunner jetty;


  public void setUp() throws Exception {
    instance = new SolrInstance("inst", null);
    instance.setUp();
    jetty = createJetty(instance);

  }

  public void testSimple() throws Exception {
    DirectXmlRequest req = new DirectXmlRequest("/dataimport", xml);
    ModifiableSolrParams params = new ModifiableSolrParams();
    params.set("command", "full-import");
    params.set("clean", "false");
    req.setParams(params);
    String url = "http://localhost:" + jetty.getLocalPort() + "/solr";
    CommonsHttpSolrServer solrServer = new CommonsHttpSolrServer(url);
    solrServer.request(req);
    ModifiableSolrParams qparams = new ModifiableSolrParams();
    qparams.add("q", "*:*");
    QueryResponse qres = solrServer.query(qparams);
    SolrDocumentList results = qres.getResults();
    assertEquals(2, results.getNumFound());
    SolrDocument doc = results.get(0);
    assertEquals("1", doc.getFieldValue("id"));
    assertEquals("Hello C1", doc.getFieldValue("desc"));
  }

  private class SolrInstance extends AbstractSolrTestCase {
    String name;
    Integer port;
    File homeDir;
    File confDir;

    /**
     * if masterPort is null, this instance is a master -- otherwise this instance is a slave, and assumes the master is
     * on localhost at the specified port.
     */
    public SolrInstance(String name, Integer port) {
      this.name = name;
      this.port = port;
    }

    public String getHomeDir() {
      return homeDir.toString();
    }

    @Override
    public String getSchemaFile() {
      return CONF_DIR + "dataimport-schema.xml";
    }

    public String getConfDir() {
      return confDir.toString();
    }

    public String getDataDir() {
      return dataDir.toString();
    }

    @Override
    public String getSolrConfigFile() {
      return CONF_DIR + "contentstream-solrconfig.xml";
    }

    public void setUp() throws Exception {

      String home = System.getProperty("java.io.tmpdir")
              + File.separator
              + getClass().getName() + "-" + System.currentTimeMillis();


      homeDir = new File(home + "inst");
      dataDir = new File(homeDir, "data");
      confDir = new File(homeDir, "conf");

      homeDir.mkdirs();
      dataDir.mkdirs();
      confDir.mkdirs();

      File f = new File(confDir, "solrconfig.xml");
      FileUtils.copyFile(new File(getSolrConfigFile()), f);
      f = new File(confDir, "schema.xml");

      FileUtils.copyFile(new File(getSchemaFile()), f);
      f = new File(confDir, "data-config.xml");
      FileUtils.copyFile(new File(CONF_DIR + "dataconfig-contentstream.xml"), f);
    }

    public void tearDown() throws Exception {
      super.tearDown();
      AbstractSolrTestCase.recurseDelete(homeDir);
    }
  }

  private JettySolrRunner createJetty(SolrInstance instance) throws Exception {
    System.setProperty("solr.solr.home", instance.getHomeDir());
    System.setProperty("solr.data.dir", instance.getDataDir());
    JettySolrRunner jetty = new JettySolrRunner("/solr", 0);
    jetty.start();
    return jetty;
  }

  static String xml = "<root>\n"
          + "<b>\n"
          + "  <id>1</id>\n"
          + "  <c>Hello C1</c>\n"
          + "</b>\n"
          + "<b>\n"
          + "  <id>2</id>\n"
          + "  <c>Hello C2</c>\n"
          + "</b>\n" + "</root>";
}

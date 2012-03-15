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

import java.io.File;
import java.io.FileOutputStream;
import java.io.IOException;
import java.util.Properties;

import javax.xml.parsers.ParserConfigurationException;

import org.apache.lucene.analysis.standard.StandardAnalyzer;
import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;
import org.apache.lucene.index.IndexWriter;
import org.apache.lucene.index.IndexWriter.MaxFieldLength;
import org.apache.lucene.queryParser.ParseException;
import org.apache.lucene.queryParser.QueryParser;
import org.apache.lucene.search.Hits;
import org.apache.lucene.search.IndexSearcher;
import org.apache.lucene.search.Query;
import org.apache.lucene.store.Directory;
import org.apache.lucene.store.FSDirectory;
import org.apache.solr.common.SolrException;
import org.apache.solr.util.AbstractSolrTestCase;
import org.apache.solr.util.TestHarness;
import org.junit.Test;
import org.xml.sax.SAXException;

/**
 * @version $Id: TestArbitraryIndexDir.java 809403 2009-08-30 19:08:49Z shalin $
 */
public class TestArbitraryIndexDir extends AbstractSolrTestCase{

  public void setUp() throws Exception {
    dataDir = new File(System.getProperty("java.io.tmpdir")
        + System.getProperty("file.separator")
        + getClass().getName() + "-" + System.currentTimeMillis() + System.getProperty("file.separator") + "solr"
        + System.getProperty("file.separator") + "data");
    dataDir.mkdirs();

    solrConfig = h.createConfig(getSolrConfigFile());
    h = new TestHarness( dataDir.getAbsolutePath(),
        solrConfig,
        getSchemaFile());
    lrf = h.getRequestFactory
    ("standard",0,20,"version","2.2");
  }
  
  public void tearDown() throws Exception {
    super.tearDown();

  }

  @Override
  public String getSchemaFile() {
    return "schema12.xml";
  }

  @Override
  public String getSolrConfigFile() {
    return "solrconfig.xml";
  }

  @Test
  public void testLoadNewIndexDir() throws IOException, ParserConfigurationException, SAXException, ParseException{
    //add a doc in original index dir
    assertU(adoc("id", String.valueOf(1),
        "name", "name"+String.valueOf(1)));
    //create a new index dir and index.properties file
    File idxprops = new File(h.getCore().getDataDir() + "index.properties");
    Properties p = new Properties();
    File newDir = new File(h.getCore().getDataDir() + "index_temp");
    newDir.mkdirs();
    p.put("index", newDir.getName());
    FileOutputStream os = null;
    try {
      os = new FileOutputStream(idxprops);
      p.store(os, "index properties");
    } catch (Exception e) {
      throw new SolrException(SolrException.ErrorCode.SERVER_ERROR,
          "Unable to write index.properties", e);
    }

    //add a doc in the new index dir
    Directory dir = FSDirectory.getDirectory(newDir);
    IndexWriter iw = new IndexWriter(dir, new StandardAnalyzer(), new MaxFieldLength(1000));
    Document doc = new Document();
    doc.add(new Field("id", "2", Field.Store.YES, Field.Index.TOKENIZED));
    doc.add(new Field("name", "name2", Field.Store.YES, Field.Index.TOKENIZED));
    iw.addDocument(doc);
    iw.commit();
    iw.close();

    //commit will cause searcher to open with the new index dir
    assertU(commit());
    //new index dir contains just 1 doc.
    assertQ("return doc with id 2",
        req("id:2"),
        "*[count(//doc)=1]"
    );
    newDir.delete();
  }
}

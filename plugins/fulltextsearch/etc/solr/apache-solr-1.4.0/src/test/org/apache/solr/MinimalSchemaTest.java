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

package org.apache.solr;

import org.apache.solr.request.*;
import org.apache.solr.util.*;

import java.util.Set;

/**
 * A test of basic features using the minial legal solr schema.
 */
public class MinimalSchemaTest extends AbstractSolrTestCase {

  public String getSchemaFile() { return "solr/conf/schema-minimal.xml"; } 


  /**
   * NOTE: we explicilty use the general 'solrconfig.xml' file here, in 
   * an attempt to test as many braod features as possible.
   *
   * Do not change this to point at some other "simpler" solrconfig.xml 
   * just because you want to add a new test case using solrconfig.xml, 
   * but your new testcase adds a feature that breaks this test.
   */
  public String getSolrConfigFile() { return "solr/conf/solrconfig.xml"; }
    
  public void setUp() throws Exception {
    super.setUp();

    /* make sure some missguided soul doesn't inadvertantly give us 
       a uniqueKey field and defeat the point of hte tests
    */
    assertNull("UniqueKey Field isn't null", 
               h.getCore().getSchema().getUniqueKeyField());

    lrf.args.put("version","2.0");

    assertU("Simple assertion that adding a document works",
            adoc("id",  "4055",
                 "subject", "Hoss",
                 "project", "Solr"));
    assertU(adoc("id",  "4056",
                 "subject", "Yonik",
                 "project", "Solr"));
    assertU(commit());
    assertU(optimize());

  }

  public void testSimpleQueries() {

    assertQ("couldn't find subject hoss",
            req("subject:Hoss")
            ,"//result[@numFound=1]"
            ,"//str[@name='id'][.='4055']"
            );

    assertQ("couldn't find subject Yonik",
            req("subject:Yonik")
            ,"//result[@numFound=1]"
            ,"//str[@name='id'][.='4056']"
            );
  }

  /** SOLR-1371 */
  public void testLuke() {
    
    assertQ("basic luke request failed",
            req("qt", "/admin/luke")
            ,"//int[@name='numDocs'][.='2']"
            ,"//int[@name='numTerms'][.='5']"
            );

    assertQ("luke show schema failed",
            req("qt", "/admin/luke",
                "show","schema")
            ,"//int[@name='numDocs'][.='2']"
            ,"//int[@name='numTerms'][.='5']"
            ,"//null[@name='uniqueKeyField']"
            ,"//null[@name='defaultSearchField']"
            );

  }


  /** 
   * Iterates over all (non "/update/*") handlers in the core and hits 
   * them with a request (using some simple params) to verify that they 
   * don't generate an error against the minimal schema
   */
  public void testAllConfiguredHandlers() {
    Set<String> handlerNames = h.getCore().getRequestHandlers().keySet();
    for (String handler : handlerNames) {
      try {
        if (handler.startsWith("/update")) {
          continue;
        }

        assertQ("failure w/handler: '" + handler + "'",
                req("qt", handler,
                    // this should be fairly innoculous for any type of query
                    "q", "foo:bar")
                ,"//lst[@name='responseHeader']"
                );
      } catch (Exception e) {
        throw new RuntimeException("exception w/handler: '" + handler + "'", 
                                   e);
      }
    }
  }
}



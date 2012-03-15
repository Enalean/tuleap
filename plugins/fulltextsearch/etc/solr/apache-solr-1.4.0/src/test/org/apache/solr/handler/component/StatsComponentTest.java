package org.apache.solr.handler.component;
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

import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.MapSolrParams;
import org.apache.solr.common.params.StatsParams;
import org.apache.solr.common.SolrInputDocument;
import org.apache.solr.core.SolrCore;
import org.apache.solr.request.LocalSolrQueryRequest;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.util.AbstractSolrTestCase;

import java.util.HashMap;
import java.util.Map;

/**
 * Statistics Component Test
 */
public class StatsComponentTest extends AbstractSolrTestCase {

  @Override
  public String getSchemaFile() {
    return "schema11.xml";
  }

  @Override
  public String getSolrConfigFile() {
    return "solrconfig.xml";
  }

  @Override
  public void setUp() throws Exception {
    super.setUp();
    lrf = h.getRequestFactory("standard", 0, 20);
  }

  public void testStats() throws Exception {
    for (String f : new String[] {
            "stats_i","stats_l","stats_f","stats_d",
            "stats_ti","stats_tl","stats_tf","stats_td"
    }) {
      doTestFieldStatisticsResult(f);
      doTestFieldStatisticsMissingResult(f);
      doTestFacetStatisticsResult(f);
      doTestFacetStatisticsMissingResult(f);
    }

    for (String f : new String[] {"stats_ii", // plain int
            "stats_is",    // sortable int
            "stats_tis","stats_tfs","stats_tls","stats_tds"  // trie fields
                                  }) {
      doTestMVFieldStatisticsResult(f);
    }
    
  }

  public void doTestFieldStatisticsResult(String f) throws Exception {
    assertU(adoc("id", "1", f, "-10"));
    assertU(adoc("id", "2", f, "-20"));
    assertU(adoc("id", "3", f, "-30"));
    assertU(adoc("id", "4", f, "-40"));
    assertU(commit());

    assertQ("test statistics values", req("q","*:*", "stats","true", "stats.field",f)
            , "//double[@name='min'][.='-40.0']"
            , "//double[@name='max'][.='-10.0']"
            , "//double[@name='sum'][.='-100.0']"
            , "//long[@name='count'][.='4']"
            , "//long[@name='missing'][.='0']"
            , "//double[@name='sumOfSquares'][.='3000.0']"
            , "//double[@name='mean'][.='-25.0']"
            , "//double[@name='stddev'][.='12.909944487358056']"
    );    
  }


  public void doTestMVFieldStatisticsResult(String f) throws Exception {
    assertU(adoc("id", "1", f, "-10", f, "-100", "active_s", "true"));
    assertU(adoc("id", "2", f, "-20", f, "200", "active_s", "true"));
    assertU(adoc("id", "3", f, "-30", f, "-1", "active_s", "false"));
    assertU(adoc("id", "4", f, "-40", f, "10", "active_s", "false"));
    assertU(adoc("id", "5", "active_s", "false"));
    assertU(commit());

    assertQ("test statistics values", req("q","*:*", "stats","true", "stats.field",f)
            , "//double[@name='min'][.='-100.0']"
            , "//double[@name='max'][.='200.0']"
            , "//double[@name='sum'][.='9.0']"
            , "//long[@name='count'][.='8']"
            , "//long[@name='missing'][.='1']"
            , "//double[@name='sumOfSquares'][.='53101.0']"
            , "//double[@name='mean'][.='1.125']"
            , "//double[@name='stddev'][.='87.08852228787508']"
    );

    assertQ("test statistics values", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
            , "//double[@name='min'][.='-100.0']"
            , "//double[@name='max'][.='200.0']"
            , "//double[@name='sum'][.='9.0']"
            , "//long[@name='count'][.='8']"
            , "//long[@name='missing'][.='1']"
            , "//double[@name='sumOfSquares'][.='53101.0']"
            , "//double[@name='mean'][.='1.125']"
            , "//double[@name='stddev'][.='87.08852228787508']"
    );

    assertQ("test value for active_s=true", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
            , "//lst[@name='true']/double[@name='min'][.='-100.0']"
            , "//lst[@name='true']/double[@name='max'][.='200.0']"
            , "//lst[@name='true']/double[@name='sum'][.='70.0']"
            , "//lst[@name='true']/long[@name='count'][.='4']"
            , "//lst[@name='true']/long[@name='missing'][.='0']"
            , "//lst[@name='true']/double[@name='sumOfSquares'][.='50500.0']"
            , "//lst[@name='true']/double[@name='mean'][.='17.5']"
            , "//lst[@name='true']/double[@name='stddev'][.='128.16005617976296']"
    );

    assertQ("test value for active_s=false", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s", "indent","true")
            , "//lst[@name='false']/double[@name='min'][.='-40.0']"
            , "//lst[@name='false']/double[@name='max'][.='10.0']"
            , "//lst[@name='false']/double[@name='sum'][.='-61.0']"
            , "//lst[@name='false']/long[@name='count'][.='4']"
            , "//lst[@name='false']/long[@name='missing'][.='1']"
            , "//lst[@name='false']/double[@name='sumOfSquares'][.='2601.0']"
            , "//lst[@name='false']/double[@name='mean'][.='-15.25']"
            , "//lst[@name='false']/double[@name='stddev'][.='23.59908190304586']"
    );


  }


  public void doTestFieldStatisticsMissingResult(String f) throws Exception {
    assertU(adoc("id", "1", f, "-10"));
    assertU(adoc("id", "2", f, "-20"));
    assertU(adoc("id", "3"));
    assertU(adoc("id", "4", f, "-40"));
    assertU(commit());

    assertQ("test statistics values", req("q","*:*", "stats","true", "stats.field",f)
            , "//double[@name='min'][.='-40.0']"
            , "//double[@name='max'][.='-10.0']"
            , "//double[@name='sum'][.='-70.0']"
            , "//long[@name='count'][.='3']"
            , "//long[@name='missing'][.='1']"
            , "//double[@name='sumOfSquares'][.='2100.0']"
            , "//double[@name='mean'][.='-23.333333333333332']"
            , "//double[@name='stddev'][.='15.275252316519467']"
    );
  }

  public void doTestFacetStatisticsResult(String f) throws Exception {
    assertU(adoc("id", "1", f, "10", "active_s", "true"));
    assertU(adoc("id", "2", f, "20", "active_s", "true"));
    assertU(adoc("id", "3", f, "30", "active_s", "false"));
    assertU(adoc("id", "4", f, "40", "active_s", "false"));
    assertU(commit());

    assertQ("test value for active_s=true", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
            , "//lst[@name='true']/double[@name='min'][.='10.0']"
            , "//lst[@name='true']/double[@name='max'][.='20.0']"
            , "//lst[@name='true']/double[@name='sum'][.='30.0']"
            , "//lst[@name='true']/long[@name='count'][.='2']"
            , "//lst[@name='true']/long[@name='missing'][.='0']"
            , "//lst[@name='true']/double[@name='sumOfSquares'][.='500.0']"
            , "//lst[@name='true']/double[@name='mean'][.='15.0']"
            , "//lst[@name='true']/double[@name='stddev'][.='7.0710678118654755']"
    );

    assertQ("test value for active_s=false", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
            , "//lst[@name='false']/double[@name='min'][.='30.0']"
            , "//lst[@name='false']/double[@name='max'][.='40.0']"
            , "//lst[@name='false']/double[@name='sum'][.='70.0']"
            , "//lst[@name='false']/long[@name='count'][.='2']"
            , "//lst[@name='false']/long[@name='missing'][.='0']"
            , "//lst[@name='false']/double[@name='sumOfSquares'][.='2500.0']"
            , "//lst[@name='false']/double[@name='mean'][.='35.0']"
            , "//lst[@name='false']/double[@name='stddev'][.='7.0710678118654755']"
    );
  }
  
  public void doTestFacetStatisticsMissingResult(String f) throws Exception {
	    assertU(adoc("id", "1", f, "10", "active_s", "true"));
	    assertU(adoc("id", "2", f, "20", "active_s", "true"));
	    assertU(adoc("id", "3", "active_s", "false"));
	    assertU(adoc("id", "4", f, "40", "active_s", "false"));
	    assertU(commit());

	    assertQ("test value for active_s=true", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
	            , "//lst[@name='true']/double[@name='min'][.='10.0']"
	            , "//lst[@name='true']/double[@name='max'][.='20.0']"
	            , "//lst[@name='true']/double[@name='sum'][.='30.0']"
	            , "//lst[@name='true']/long[@name='count'][.='2']"
	            , "//lst[@name='true']/long[@name='missing'][.='0']"
	            , "//lst[@name='true']/double[@name='sumOfSquares'][.='500.0']"
	            , "//lst[@name='true']/double[@name='mean'][.='15.0']"
	            , "//lst[@name='true']/double[@name='stddev'][.='7.0710678118654755']"
	    );

	    assertQ("test value for active_s=false", req("q","*:*", "stats","true", "stats.field",f, "stats.facet","active_s")
	            , "//lst[@name='false']/double[@name='min'][.='40.0']"
	            , "//lst[@name='false']/double[@name='max'][.='40.0']"
	            , "//lst[@name='false']/double[@name='sum'][.='40.0']"
	            , "//lst[@name='false']/long[@name='count'][.='1']"
	            , "//lst[@name='false']/long[@name='missing'][.='1']"
	            , "//lst[@name='false']/double[@name='sumOfSquares'][.='1600.0']"
	            , "//lst[@name='false']/double[@name='mean'][.='40.0']"
	            , "//lst[@name='false']/double[@name='stddev'][.='0.0']"
	    );
	  }
}

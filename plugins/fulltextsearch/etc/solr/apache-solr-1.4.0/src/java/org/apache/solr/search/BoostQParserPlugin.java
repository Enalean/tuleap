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
package org.apache.solr.search;

import org.apache.lucene.queryParser.ParseException;
import org.apache.lucene.search.Query;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.search.function.BoostedQuery;
import org.apache.solr.search.function.FunctionQuery;
import org.apache.solr.search.function.QueryValueSource;
import org.apache.solr.search.function.ValueSource;

/**
 * Create a boosted query from the input value.  The main value is the query to be boosted.
 * <br>Other parameters: <code>b</code>, the function query to use as the boost.
 * <p>Example: <code>{!boost b=log(popularity)}foo</code> creates a query "foo"
 * which is boosted (scores are multiplied) by the function query <code>log(popularity)</code>.
 * The query to be boosted may be of any type.
 *
 * <p>Example: <code>{!boost b=recip(ms(NOW,mydatefield),3.16e-11,1,1)}foo</code> creates a query "foo"
 * which is boosted by the date boosting function referenced in {@link org.apache.solr.search.function.ReciprocalFloatFunction}
 */
public class BoostQParserPlugin extends QParserPlugin {
  public static String NAME = "boost";
  public static String BOOSTFUNC = "b";

  public void init(NamedList args) {
  }

  public QParser createParser(String qstr, SolrParams localParams, SolrParams params, SolrQueryRequest req) {
    return new QParser(qstr, localParams, params, req) {
      QParser baseParser;
      ValueSource vs;
      String b;

      public Query parse() throws ParseException {
        b = localParams.get(BOOSTFUNC);
        baseParser = subQuery(localParams.get(QueryParsing.V), null);
        Query q = baseParser.parse();

        if (b == null) return q;
        Query bq = subQuery(b, FunctionQParserPlugin.NAME).parse();
        if (bq instanceof FunctionQuery) {
          vs = ((FunctionQuery)bq).getValueSource();
        } else {
          vs = new QueryValueSource(bq, 0.0f);
        }
        return new BoostedQuery(q, vs);
      }


      public String[] getDefaultHighlightFields() {
        return baseParser.getDefaultHighlightFields();
      }
                                           
      public Query getHighlightQuery() throws ParseException {
        return baseParser.getHighlightQuery();
      }

      public void addDebugInfo(NamedList<Object> debugInfo) {
        // encapsulate base debug info in a sub-list?
        baseParser.addDebugInfo(debugInfo);
        debugInfo.add("boost_str",b);
        debugInfo.add("boost_parsed",vs);
      }
    };
  }

}

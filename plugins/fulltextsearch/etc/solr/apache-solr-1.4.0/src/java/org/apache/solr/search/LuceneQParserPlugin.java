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
import org.apache.lucene.queryParser.QueryParser;
import org.apache.lucene.search.Query;
import org.apache.lucene.search.Sort;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.request.SolrQueryRequest;

import java.util.List;
/**
 * Parse Solr's variant on the Lucene QueryParser syntax.
 * <br>Other parameters:<ul>
 * <li>q.op - the default operator "OR" or "AND"</li>
 * <li>df - the default field name</li>
 * </ul>
 * <br>Example: <code>{!lucene q.op=AND df=text sort='price asc'}myfield:foo +bar -baz</code>
 */
public class LuceneQParserPlugin extends QParserPlugin {
  public static String NAME = "lucene";

  public void init(NamedList args) {
  }

  public QParser createParser(String qstr, SolrParams localParams, SolrParams params, SolrQueryRequest req) {
    return new LuceneQParser(qstr, localParams, params, req);
  }
}

class LuceneQParser extends QParser {
  String sortStr;
  SolrQueryParser lparser;

  public LuceneQParser(String qstr, SolrParams localParams, SolrParams params, SolrQueryRequest req) {
    super(qstr, localParams, params, req);
  }


  public Query parse() throws ParseException {
    String qstr = getString();

    String defaultField = getParam(CommonParams.DF);
    if (defaultField==null) {
      defaultField = getReq().getSchema().getDefaultSearchFieldName();
    }
    lparser = new SolrQueryParser(this, defaultField);

    // these could either be checked & set here, or in the SolrQueryParser constructor
    String opParam = getParam(QueryParsing.OP);
    if (opParam != null) {
      lparser.setDefaultOperator("AND".equals(opParam) ? QueryParser.Operator.AND : QueryParser.Operator.OR);
    } else {
      // try to get default operator from schema
      QueryParser.Operator operator = getReq().getSchema().getSolrQueryParser(null).getDefaultOperator();
      lparser.setDefaultOperator(null == operator ? QueryParser.Operator.OR : operator);
    }

    return lparser.parse(qstr);
  }


  public String[] getDefaultHighlightFields() {
    return new String[]{lparser.getField()};
  }
  
}


class OldLuceneQParser extends LuceneQParser {
  String sortStr;

  public OldLuceneQParser(String qstr, SolrParams localParams, SolrParams params, SolrQueryRequest req) {
    super(qstr, localParams, params, req);
  }

  public Query parse() throws ParseException {
    // handle legacy "query;sort" syntax
    if (getLocalParams() == null) {
      String qstr = getString();
      sortStr = getParams().get(CommonParams.SORT);
      if (sortStr == null) {
        // sort may be legacy form, included in the query string
        List<String> commands = StrUtils.splitSmart(qstr,';');
        if (commands.size() == 2) {
          qstr = commands.get(0);
          sortStr = commands.get(1);
        } else if (commands.size() == 1) {
          // This is need to support the case where someone sends: "q=query;"
          qstr = commands.get(0);
        }
        else if (commands.size() > 2) {
          throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, "If you want to use multiple ';' in the query, use the 'sort' param.");
        }
      }
      setString(qstr);
    }

    return super.parse();
  }

  @Override
  public SortSpec getSort(boolean useGlobal) throws ParseException {
    SortSpec sort = super.getSort(useGlobal);
    if (sortStr != null && sortStr.length()>0 && sort.getSort()==null) {
      Sort oldSort = QueryParsing.parseSort(sortStr, getReq().getSchema());
      if( oldSort != null ) {
        sort.sort = oldSort;
      }
    }
    return sort;
  }

}


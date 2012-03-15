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

package org.apache.solr.tst;

import org.apache.lucene.search.*;
import org.apache.lucene.document.Document;

import java.util.List;
import java.io.IOException;
import java.net.URL;

import org.apache.solr.search.DocSlice;
import org.apache.solr.search.QueryParsing;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.core.SolrCore;
import org.apache.solr.request.SolrRequestHandler;
import org.apache.solr.request.SolrQueryResponse;
import org.apache.solr.request.SolrQueryRequest;

/**
 * @version $Id: OldRequestHandler.java 696539 2008-09-18 02:16:26Z ryan $
 * 
 * @deprecated Test against the real request handlers instead.
 */
@Deprecated
public class OldRequestHandler implements SolrRequestHandler {
  long numRequests;
  long numErrors;
  
  public void init(NamedList args) {
    SolrCore.log.info( "Unused request handler arguments:" + args);
  }


  public void handleRequest(SolrQueryRequest req, SolrQueryResponse rsp) {
    numRequests++;

    Query query = null;
    Filter filter = null;

    List<String> commands = StrUtils.splitSmart(req.getQueryString(),';');

    String qs = commands.size() >= 1 ? commands.get(0) : "";
    query = QueryParsing.parseQuery(qs, req.getSchema());

    // If the first non-query, non-filter command is a simple sort on an indexed field, then
    // we can use the Lucene sort ability.
    Sort sort = null;
    if (commands.size() >= 2) {
      sort = QueryParsing.parseSort(commands.get(1), req.getSchema());
    }

    Hits hits=null;

    try {
      hits = req.getSearcher().search(query,filter,sort);

      int numHits = hits.length();
      int startRow = Math.min(numHits, req.getStart());
      int endRow = Math.min(numHits,req.getStart()+req.getLimit());
      int numRows = endRow-startRow;

      int[] ids = new int[numRows];
      Document[] data = new Document[numRows];
      for (int i=startRow; i<endRow; i++) {
        ids[i] = hits.id(i);
        data[i] = hits.doc(i);
      }

      rsp.add(null, new DocSlice(0,numRows,ids,null,numHits,0.0f));

      /***********************
      rsp.setResults(new DocSlice(0,numRows,ids,null,numHits));

      // Setting the actual document objects is optional
      rsp.setResults(data);
      ************************/
    } catch (IOException e) {
      rsp.setException(e);
      numErrors++;
      return;
    }

  }


  public String getName() {
    return OldRequestHandler.class.getName();
  }

  public String getVersion() {
    return SolrCore.version;
  }

  public String getDescription() {
    return "The original Hits based request handler";
  }

  public Category getCategory() {
    return Category.QUERYHANDLER;
  }

  public String getSourceId() {
    return "$Id: OldRequestHandler.java 696539 2008-09-18 02:16:26Z ryan $";
  }

  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/tst/OldRequestHandler.java $";
  }

  public URL[] getDocs() {
    return null;
  }

  public NamedList getStatistics() {
    NamedList lst = new NamedList();
    lst.add("requests", numRequests);
    lst.add("errors", numErrors);
    return lst;
  }


}

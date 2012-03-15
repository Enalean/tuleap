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

import org.apache.lucene.search.Query;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.params.CommonParams;
import org.apache.solr.common.params.HighlightParams;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.highlight.SolrHighlighter;
import org.apache.solr.request.SolrQueryRequest;

import java.io.IOException;
import java.net.URL;
import java.util.Arrays;
import java.util.Map;

/**
 * TODO!
 *
 * @version $Id: HighlightComponent.java 821556 2009-10-04 16:26:06Z markrmiller $
 * @since solr 1.3
 */
public class HighlightComponent extends SearchComponent 
{
  public static final String COMPONENT_NAME = "highlight";
  
  @Override
  public void prepare(ResponseBuilder rb) throws IOException
  {
    SolrHighlighter highlighter = rb.req.getCore().getHighlighter();
    rb.doHighlights = highlighter.isHighlightingEnabled(rb.req.getParams());
  }
  
  @Override
  public void process(ResponseBuilder rb) throws IOException {
    SolrQueryRequest req = rb.req;
    if (rb.doHighlights) {
      SolrHighlighter highlighter = req.getCore().getHighlighter();
      SolrParams params = req.getParams();

      String[] defaultHighlightFields;  //TODO: get from builder by default?

      if (rb.getQparser() != null) {
        defaultHighlightFields = rb.getQparser().getDefaultHighlightFields();
      } else {
        defaultHighlightFields = params.getParams(CommonParams.DF);
      }
      
      Query highlightQuery = rb.getHighlightQuery();
      if(highlightQuery==null) {
        if (rb.getQparser() != null) {
          try {
            highlightQuery = rb.getQparser().getHighlightQuery();
            rb.setHighlightQuery( highlightQuery );
          } catch (Exception e) {
            throw new SolrException(SolrException.ErrorCode.BAD_REQUEST, e);
          }
        } else {
          highlightQuery = rb.getQuery();
          rb.setHighlightQuery( highlightQuery );
        }
      }
      
      if(highlightQuery != null) {
        boolean rewrite = !(Boolean.valueOf(req.getParams().get(HighlightParams.USE_PHRASE_HIGHLIGHTER, "true")) && Boolean.valueOf(req.getParams().get(HighlightParams.HIGHLIGHT_MULTI_TERM, "true")));
        highlightQuery = rewrite ?  highlightQuery.rewrite(req.getSearcher().getReader()) : highlightQuery;
      }
      
      // No highlighting if there is no query -- consider q.alt="*:*
      if( highlightQuery != null ) {
        NamedList sumData = highlighter.doHighlighting(
                rb.getResults().docList,
                highlightQuery,
                req, defaultHighlightFields );
        
        if(sumData != null) {
          // TODO ???? add this directly to the response?
          rb.rsp.add("highlighting", sumData);
        }
      }
    }
  }

  public void modifyRequest(ResponseBuilder rb, SearchComponent who, ShardRequest sreq) {
    if (!rb.doHighlights) return;

    // Turn on highlighting only only when retrieving fields
    if ((sreq.purpose & ShardRequest.PURPOSE_GET_FIELDS) != 0) {
        sreq.purpose |= ShardRequest.PURPOSE_GET_HIGHLIGHTS;
        // should already be true...
        sreq.params.set(HighlightParams.HIGHLIGHT, "true");      
    } else {
      sreq.params.set(HighlightParams.HIGHLIGHT, "false");      
    }
  }

  @Override
  public void handleResponses(ResponseBuilder rb, ShardRequest sreq) {
  }

  @Override
  public void finishStage(ResponseBuilder rb) {
    if (rb.doHighlights && rb.stage == ResponseBuilder.STAGE_GET_FIELDS) {

      Map.Entry<String, Object>[] arr = new NamedList.NamedListEntry[rb.resultIds.size()];

      // TODO: make a generic routine to do automatic merging of id keyed data
      for (ShardRequest sreq : rb.finished) {
        if ((sreq.purpose & ShardRequest.PURPOSE_GET_HIGHLIGHTS) == 0) continue;
        for (ShardResponse srsp : sreq.responses) {
          NamedList hl = (NamedList)srsp.getSolrResponse().getResponse().get("highlighting");
          for (int i=0; i<hl.size(); i++) {
            String id = hl.getName(i);
            ShardDoc sdoc = rb.resultIds.get(id);
            int idx = sdoc.positionInResponse;
            arr[idx] = new NamedList.NamedListEntry<Object>(id, hl.getVal(i));
          }
        }
      }

      // remove nulls in case not all docs were able to be retrieved
      rb.rsp.add("highlighting", removeNulls(new SimpleOrderedMap(arr)));      
    }
  }


  static NamedList removeNulls(NamedList nl) {
    for (int i=0; i<nl.size(); i++) {
      if (nl.getName(i)==null) {
        NamedList newList = nl instanceof SimpleOrderedMap ? new SimpleOrderedMap() : new NamedList();
        for (int j=0; j<nl.size(); j++) {
          String n = nl.getName(j);
          if (n != null) {
            newList.add(n, nl.getVal(j));
          }
        }
        return newList;
      }
    }
    return nl;
  }

  ////////////////////////////////////////////
  ///  SolrInfoMBean
  ////////////////////////////////////////////
  
  @Override
  public String getDescription() {
    return "Highlighting";
  }
  
  @Override
  public String getVersion() {
    return "$Revision: 821556 $";
  }
  
  @Override
  public String getSourceId() {
    return "$Id: HighlightComponent.java 821556 2009-10-04 16:26:06Z markrmiller $";
  }
  
  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/component/HighlightComponent.java $";
  }
  
  @Override
  public URL[] getDocs() {
    return null;
  }
}

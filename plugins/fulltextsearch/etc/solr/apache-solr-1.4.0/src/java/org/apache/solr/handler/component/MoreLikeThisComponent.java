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

import java.io.IOException;
import java.net.URL;

import org.apache.solr.common.params.MoreLikeThisParams;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.handler.MoreLikeThisHandler;
import org.apache.solr.search.DocList;
import org.apache.solr.search.SolrIndexSearcher;

/**
 * TODO!
 * 
 * @version $Id: MoreLikeThisComponent.java 631357 2008-02-26 19:47:07Z yonik $
 * @since solr 1.3
 */
public class MoreLikeThisComponent extends SearchComponent
{
  public static final String COMPONENT_NAME = "mlt";
  
  @Override
  public void prepare(ResponseBuilder rb) throws IOException
  {
    
  }

  @Override
  public void process(ResponseBuilder rb) throws IOException
  {
    SolrParams p = rb.req.getParams();
    if( p.getBool( MoreLikeThisParams.MLT, false ) ) {
      SolrIndexSearcher searcher = rb.req.getSearcher();
      
      MoreLikeThisHandler.MoreLikeThisHelper mlt 
        = new MoreLikeThisHandler.MoreLikeThisHelper( p, searcher );
      
      int mltcount = p.getInt( MoreLikeThisParams.DOC_COUNT, 5 );
      NamedList<DocList> sim = mlt.getMoreLikeThese(
          rb.getResults().docList, mltcount, rb.getFieldFlags() );

      // TODO ???? add this directly to the response?
      rb.rsp.add( "moreLikeThis", sim );
    }
  }

  /////////////////////////////////////////////
  ///  SolrInfoMBean
  ////////////////////////////////////////////

  @Override
  public String getDescription() {
    return "More Like This";
  }

  @Override
  public String getVersion() {
    return "$Revision: 631357 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: MoreLikeThisComponent.java 631357 2008-02-26 19:47:07Z yonik $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/component/MoreLikeThisComponent.java $";
  }

  @Override
  public URL[] getDocs() {
    return null;
  }
}

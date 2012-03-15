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

package org.apache.solr.client.solrj.request;

import java.io.IOException;
import java.util.Collection;

import org.apache.solr.client.solrj.SolrServer;
import org.apache.solr.client.solrj.SolrServerException;
import org.apache.solr.client.solrj.SolrRequest;
import org.apache.solr.client.solrj.response.SolrPingResponse;
import org.apache.solr.common.params.ModifiableSolrParams;
import org.apache.solr.common.util.ContentStream;

/**
 * 
 * @version $Id: SolrPing.java 679649 2008-07-25 03:18:24Z ryan $
 * @since solr 1.3
 */
public class SolrPing extends SolrRequest
{
  private ModifiableSolrParams params;
  
  public SolrPing()
  {
    super( METHOD.GET, "/admin/ping" );
    params = new ModifiableSolrParams();
  }

  @Override
  public Collection<ContentStream> getContentStreams() {
    return null;
  }

  @Override
  public ModifiableSolrParams getParams() {
    return params;
  }

  @Override
  public SolrPingResponse process( SolrServer server ) throws SolrServerException, IOException 
  {
    long startTime = System.currentTimeMillis();
    SolrPingResponse res = new SolrPingResponse();
    res.setResponse( server.request( this ) );
    res.setElapsedTime( System.currentTimeMillis()-startTime );
    return res;
  }
}

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

package org.apache.solr.handler;

import java.io.IOException;
import java.util.ArrayList;

import org.apache.commons.io.IOUtils;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.request.SolrQueryRequest;
import org.apache.solr.request.SolrQueryResponse;

public class DumpRequestHandler extends RequestHandlerBase
{
  @Override
  public void handleRequestBody(SolrQueryRequest req, SolrQueryResponse rsp) throws IOException 
  {
    // Show params
    rsp.add( "params", req.getParams().toNamedList() );
        
    // Write the streams...
    if( req.getContentStreams() != null ) {
      ArrayList streams = new ArrayList();
      // Cycle through each stream
      for( ContentStream content : req.getContentStreams() ) {
        NamedList<Object> stream = new SimpleOrderedMap<Object>();
        stream.add( "name", content.getName() );
        stream.add( "sourceInfo", content.getSourceInfo() );
        stream.add( "size", content.getSize() );
        stream.add( "contentType", content.getContentType() );
        stream.add( "stream", IOUtils.toString( content.getStream() ) );
        streams.add( stream );
      }
      rsp.add( "streams", streams );
    }

    rsp.add("context", req.getContext());
  }

  //////////////////////// SolrInfoMBeans methods //////////////////////

  @Override
  public String getDescription() {
    return "Dump handler (debug)";
  }

  @Override
  public String getVersion() {
      return "$Revision: 707990 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: DumpRequestHandler.java 707990 2008-10-26 13:23:43Z ehatcher $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/DumpRequestHandler.java $";
  }
}

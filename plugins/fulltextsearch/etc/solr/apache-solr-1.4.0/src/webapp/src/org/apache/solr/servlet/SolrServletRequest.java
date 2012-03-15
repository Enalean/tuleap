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

package org.apache.solr.servlet;

import org.apache.solr.request.SolrQueryRequestBase;
import org.apache.solr.request.ServletSolrParams;
import org.apache.solr.core.SolrCore;

import javax.servlet.http.HttpServletRequest;

/**
 * @version $Id$
 */
class SolrServletRequest extends SolrQueryRequestBase {
  public SolrServletRequest(SolrCore core, HttpServletRequest req) {
    super(core, new ServletSolrParams(req));
  }
}

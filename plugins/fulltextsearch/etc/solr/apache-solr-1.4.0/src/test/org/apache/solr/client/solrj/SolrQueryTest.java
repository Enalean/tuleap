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

package org.apache.solr.client.solrj;

import org.apache.solr.common.params.FacetParams;

import junit.framework.Assert;
import junit.framework.TestCase;

/**
 * 
 * @version $Id: SolrQueryTest.java 749056 2009-03-01 16:29:31Z yonik $
 * @since solr 1.3
 */
public class SolrQueryTest extends TestCase {
  
  public void testSolrQueryMethods() {
    SolrQuery q = new SolrQuery("dog");
    boolean b = false;
    
    q.setFacetLimit(10);
    q.addFacetField("price");
    q.addFacetField("state");
    Assert.assertEquals(q.getFacetFields().length, 2);
    q.addFacetQuery("instock:true");
    q.addFacetQuery("instock:false");
    q.addFacetQuery("a:b");
    Assert.assertEquals(q.getFacetQuery().length, 3);
    
    b = q.removeFacetField("price");
    Assert.assertEquals(b, true);
    b = q.removeFacetField("price2");
    Assert.assertEquals(b, false);
    b = q.removeFacetField("state");
    Assert.assertEquals(b, true);
    Assert.assertEquals(null, q.getFacetFields());
    
    b = q.removeFacetQuery("instock:true");
    Assert.assertEquals(b, true);
    b = q.removeFacetQuery("instock:false");
    b = q.removeFacetQuery("a:c");
    Assert.assertEquals(b, false);
    b = q.removeFacetQuery("a:b");
    Assert.assertEquals(null, q.getFacetQuery());   
    
    q.addSortField("price", SolrQuery.ORDER.asc);
    q.addSortField("date", SolrQuery.ORDER.desc);
    q.addSortField("qty", SolrQuery.ORDER.desc);
    q.removeSortField("date", SolrQuery.ORDER.desc);
    Assert.assertEquals(2, q.getSortFields().length);
    q.removeSortField("price", SolrQuery.ORDER.asc);
    q.removeSortField("qty", SolrQuery.ORDER.desc);
    Assert.assertEquals(null, q.getSortFields());
    
    q.addHighlightField("hl1");
    q.addHighlightField("hl2");
    q.setHighlightSnippets(2);
    Assert.assertEquals(2, q.getHighlightFields().length);
    Assert.assertEquals(100, q.getHighlightFragsize());
    Assert.assertEquals(q.getHighlightSnippets(), 2);
    q.removeHighlightField("hl1");
    q.removeHighlightField("hl3");
    Assert.assertEquals(1, q.getHighlightFields().length);
    q.removeHighlightField("hl2");
    Assert.assertEquals(null, q.getHighlightFields());
    
    // check to see that the removes are properly clearing the cgi params
    Assert.assertEquals(q.toString(), "q=dog");

    //Add time allowed param
    q.setTimeAllowed(1000);
    Assert.assertEquals((Integer)1000, q.getTimeAllowed() );
    //Adding a null should remove it
    q.setTimeAllowed(null);
    Assert.assertEquals(null, q.getTimeAllowed() ); 
    
    System.out.println(q);
  }
  
  public void testFacetSort() {
    SolrQuery q = new SolrQuery("dog");
    assertEquals("count", q.getFacetSortString());
    q.setFacetSort("index");
    assertEquals("index", q.getFacetSortString());
  }

  public void testFacetSortLegacy() {
    SolrQuery q = new SolrQuery("dog");
    assertTrue("expected default value to be true", q.getFacetSort());
    q.setFacetSort(false);
    assertFalse("expected set value to be false", q.getFacetSort());
  }

  public void testSettersGetters() {
      SolrQuery q = new SolrQuery("foo");
      assertEquals(10, q.setFacetLimit(10).getFacetLimit());
      assertEquals(10, q.setFacetMinCount(10).getFacetMinCount());
      assertEquals("index", q.setFacetSort("index").getFacetSortString());
      assertEquals(10, q.setHighlightSnippets(10).getHighlightSnippets());
      assertEquals(10, q.setHighlightFragsize(10).getHighlightFragsize());
      assertEquals(true, q.setHighlightRequireFieldMatch(true).getHighlightRequireFieldMatch());
      assertEquals("foo", q.setHighlightSimplePre("foo").getHighlightSimplePre());
      assertEquals("foo", q.setHighlightSimplePost("foo").getHighlightSimplePost());
      assertEquals(true, q.setHighlight(true).getHighlight());
      assertEquals("foo", q.setQuery("foo").getQuery());
      assertEquals(10, q.setRows(10).getRows().intValue());
      assertEquals(10, q.setStart(10).getStart().intValue());
      assertEquals("foo", q.setQueryType("foo").getQueryType());
      assertEquals(10, q.setTimeAllowed(10).getTimeAllowed().intValue());
      
      // non-standard
      assertEquals("foo", q.setFacetPrefix("foo").get( FacetParams.FACET_PREFIX, null ) );
      assertEquals("foo", q.setFacetPrefix("a", "foo").getFieldParam( "a", FacetParams.FACET_PREFIX, null ) );

      assertEquals( Boolean.TRUE, q.setMissing(Boolean.TRUE.toString()).getBool( FacetParams.FACET_MISSING ) );
      assertEquals( Boolean.FALSE, q.setFacetMissing( Boolean.FALSE ).getBool( FacetParams.FACET_MISSING ) );      
      assertEquals( "true", q.setParam( "xxx", true ).getParams( "xxx" )[0] );
  }
  
  public void testOrder() {
    assertEquals( SolrQuery.ORDER.asc, SolrQuery.ORDER.desc.reverse() );
    assertEquals( SolrQuery.ORDER.desc, SolrQuery.ORDER.asc.reverse() );
  }
}

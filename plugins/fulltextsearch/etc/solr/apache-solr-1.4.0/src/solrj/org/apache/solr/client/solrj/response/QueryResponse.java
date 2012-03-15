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

package org.apache.solr.client.solrj.response;

import java.util.ArrayList;
import java.util.Date;
import java.util.HashMap;
import java.util.LinkedHashMap;
import java.util.List;
import java.util.Map;

import org.apache.solr.common.SolrDocumentList;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.client.solrj.SolrServer;
import org.apache.solr.client.solrj.beans.DocumentObjectBinder;

/**
 * 
 * @version $Id: QueryResponse.java 816372 2009-09-17 20:28:53Z yonik $
 * @since solr 1.3
 */
@SuppressWarnings("unchecked")
public class QueryResponse extends SolrResponseBase 
{
  // Direct pointers to known types
  private NamedList<Object> _header = null;
  private SolrDocumentList _results = null;
  private NamedList<ArrayList> _sortvalues = null;
  private NamedList<Object> _facetInfo = null;
  private NamedList<Object> _debugInfo = null;
  private NamedList<Object> _highlightingInfo = null;
  private NamedList<Object> _spellInfo = null;
  private NamedList<Object> _statsInfo = null;

  // Facet stuff
  private Map<String,Integer> _facetQuery = null;
  private List<FacetField> _facetFields = null;
  private List<FacetField> _limitingFacets = null;
  private List<FacetField> _facetDates = null;

  // Highlight Info
  private Map<String,Map<String,List<String>>> _highlighting = null;

  // SpellCheck Response
  private SpellCheckResponse _spellResponse = null;

  // Field stats Response
  private Map<String,FieldStatsInfo> _fieldStatsInfo = null;
  
  // Debug Info
  private Map<String,Object> _debugMap = null;
  private Map<String,String> _explainMap = null;

  // utility variable used for automatic binding -- it should not be serialized
  private transient final SolrServer solrServer;
  
  public QueryResponse(){
    solrServer = null;
  }
  
  /**
   * Utility constructor to set the solrServer and namedList
   */
  public QueryResponse( NamedList<Object> res , SolrServer solrServer){
    this.setResponse( res );
    this.solrServer = solrServer;
  }

  @Override
  public void setResponse( NamedList<Object> res )
  {
    super.setResponse( res );
    
    // Look for known things
    for( int i=0; i<res.size(); i++ ) {
      String n = res.getName( i );
      if( "responseHeader".equals( n ) ) {
        _header = (NamedList<Object>) res.getVal( i );
      }
      else if( "response".equals( n ) ) {
        _results = (SolrDocumentList) res.getVal( i );
      }
      else if( "sort_values".equals( n ) ) {
        _sortvalues = (NamedList<ArrayList>) res.getVal( i );
      }
      else if( "facet_counts".equals( n ) ) {
        _facetInfo = (NamedList<Object>) res.getVal( i );
        extractFacetInfo( _facetInfo );
      }
      else if( "debug".equals( n ) ) {
        _debugInfo = (NamedList<Object>) res.getVal( i );
        extractDebugInfo( _debugInfo );
      }
      else if( "highlighting".equals( n ) ) {
        _highlightingInfo = (NamedList<Object>) res.getVal( i );
        extractHighlightingInfo( _highlightingInfo );
      }
      else if ( "spellcheck".equals( n ) )  {
        _spellInfo = (NamedList<Object>) res.getVal( i );
        extractSpellCheckInfo( _spellInfo );
      }
      else if ( "stats".equals( n ) )  {
        _statsInfo = (NamedList<Object>) res.getVal( i );
        extractStatsInfo( _statsInfo );
      }
    }
  }

  private void extractSpellCheckInfo(NamedList<Object> spellInfo) {
    _spellResponse = new SpellCheckResponse(spellInfo);
  }

  private void extractStatsInfo(NamedList<Object> info) {
    if( info != null ) {
      _fieldStatsInfo = new HashMap<String, FieldStatsInfo>();
      NamedList<NamedList<Object>> ff = (NamedList<NamedList<Object>>) info.get( "stats_fields" );
      if( ff != null ) {
        for( Map.Entry<String,NamedList<Object>> entry : ff ) {
          NamedList<Object> v = entry.getValue();
          if( v != null ) {
            _fieldStatsInfo.put( entry.getKey(), 
                new FieldStatsInfo( v, entry.getKey() ) );
          }
        }
      }
    }
  }

  private void extractDebugInfo( NamedList<Object> debug )
  {
    _debugMap = new LinkedHashMap<String, Object>(); // keep the order
    for( Map.Entry<String, Object> info : debug ) {
      _debugMap.put( info.getKey(), info.getValue() );
    }

    // Parse out interesting bits from the debug info
    _explainMap = new HashMap<String, String>();
    NamedList<String> explain = (NamedList<String>)_debugMap.get( "explain" );
    if( explain != null ) {
      for( Map.Entry<String, String> info : explain ) {
        String key = info.getKey();
        _explainMap.put( key, info.getValue() );
      }
    }
  }

  private void extractHighlightingInfo( NamedList<Object> info )
  {
    _highlighting = new HashMap<String,Map<String,List<String>>>();
    for( Map.Entry<String, Object> doc : info ) {
      Map<String,List<String>> fieldMap = new HashMap<String, List<String>>();
      _highlighting.put( doc.getKey(), fieldMap );
      
      NamedList<List<String>> fnl = (NamedList<List<String>>)doc.getValue();
      for( Map.Entry<String, List<String>> field : fnl ) {
        fieldMap.put( field.getKey(), field.getValue() );
      }
    }
  }

  private void extractFacetInfo( NamedList<Object> info )
  {
    // Parse the queries
    _facetQuery = new HashMap<String, Integer>();
    NamedList<Integer> fq = (NamedList<Integer>) info.get( "facet_queries" );
    if (fq != null) {
      for( Map.Entry<String, Integer> entry : fq ) {
        _facetQuery.put( entry.getKey(), entry.getValue() );
      }
    }
    
    // Parse the facet info into fields
    // TODO?? The list could be <int> or <long>?  If always <long> then we can switch to <Long>
    NamedList<NamedList<Number>> ff = (NamedList<NamedList<Number>>) info.get( "facet_fields" );
    if( ff != null ) {
      _facetFields = new ArrayList<FacetField>( ff.size() );
      _limitingFacets = new ArrayList<FacetField>( ff.size() );
      
      long minsize = _results.getNumFound();
      for( Map.Entry<String,NamedList<Number>> facet : ff ) {
        FacetField f = new FacetField( facet.getKey() );
        for( Map.Entry<String, Number> entry : facet.getValue() ) {
          f.add( entry.getKey(), entry.getValue().longValue() );
        }
        
        _facetFields.add( f );
        FacetField nl = f.getLimitingFields( minsize );
        if( nl.getValueCount() > 0 ) {
          _limitingFacets.add( nl );
        }
      }
    }
    
    //Parse date facets
    NamedList<NamedList<Object>> df = (NamedList<NamedList<Object>>) info.get("facet_dates");
    if (df != null) {
      // System.out.println(df);
      _facetDates = new ArrayList<FacetField>( df.size() );
      for (Map.Entry<String, NamedList<Object>> facet : df) {
        // System.out.println("Key: " + facet.getKey() + " Value: " + facet.getValue());
        NamedList<Object> values = facet.getValue();
        String gap = (String) values.get("gap");
        Date end = (Date) values.get("end");
        FacetField f = new FacetField(facet.getKey(), gap, end);
        
        for (Map.Entry<String, Object> entry : values)   {
          try {
            f.add(entry.getKey(), Long.parseLong(entry.getValue().toString()));
          } catch (NumberFormatException e) {
            //Ignore for non-number responses which are already handled above
          }
        }
        
        _facetDates.add(f);
      }
    }
  }

  //------------------------------------------------------
  //------------------------------------------------------

  /**
   * Remove the field facet info
   */
  public void removeFacets() {
    _facetFields = new ArrayList<FacetField>();
  }
  
  //------------------------------------------------------
  //------------------------------------------------------

  public NamedList<Object> getHeader() {
    return _header;
  }

  public SolrDocumentList getResults() {
    return _results;
  }
 
  public NamedList<ArrayList> getSortValues(){
    return _sortvalues;
  }

  public Map<String, Object> getDebugMap() {
    return _debugMap;
  }

  public Map<String, String> getExplainMap() {
    return _explainMap;
  }

  public Map<String,Integer> getFacetQuery() {
    return _facetQuery;
  }

  public Map<String, Map<String, List<String>>> getHighlighting() {
    return _highlighting;
  }

  public SpellCheckResponse getSpellCheckResponse() {
    return _spellResponse;
  }

  /**
   * See also: {@link #getLimitingFacets()}
   */
  public List<FacetField> getFacetFields() {
    return _facetFields;
  }
  
  public List<FacetField> getFacetDates()   {
    return _facetDates;
  }
  
  /** get 
   * 
   * @param name the name of the 
   * @return the FacetField by name or null if it does not exist
   */
  public FacetField getFacetField(String name) {
    if (_facetFields==null) return null;
    for (FacetField f : _facetFields) {
      if (f.getName().equals(name)) return f;
    }
    return null;
  }
  
  public FacetField getFacetDate(String name)   {
    if (_facetDates == null)
      return null;
    for (FacetField f : _facetDates)
      if (f.getName().equals(name))
        return f;
    return null;
  }
  
  /**
   * @return a list of FacetFields where the count is less then
   * then #getResults() {@link SolrDocumentList#getNumFound()}
   * 
   * If you want all results exactly as returned by solr, use:
   * {@link #getFacetFields()}
   */
  public List<FacetField> getLimitingFacets() {
    return _limitingFacets;
  }
  
  public <T> List<T> getBeans(Class<T> type){
    return solrServer == null ? 
      new DocumentObjectBinder().getBeans(type,_results):
      solrServer.getBinder().getBeans(type, _results);
  }

  public Map<String, FieldStatsInfo> getFieldStatsInfo() {
    return _fieldStatsInfo;
  }
}




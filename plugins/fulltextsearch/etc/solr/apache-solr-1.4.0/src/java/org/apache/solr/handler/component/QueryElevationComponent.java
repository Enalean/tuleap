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

import java.io.File;
import java.io.IOException;
import java.io.InputStream;
import java.io.StringReader;
import java.net.MalformedURLException;
import java.net.URL;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.HashMap;
import java.util.List;
import java.util.Map;
import java.util.WeakHashMap;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

import javax.xml.xpath.XPath;
import javax.xml.xpath.XPathConstants;
import javax.xml.xpath.XPathExpressionException;
import javax.xml.xpath.XPathFactory;

import org.apache.lucene.analysis.Analyzer;
import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.Term;
import org.apache.lucene.search.*;
import org.apache.lucene.util.StringHelper;
import org.apache.solr.common.SolrException;
import org.apache.solr.common.params.SolrParams;
import org.apache.solr.common.util.DOMUtil;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.common.util.SimpleOrderedMap;
import org.apache.solr.core.Config;
import org.apache.solr.core.SolrCore;
import org.apache.solr.schema.FieldType;
import org.apache.solr.schema.SchemaField;
import org.apache.solr.search.SortSpec;
import org.apache.solr.search.SolrIndexSearcher;
import org.apache.solr.util.VersionedFile;
import org.apache.solr.util.RefCounted;
import org.apache.solr.util.plugin.SolrCoreAware;
import org.apache.solr.request.SolrQueryRequest;
import org.w3c.dom.Node;
import org.w3c.dom.NodeList;

/**
 * A component to elevate some documents to the top of the result set.
 * 
 * @version $Id: QueryElevationComponent.java 819234 2009-09-26 23:46:44Z markrmiller $
 * @since solr 1.3
 */
public class QueryElevationComponent extends SearchComponent implements SolrCoreAware
{
  private static Logger log = LoggerFactory.getLogger(QueryElevationComponent.class);
  
  // Constants used in solrconfig.xml
  static final String FIELD_TYPE = "queryFieldType";
  static final String CONFIG_FILE = "config-file";
  static final String FORCE_ELEVATION = "forceElevation";
  static final String EXCLUDE = "exclude";
  
  // Runtime param -- should be in common?
  static final String ENABLE = "enableElevation";
    
  private SolrParams initArgs = null;
  private Analyzer analyzer = null;
  private String idField = null;
  boolean forceElevation = false;
  
  // For each IndexReader, keep a query->elevation map
  // When the configuration is loaded from the data directory.
  // The key is null if loaded from the config directory, and
  // is never re-loaded.
  final Map<IndexReader,Map<String, ElevationObj>> elevationCache = 
    new WeakHashMap<IndexReader, Map<String,ElevationObj>>();

  class ElevationObj {
    final String text;
    final String analyzed;
    final BooleanClause[] exclude;
    final BooleanQuery include;
    final Map<String,Integer> priority;
    
    // use singletons so hashCode/equals on Sort will just work
    final FieldComparatorSource comparatorSource;

    ElevationObj( String qstr, List<String> elevate, List<String> exclude ) throws IOException
    {
      this.text = qstr;
      this.analyzed = getAnalyzedQuery( this.text );
      
      this.include = new BooleanQuery();
      this.include.setBoost( 0 );
      this.priority = new HashMap<String, Integer>();
      int max = elevate.size()+5;
      for( String id : elevate ) {
        TermQuery tq = new TermQuery( new Term( idField, id ) );
        include.add( tq, BooleanClause.Occur.SHOULD );
        this.priority.put( id, max-- );
      }
      
      if( exclude == null || exclude.isEmpty() ) {
        this.exclude = null;
      }
      else {
        this.exclude = new BooleanClause[exclude.size()];
        for( int i=0; i<exclude.size(); i++ ) {
          TermQuery tq = new TermQuery( new Term( idField, exclude.get(i) ) );
          this.exclude[i] = new BooleanClause( tq, BooleanClause.Occur.MUST_NOT );
        }
      }

      this.comparatorSource = new ElevationComparatorSource(priority);
    }
  }
  
  @Override
  public void init( NamedList args )
  {
    this.initArgs = SolrParams.toSolrParams( args );
  }
  
  public void inform(SolrCore core)
  {
    String a = initArgs.get( FIELD_TYPE );
    if( a != null ) {
      FieldType ft = core.getSchema().getFieldTypes().get( a );
      if( ft == null ) {
        throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
            "Unknown FieldType: '"+a+"' used in QueryElevationComponent" );
      }
      analyzer = ft.getQueryAnalyzer();
    }

    SchemaField sf = core.getSchema().getUniqueKeyField();
    if( sf == null ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, 
          "QueryElevationComponent requires the schema to have a uniqueKeyField" );
    }
    idField = StringHelper.intern(sf.getName());
    
    forceElevation = initArgs.getBool( FORCE_ELEVATION, forceElevation );
    try {
      synchronized( elevationCache ) {
        elevationCache.clear();
        String f = initArgs.get( CONFIG_FILE );
        if( f == null ) {
          throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
              "QueryElevationComponent must specify argument: '"+CONFIG_FILE
              +"' -- path to elevate.xml" );
        }
        File fC = new File( core.getResourceLoader().getConfigDir(), f );
        File fD = new File( core.getDataDir(), f );
        if( fC.exists() == fD.exists() ) {
          throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
              "QueryElevationComponent missing config file: '"+f + "\n"
              +"either: "+fC.getAbsolutePath() + " or " + fD.getAbsolutePath() + " must exist, but not both." );
        }
        if( fC.exists() ) {
          log.info( "Loading QueryElevation from: "+fC.getAbsolutePath() );
          Config cfg = new Config( core.getResourceLoader(), f );
          elevationCache.put(null, loadElevationMap( cfg ));
        }
        else {
          // preload the first data
          RefCounted<SolrIndexSearcher> searchHolder = null;
          try {
            searchHolder = core.getNewestSearcher(false);
            IndexReader reader = searchHolder.get().getReader();
            getElevationMap( reader, core );
          } finally {
            if (searchHolder != null) searchHolder.decref();
          }
        }
      }
    }
    catch( Exception ex ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Error initializing QueryElevationComponent.", ex );
    }
  }

  Map<String, ElevationObj> getElevationMap( IndexReader reader, SolrCore core ) throws Exception
  {
    synchronized( elevationCache ) {
      Map<String, ElevationObj> map = elevationCache.get( null );
      if (map != null) return map;

      map = elevationCache.get( reader );
      if( map == null ) {
        String f = initArgs.get( CONFIG_FILE );
        if( f == null ) {
          throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
                  "QueryElevationComponent must specify argument: "+CONFIG_FILE );
        }
        log.info( "Loading QueryElevation from data dir: "+f );

        InputStream is = VersionedFile.getLatestFile( core.getDataDir(), f );
        Config cfg = new Config( core.getResourceLoader(), f, is, null );
        map = loadElevationMap( cfg );
        elevationCache.put( reader, map );
      }
      return map;
    }
  }
  
  private Map<String, ElevationObj> loadElevationMap( Config cfg ) throws IOException
  {
    XPath xpath = XPathFactory.newInstance().newXPath();
    Map<String, ElevationObj> map = new HashMap<String, ElevationObj>();
    NodeList nodes = (NodeList)cfg.evaluate( "elevate/query", XPathConstants.NODESET );
    for (int i=0; i<nodes.getLength(); i++) {
      Node node = nodes.item( i );
      String qstr = DOMUtil.getAttr( node, "text", "missing query 'text'" );
      
      NodeList children = null;
      try {
        children = (NodeList)xpath.evaluate("doc", node, XPathConstants.NODESET);
      } 
      catch (XPathExpressionException e) {
        throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, 
            "query requires '<doc .../>' child" );
      }

      ArrayList<String> include = new ArrayList<String>();
      ArrayList<String> exclude = new ArrayList<String>();
      for (int j=0; j<children.getLength(); j++) {
        Node child = children.item(j);
        String id = DOMUtil.getAttr( child, "id", "missing 'id'" );
        String e = DOMUtil.getAttr( child, EXCLUDE, null );
        if( e != null ) {
          if( Boolean.valueOf( e ) ) {
            exclude.add( id );
            continue;
          }
        }
        include.add( id );
      }
      
      ElevationObj elev = new ElevationObj( qstr, include, exclude );
      if( map.containsKey( elev.analyzed ) ) {
        throw new SolrException( SolrException.ErrorCode.SERVER_ERROR, 
            "Boosting query defined twice for query: '"+elev.text+"' ("+elev.analyzed+"')" );
      }
      map.put( elev.analyzed, elev );
    }
    return map;
  }
  
  /**
   * Helpful for testing without loading config.xml
   * @throws IOException 
   */
  void setTopQueryResults( IndexReader reader, String query, String[] ids, String[] ex ) throws IOException
  {
    if( ids == null ) {
      ids = new String[0];
    }
    if( ex == null ) {
      ex = new String[0];
    }
    
    Map<String,ElevationObj> elev = elevationCache.get( reader );
    if( elev == null ) {
      elev = new HashMap<String, ElevationObj>();
      elevationCache.put( reader, elev );
    }
    ElevationObj obj = new ElevationObj( query, Arrays.asList(ids), Arrays.asList(ex) );
    elev.put( obj.analyzed, obj );
  }
  
  String getAnalyzedQuery( String query ) throws IOException
  {
    if( analyzer == null ) {
      return query;
    }
    StringBuilder norm = new StringBuilder();
    TokenStream tokens = analyzer.reusableTokenStream( "", new StringReader( query ) );
    tokens.reset();
    
    Token token = tokens.next();
    while( token != null ) {
      norm.append( new String(token.termBuffer(), 0, token.termLength()) );
      token = tokens.next();
    }
    return norm.toString();
  }

  //---------------------------------------------------------------------------------
  // SearchComponent
  //---------------------------------------------------------------------------------
  
  @Override
  public void prepare(ResponseBuilder rb) throws IOException
  {
    SolrQueryRequest req = rb.req;
    SolrParams params = req.getParams();
    // A runtime param can skip 
    if( !params.getBool( ENABLE, true ) ) {
      return;
    }

    // A runtime parameter can alter the config value for forceElevation
    boolean force = params.getBool( FORCE_ELEVATION, forceElevation );
    
    Query query = rb.getQuery();
    String qstr = rb.getQueryString();
    if( query == null || qstr == null) {
      return;
    }

    qstr = getAnalyzedQuery(qstr);
    IndexReader reader = req.getSearcher().getReader();
    ElevationObj booster = null;
    try {
      booster = getElevationMap( reader, req.getCore() ).get( qstr );
    }
    catch( Exception ex ) {
      throw new SolrException( SolrException.ErrorCode.SERVER_ERROR,
          "Error loading elevation", ex );      
    }
    
    if( booster != null ) {
      // Change the query to insert forced documents
      BooleanQuery newq = new BooleanQuery( true );
      newq.add( query, BooleanClause.Occur.SHOULD );
      newq.add( booster.include, BooleanClause.Occur.SHOULD );
      if( booster.exclude != null ) {
        for( BooleanClause bq : booster.exclude ) {
          newq.add( bq );
        }
      }
      rb.setQuery( newq );
      
      // if the sort is 'score desc' use a custom sorting method to 
      // insert documents in their proper place 
      SortSpec sortSpec = rb.getSortSpec();
      if( sortSpec.getSort() == null ) {
        sortSpec.setSort( new Sort( new SortField[] {
            new SortField(idField, booster.comparatorSource, false ),
            new SortField(null, SortField.SCORE, false)
        }));
      }
      else {
        // Check if the sort is based on score
        boolean modify = false;
        SortField[] current = sortSpec.getSort().getSort();
        ArrayList<SortField> sorts = new ArrayList<SortField>( current.length + 1 );
        // Perhaps force it to always sort by score
        if( force && current[0].getType() != SortField.SCORE ) {
          sorts.add( new SortField(idField, booster.comparatorSource, false ) );
          modify = true;
        }
        for( SortField sf : current ) {
          if( sf.getType() == SortField.SCORE ) {
            sorts.add( new SortField(idField, booster.comparatorSource, sf.getReverse() ) );
            modify = true;
          }
          sorts.add( sf );
        }
        if( modify ) {
          sortSpec.setSort( new Sort( sorts.toArray( new SortField[sorts.size()] ) ) );
        }
      }
    }
    
    // Add debugging information
    if( rb.isDebug() ) {
      List<String> match = null;
      if( booster != null ) {
        // Extract the elevated terms into a list
        match = new ArrayList<String>(booster.priority.size());
        for( Object o : booster.include.clauses() ) {
          TermQuery tq = (TermQuery)((BooleanClause)o).getQuery();
          match.add( tq.getTerm().text() );
        }
      }
      
      SimpleOrderedMap<Object> dbg = new SimpleOrderedMap<Object>();
      dbg.add( "q", qstr );
      dbg.add( "match", match );
      rb.addDebugInfo( "queryBoosting", dbg );
    }
  }

  @Override
  public void process(ResponseBuilder rb) throws IOException {
    // Do nothing -- the real work is modifying the input query
  }
    
  //---------------------------------------------------------------------------------
  // SolrInfoMBean
  //---------------------------------------------------------------------------------

  @Override
  public String getDescription() {
    return "Query Boosting -- boost particular documents for a given query";
  }

  @Override
  public String getVersion() {
    return "$Revision: 819234 $";
  }

  @Override
  public String getSourceId() {
    return "$Id: QueryElevationComponent.java 819234 2009-09-26 23:46:44Z markrmiller $";
  }

  @Override
  public String getSource() {
    return "$URL: https://svn.apache.org/repos/asf/lucene/solr/branches/branch-1.4/src/java/org/apache/solr/handler/component/QueryElevationComponent.java $";
  }

  @Override
  public URL[] getDocs() {
    try {
      return new URL[] {
        new URL("http://wiki.apache.org/solr/QueryElevationComponent")
      };
    } 
    catch (MalformedURLException e) {
      throw new RuntimeException( e );
    }
  }
}

class ElevationComparatorSource extends FieldComparatorSource {
  private final Map<String,Integer> priority;

  public ElevationComparatorSource( final Map<String,Integer> boosts) {
    this.priority = boosts;
  }

  public FieldComparator newComparator(final String fieldname, final int numHits, int sortPos, boolean reversed) throws IOException {
    return new FieldComparator() {
      
      FieldCache.StringIndex idIndex;
      private final int[] values = new int[numHits];
      int bottomVal;

      public int compare(int slot1, int slot2) {
        return values[slot2] - values[slot1];  // values will be small enough that there is no overflow concern
      }

      public void setBottom(int slot) {
        bottomVal = values[slot];
      }

      private int docVal(int doc) throws IOException {
        String id = idIndex.lookup[idIndex.order[doc]];
        Integer prio = priority.get(id);
        return prio == null ? 0 : prio.intValue();
      }

      public int compareBottom(int doc) throws IOException {
        return docVal(doc) - bottomVal;
      }

      public void copy(int slot, int doc) throws IOException {
        values[slot] = docVal(doc);
      }

      public void setNextReader(IndexReader reader, int docBase) throws IOException {
        idIndex = FieldCache.DEFAULT.getStringIndex(reader, fieldname);
      }

      public Comparable value(int slot) {
        return values[slot];
      }
    };
  }
}

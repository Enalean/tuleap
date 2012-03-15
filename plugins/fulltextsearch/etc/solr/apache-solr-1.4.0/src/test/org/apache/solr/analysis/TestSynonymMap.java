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

package org.apache.solr.analysis;

import java.util.ArrayList;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

import org.apache.lucene.analysis.Token;

public class TestSynonymMap extends AnalysisTestCase {

  public void testInvalidMappingRules() throws Exception {
    SynonymMap synMap = new SynonymMap( true );
    List<String> rules = new ArrayList<String>( 1 );
    rules.add( "a=>b=>c" );
    try{
        SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
        fail( "RuntimeException must be thrown." );
    }
    catch( RuntimeException expected ){}
  }
  
  public void testReadMappingRules() throws Exception {
	SynonymMap synMap;

    // (a)->[b]
    List<String> rules = new ArrayList<String>();
    rules.add( "a=>b" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 1, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "b" );

    // (a)->[c]
    // (b)->[c]
    rules.clear();
    rules.add( "a,b=>c" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "c" );
    assertTokIncludes( synMap, "b", "c" );

    // (a)->[b][c]
    rules.clear();
    rules.add( "a=>b,c" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 1, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "b" );
    assertTokIncludes( synMap, "a", "c" );

    // (a)->(b)->[a2]
    //      [a1]
    rules.clear();
    rules.add( "a=>a1" );
    rules.add( "a b=>a2" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 1, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a1" );
    assertEquals( 1, getSubSynonymMap( synMap, "a" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "a" ), "b", "a2" );

    // (a)->(b)->[a2]
    //      (c)->[a3]
    //      [a1]
    rules.clear();
    rules.add( "a=>a1" );
    rules.add( "a b=>a2" );
    rules.add( "a c=>a3" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 1, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a1" );
    assertEquals( 2, getSubSynonymMap( synMap, "a" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "a" ), "b", "a2" );
    assertTokIncludes( getSubSynonymMap( synMap, "a" ), "c", "a3" );

    // (a)->(b)->[a2]
    //      [a1]
    // (b)->(c)->[b2]
    //      [b1]
    rules.clear();
    rules.add( "a=>a1" );
    rules.add( "a b=>a2" );
    rules.add( "b=>b1" );
    rules.add( "b c=>b2" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a1" );
    assertEquals( 1, getSubSynonymMap( synMap, "a" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "a" ), "b", "a2" );
    assertTokIncludes( synMap, "b", "b1" );
    assertEquals( 1, getSubSynonymMap( synMap, "b" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "b" ), "c", "b2" );
  }
  
  public void testRead1waySynonymRules() throws Exception {
    SynonymMap synMap;

    // (a)->[a]
    // (b)->[a]
    List<String> rules = new ArrayList<String>();
    rules.add( "a,b" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", false, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertTokIncludes( synMap, "b", "a" );

    // (a)->[a]
    // (b)->[a]
    // (c)->[a]
    rules.clear();
    rules.add( "a,b,c" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", false, null);
    assertEquals( 3, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertTokIncludes( synMap, "b", "a" );
    assertTokIncludes( synMap, "c", "a" );

    // (a)->[a]
    // (b1)->(b2)->[a]
    rules.clear();
    rules.add( "a,b1 b2" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", false, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertEquals( 1, getSubSynonymMap( synMap, "b1" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "b1" ), "b2", "a" );

    // (a1)->(a2)->[a1][a2]
    // (b)->[a1][a2]
    rules.clear();
    rules.add( "a1 a2,b" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", false, null);
    assertEquals( 2, synMap.submap.size() );
    assertEquals( 1, getSubSynonymMap( synMap, "a1" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "a1" ), "a2", "a1" );
    assertTokIncludes( getSubSynonymMap( synMap, "a1" ), "a2", "a2" );
    assertTokIncludes( synMap, "b", "a1" );
    assertTokIncludes( synMap, "b", "a2" );
  }
  
  public void testRead2waySynonymRules() throws Exception {
    SynonymMap synMap;

    // (a)->[a][b]
    // (b)->[a][b]
    List<String> rules = new ArrayList<String>();
    rules.add( "a,b" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertTokIncludes( synMap, "a", "b" );
    assertTokIncludes( synMap, "b", "a" );
    assertTokIncludes( synMap, "b", "b" );

    // (a)->[a][b][c]
    // (b)->[a][b][c]
    // (c)->[a][b][c]
    rules.clear();
    rules.add( "a,b,c" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 3, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertTokIncludes( synMap, "a", "b" );
    assertTokIncludes( synMap, "a", "c" );
    assertTokIncludes( synMap, "b", "a" );
    assertTokIncludes( synMap, "b", "b" );
    assertTokIncludes( synMap, "b", "c" );
    assertTokIncludes( synMap, "c", "a" );
    assertTokIncludes( synMap, "c", "b" );
    assertTokIncludes( synMap, "c", "c" );

    // (a)->[a]
    //      [b1][b2]
    // (b1)->(b2)->[a]
    //             [b1][b2]
    rules.clear();
    rules.add( "a,b1 b2" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 2, synMap.submap.size() );
    assertTokIncludes( synMap, "a", "a" );
    assertTokIncludes( synMap, "a", "b1" );
    assertTokIncludes( synMap, "a", "b2" );
    assertEquals( 1, getSubSynonymMap( synMap, "b1" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "b1" ), "b2", "a" );
    assertTokIncludes( getSubSynonymMap( synMap, "b1" ), "b2", "b1" );
    assertTokIncludes( getSubSynonymMap( synMap, "b1" ), "b2", "b2" );

    // (a1)->(a2)->[a1][a2]
    //             [b]
    // (b)->[a1][a2]
    //      [b]
    rules.clear();
    rules.add( "a1 a2,b" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, null);
    assertEquals( 2, synMap.submap.size() );
    assertEquals( 1, getSubSynonymMap( synMap, "a1" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( synMap, "a1" ), "a2", "a1" );
    assertTokIncludes( getSubSynonymMap( synMap, "a1" ), "a2", "a2" );
    assertTokIncludes( getSubSynonymMap( synMap, "a1" ), "a2", "b" );
    assertTokIncludes( synMap, "b", "a1" );
    assertTokIncludes( synMap, "b", "a2" );
    assertTokIncludes( synMap, "b", "b" );
  }
  
  public void testBigramTokenizer() throws Exception {
	SynonymMap synMap;
	
	// prepare bi-gram tokenizer factory
	BaseTokenizerFactory tf = new NGramTokenizerFactory();
	Map<String, String> args = new HashMap<String, String>();
	args.put("minGramSize","2");
	args.put("maxGramSize","2");
	tf.init( args );

    // (ab)->(bc)->(cd)->[ef][fg][gh]
    List<String> rules = new ArrayList<String>();
    rules.add( "abcd=>efgh" );
    synMap = new SynonymMap( true );
    SynonymFilterFactory.parseRules( rules, synMap, "=>", ",", true, tf);
    assertEquals( 1, synMap.submap.size() );
    assertEquals( 1, getSubSynonymMap( synMap, "ab" ).submap.size() );
    assertEquals( 1, getSubSynonymMap( getSubSynonymMap( synMap, "ab" ), "bc" ).submap.size() );
    assertTokIncludes( getSubSynonymMap( getSubSynonymMap( synMap, "ab" ), "bc" ), "cd", "ef" );
    assertTokIncludes( getSubSynonymMap( getSubSynonymMap( synMap, "ab" ), "bc" ), "cd", "fg" );
    assertTokIncludes( getSubSynonymMap( getSubSynonymMap( synMap, "ab" ), "bc" ), "cd", "gh" );
  }
  
  private void assertTokIncludes( SynonymMap map, String src, String exp ) throws Exception {
    Token[] tokens = ((SynonymMap)map.submap.get( src )).synonyms;
    boolean inc = false;
    for( Token token : tokens ){
      if( exp.equals( new String(token.termBuffer(), 0, token.termLength()) ) )
        inc = true;
    }
    assertTrue( inc );
  }
  
  private SynonymMap getSubSynonymMap( SynonymMap map, String src ){
    return (SynonymMap)map.submap.get( src );
  }
}

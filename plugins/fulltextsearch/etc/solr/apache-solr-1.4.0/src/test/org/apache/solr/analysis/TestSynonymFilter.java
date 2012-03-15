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

import org.apache.lucene.analysis.Token;
import org.apache.lucene.analysis.TokenStream;

import java.io.IOException;
import java.util.ArrayList;
import java.util.Arrays;
import java.util.Iterator;
import java.util.List;

/**
 * @version $Id: TestSynonymFilter.java 647621 2008-04-13 20:07:02Z yonik $
 */
public class TestSynonymFilter extends BaseTokenTestCase {

  public List strings(String str) {
    String[] arr = str.split(" ");
    return Arrays.asList(arr);
  }


  public List<Token> getTokList(SynonymMap dict, String input, boolean includeOrig) throws IOException {
    ArrayList<Token> lst = new ArrayList<Token>();
    final List toks = tokens(input);
    TokenStream ts = new TokenStream() {
      Iterator iter = toks.iterator();
      @Override
      public Token next() throws IOException {
        return iter.hasNext() ? (Token)iter.next() : null;
      }
    };

    SynonymFilter sf = new SynonymFilter(ts, dict);

    Token target = new Token();  // test with token reuse
    while(true) {
      Token t = sf.next(target);
      if (t==null) return lst;
      lst.add((Token)t.clone());
    }
  }


  public void testMatching() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = false;
    boolean merge = true;
    map.add(strings("a b"), tokens("ab"), orig, merge);
    map.add(strings("a c"), tokens("ac"), orig, merge);
    map.add(strings("a"), tokens("aa"), orig, merge);
    map.add(strings("b"), tokens("bb"), orig, merge);
    map.add(strings("z x c v"), tokens("zxcv"), orig, merge);
    map.add(strings("x c"), tokens("xc"), orig, merge);

    // System.out.println(map);
    // System.out.println(getTokList(map,"a",false));

    assertTokEqual(getTokList(map,"$",false), tokens("$"));
    assertTokEqual(getTokList(map,"a",false), tokens("aa"));
    assertTokEqual(getTokList(map,"a $",false), tokens("aa $"));
    assertTokEqual(getTokList(map,"$ a",false), tokens("$ aa"));
    assertTokEqual(getTokList(map,"a a",false), tokens("aa aa"));
    assertTokEqual(getTokList(map,"b",false), tokens("bb"));
    assertTokEqual(getTokList(map,"z x c v",false), tokens("zxcv"));
    assertTokEqual(getTokList(map,"z x c $",false), tokens("z xc $"));

    // repeats
    map.add(strings("a b"), tokens("ab"), orig, merge);
    map.add(strings("a b"), tokens("ab"), orig, merge);
    assertTokEqual(getTokList(map,"a b",false), tokens("ab"));

    // check for lack of recursion
    map.add(strings("zoo"), tokens("zoo"), orig, merge);
    assertTokEqual(getTokList(map,"zoo zoo $ zoo",false), tokens("zoo zoo $ zoo"));
    map.add(strings("zoo"), tokens("zoo zoo"), orig, merge);
    assertTokEqual(getTokList(map,"zoo zoo $ zoo",false), tokens("zoo zoo zoo zoo $ zoo zoo"));
  }

  public void testIncludeOrig() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = true;
    boolean merge = true;
    map.add(strings("a b"), tokens("ab"), orig, merge);
    map.add(strings("a c"), tokens("ac"), orig, merge);
    map.add(strings("a"), tokens("aa"), orig, merge);
    map.add(strings("b"), tokens("bb"), orig, merge);
    map.add(strings("z x c v"), tokens("zxcv"), orig, merge);
    map.add(strings("x c"), tokens("xc"), orig, merge);

    // System.out.println(map);
    // System.out.println(getTokList(map,"a",false));

    assertTokEqual(getTokList(map,"$",false), tokens("$"));
    assertTokEqual(getTokList(map,"a",false), tokens("a/aa"));
    assertTokEqual(getTokList(map,"a",false), tokens("a/aa"));
    assertTokEqual(getTokList(map,"$ a",false), tokens("$ a/aa"));
    assertTokEqual(getTokList(map,"a $",false), tokens("a/aa $"));
    assertTokEqual(getTokList(map,"$ a !",false), tokens("$ a/aa !"));
    assertTokEqual(getTokList(map,"a a",false), tokens("a/aa a/aa"));
    assertTokEqual(getTokList(map,"b",false), tokens("b/bb"));
    assertTokEqual(getTokList(map,"z x c v",false), tokens("z/zxcv x c v"));
    assertTokEqual(getTokList(map,"z x c $",false), tokens("z x/xc c $"));

    // check for lack of recursion
    map.add(strings("zoo zoo"), tokens("zoo"), orig, merge);
    assertTokEqual(getTokList(map,"zoo zoo $ zoo",false), tokens("zoo/zoo zoo/zoo $ zoo/zoo"));
    map.add(strings("zoo"), tokens("zoo zoo"), orig, merge);
    assertTokEqual(getTokList(map,"zoo zoo $ zoo",false), tokens("zoo/zoo zoo $ zoo/zoo zoo"));
  }


  public void testMapMerge() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = false;
    boolean merge = true;
    map.add(strings("a"), tokens("a5,5"), orig, merge);
    map.add(strings("a"), tokens("a3,3"), orig, merge);
    // System.out.println(map);
    assertTokEqual(getTokList(map,"a",false), tokens("a3 a5,2"));

    map.add(strings("b"), tokens("b3,3"), orig, merge);
    map.add(strings("b"), tokens("b5,5"), orig, merge);
    //System.out.println(map);
    assertTokEqual(getTokList(map,"b",false), tokens("b3 b5,2"));


    map.add(strings("a"), tokens("A3,3"), orig, merge);
    map.add(strings("a"), tokens("A5,5"), orig, merge);
    assertTokEqual(getTokList(map,"a",false), tokens("a3/A3 a5,2/A5"));

    map.add(strings("a"), tokens("a1"), orig, merge);
    assertTokEqual(getTokList(map,"a",false), tokens("a1 a3,2/A3 a5,2/A5"));

    map.add(strings("a"), tokens("a2,2"), orig, merge);
    map.add(strings("a"), tokens("a4,4 a6,2"), orig, merge);
    assertTokEqual(getTokList(map,"a",false), tokens("a1 a2 a3/A3 a4 a5/A5 a6"));
  }


  public void testOverlap() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = false;
    boolean merge = true;
    map.add(strings("qwe"), tokens("qq/ww/ee"), orig, merge);
    map.add(strings("qwe"), tokens("xx"), orig, merge);
    map.add(strings("qwe"), tokens("yy"), orig, merge);
    map.add(strings("qwe"), tokens("zz"), orig, merge);
    assertTokEqual(getTokList(map,"$",false), tokens("$"));
    assertTokEqual(getTokList(map,"qwe",false), tokens("qq/ww/ee/xx/yy/zz"));

    // test merging within the map

    map.add(strings("a"), tokens("a5,5 a8,3 a10,2"), orig, merge);
    map.add(strings("a"), tokens("a3,3 a7,4 a9,2 a11,2 a111,100"), orig, merge);
    assertTokEqual(getTokList(map,"a",false), tokens("a3 a5,2 a7,2 a8 a9 a10 a11 a111,100"));
  }

  public void testOffsets() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = false;
    boolean merge = true;

    // test that generated tokens start at the same offset as the original
    map.add(strings("a"), tokens("aa"), orig, merge);
    assertTokEqual(getTokList(map,"a,5",false), tokens("aa,5"));
    assertTokEqual(getTokList(map,"a,0",false), tokens("aa,0"));

    // test that offset of first replacement is ignored (always takes the orig offset)
    map.add(strings("b"), tokens("bb,100"), orig, merge);
    assertTokEqual(getTokList(map,"b,5",false), tokens("bb,5"));
    assertTokEqual(getTokList(map,"b,0",false), tokens("bb,0"));

    // test that subsequent tokens are adjusted accordingly
    map.add(strings("c"), tokens("cc,100 c2,2"), orig, merge);
    assertTokEqual(getTokList(map,"c,5",false), tokens("cc,5 c2,2"));
    assertTokEqual(getTokList(map,"c,0",false), tokens("cc,0 c2,2"));

  }


  public void testOffsetsWithOrig() throws IOException {
    SynonymMap map = new SynonymMap();

    boolean orig = true;
    boolean merge = true;

    // test that generated tokens start at the same offset as the original
    map.add(strings("a"), tokens("aa"), orig, merge);
    assertTokEqual(getTokList(map,"a,5",false), tokens("a,5/aa"));
    assertTokEqual(getTokList(map,"a,0",false), tokens("a,0/aa"));

    // test that offset of first replacement is ignored (always takes the orig offset)
    map.add(strings("b"), tokens("bb,100"), orig, merge);
    assertTokEqual(getTokList(map,"b,5",false), tokens("bb,5/b"));
    assertTokEqual(getTokList(map,"b,0",false), tokens("bb,0/b"));

    // test that subsequent tokens are adjusted accordingly
    map.add(strings("c"), tokens("cc,100 c2,2"), orig, merge);
    assertTokEqual(getTokList(map,"c,5",false), tokens("cc,5/c c2,2"));
    assertTokEqual(getTokList(map,"c,0",false), tokens("cc,0/c c2,2"));
  }


  public void testOffsetBug() throws IOException {
    // With the following rules:
    // a a=>b
    // x=>y
    // analysing "a x" causes "y" to have a bad offset (end less than start)
    // SOLR-167
    SynonymMap map = new SynonymMap();

    boolean orig = false;
    boolean merge = true;

    map.add(strings("a a"), tokens("b"), orig, merge);
    map.add(strings("x"), tokens("y"), orig, merge);

    System.out.println(getTokList(map,"a,1,0,1 a,1,2,3 x,1,4,5",false));

    // "a a x" => "b y"
    assertTokEqualOff(getTokList(map,"a,1,0,1 a,1,2,3 x,1,4,5",false), tokens("b,1,0,3 y,1,4,5"));
  }

}

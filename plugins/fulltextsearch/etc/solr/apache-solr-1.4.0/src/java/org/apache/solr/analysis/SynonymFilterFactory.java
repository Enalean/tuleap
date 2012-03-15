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
import org.apache.solr.common.ResourceLoader;
import org.apache.solr.common.util.StrUtils;
import org.apache.solr.util.plugin.ResourceLoaderAware;

import java.io.File;
import java.io.IOException;
import java.io.Reader;
import java.io.StringReader;
import java.util.ArrayList;
import java.util.List;
import java.util.Map;

/**
 * @version $Id: SynonymFilterFactory.java 712457 2008-11-09 01:24:11Z koji $
 */
public class SynonymFilterFactory extends BaseTokenFilterFactory implements ResourceLoaderAware {

  public void inform(ResourceLoader loader) {
    String synonyms = args.get("synonyms");

    boolean ignoreCase = getBoolean("ignoreCase", false);
    boolean expand = getBoolean("expand", true);

    String tf = args.get("tokenizerFactory");
    TokenizerFactory tokFactory = null;
    if( tf != null ){
      tokFactory = loadTokenizerFactory( loader, tf, args );
    }

    if (synonyms != null) {
      List<String> wlist=null;
      try {
        File synonymFile = new File(synonyms);
        if (synonymFile.exists()) {
          wlist = loader.getLines(synonyms);
        } else  {
          List<String> files = StrUtils.splitFileNames(synonyms);
          wlist = new ArrayList<String>();
          for (String file : files) {
            List<String> lines = loader.getLines(file.trim());
            wlist.addAll(lines);
          }
        }
      } catch (IOException e) {
        throw new RuntimeException(e);
      }
      synMap = new SynonymMap(ignoreCase);
      parseRules(wlist, synMap, "=>", ",", expand,tokFactory);
    }
  }

  private SynonymMap synMap;

  static void parseRules(List<String> rules, SynonymMap map, String mappingSep,
    String synSep, boolean expansion, TokenizerFactory tokFactory) {
    int count=0;
    for (String rule : rules) {
      // To use regexes, we need an expression that specifies an odd number of chars.
      // This can't really be done with string.split(), and since we need to
      // do unescaping at some point anyway, we wouldn't be saving any effort
      // by using regexes.

      List<String> mapping = StrUtils.splitSmart(rule, mappingSep, false);

      List<List<String>> source;
      List<List<String>> target;

      if (mapping.size() > 2) {
        throw new RuntimeException("Invalid Synonym Rule:" + rule);
      } else if (mapping.size()==2) {
        source = getSynList(mapping.get(0), synSep, tokFactory);
        target = getSynList(mapping.get(1), synSep, tokFactory);
      } else {
        source = getSynList(mapping.get(0), synSep, tokFactory);
        if (expansion) {
          // expand to all arguments
          target = source;
        } else {
          // reduce to first argument
          target = new ArrayList<List<String>>(1);
          target.add(source.get(0));
        }
      }

      boolean includeOrig=false;
      for (List<String> fromToks : source) {
        count++;
        for (List<String> toToks : target) {
          map.add(fromToks,
                  SynonymMap.makeTokens(toToks),
                  includeOrig,
                  true
          );
        }
      }
    }
  }

  // a , b c , d e f => [[a],[b,c],[d,e,f]]
  private static List<List<String>> getSynList(String str, String separator, TokenizerFactory tokFactory) {
    List<String> strList = StrUtils.splitSmart(str, separator, false);
    // now split on whitespace to get a list of token strings
    List<List<String>> synList = new ArrayList<List<String>>();
    for (String toks : strList) {
      List<String> tokList = tokFactory == null ?
        StrUtils.splitWS(toks, true) : splitByTokenizer(toks, tokFactory);
      synList.add(tokList);
    }
    return synList;
  }

  private static List<String> splitByTokenizer(String source, TokenizerFactory tokFactory){
    StringReader reader = new StringReader( source );
    TokenStream ts = loadTokenizer(tokFactory, reader);
    List<String> tokList = new ArrayList<String>();
    try {
      for( Token token = ts.next(); token != null; token = ts.next() ){
        String text = new String(token.termBuffer(), 0, token.termLength());
        if( text.length() > 0 )
          tokList.add( text );
      }
    } catch (IOException e) {
      throw new RuntimeException(e);
    }
    finally{
      reader.close();
    }
    return tokList;
  }

  private static TokenizerFactory loadTokenizerFactory(ResourceLoader loader, String cname, Map<String,String> args){
    TokenizerFactory tokFactory = (TokenizerFactory)loader.newInstance( cname );
    tokFactory.init( args );
    return tokFactory;
  }

  private static TokenStream loadTokenizer(TokenizerFactory tokFactory, Reader reader){
    return tokFactory.create( reader );
  }

  public SynonymMap getSynonymMap() {
    return synMap;
  }

  public SynonymFilter create(TokenStream input) {
    return new SynonymFilter(input,synMap);
  }
}

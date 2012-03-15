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

package org.apache.solr.search.function;

import org.apache.lucene.analysis.ngram.EdgeNGramTokenFilter;
import org.apache.lucene.queryParser.ParseException;
import org.apache.lucene.search.Query;
import org.apache.solr.search.ValueSourceParser;
import org.apache.solr.search.FunctionQParser;
import org.apache.solr.search.function.DocValues;
import org.apache.solr.search.function.QueryValueSource;
import org.apache.solr.search.function.SimpleFloatFunction;
import org.apache.solr.search.function.ValueSource;
import org.apache.solr.util.AbstractSolrTestCase;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.core.SolrCore;

import java.util.ArrayList;
import java.util.List;
import java.util.Random;
import java.util.Arrays;
import java.io.File;
import java.io.Writer;
import java.io.OutputStreamWriter;
import java.io.FileOutputStream;

/**
 * Tests some basic functionality of Solr while demonstrating good
 * Best Practices for using AbstractSolrTestCase
 */
public class TestFunctionQuery extends AbstractSolrTestCase {

  public String getSchemaFile() { return "schema11.xml"; }
  public String getSolrConfigFile() { return "solrconfig-functionquery.xml"; }
  public String getCoreName() { return "basic"; }

  public void setUp() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.setUp();
  }
  public void tearDown() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.tearDown();
  }

  String base = "external_foo_extf";
  void makeExternalFile(String field, String contents, String charset) {
    String dir = h.getCore().getDataDir();
    String filename = dir + "/external_" + field + "." + System.currentTimeMillis();
    try {
      Writer out = new OutputStreamWriter(new FileOutputStream(filename), charset);
      out.write(contents);
      out.close();
    } catch (Exception e) {
      throw new RuntimeException(e);
    }
  }


  void createIndex(String field, float... values) {
    // lrf.args.put("version","2.0");
    for (float val : values) {
      String s = Float.toString(val);
      if (field!=null) assertU(adoc("id", s, field, s));
      else assertU(adoc("id", s));
      // System.out.println("added doc for " + val);
    }
    assertU(optimize()); // squeeze out any possible deleted docs
  }

  // replace \0 with the field name and create a parseable string 
  public String func(String field, String template) {
    StringBuilder sb = new StringBuilder("_val_:\"");
    for (char ch : template.toCharArray()) {
      if (ch=='\0') {
        sb.append(field);
        continue;
      }
      if (ch=='"') sb.append('\\');
      sb.append(ch);
    }
    sb.append('"');
    return sb.toString();
  }

  void singleTest(String field, String funcTemplate, List<String> args, float... results) {
    String parseableQuery = func(field, funcTemplate);

    List<String> nargs = new ArrayList<String>(Arrays.asList("q", parseableQuery
            ,"fl", "*,score"
            ,"indent","on"
            ,"rows","100"));

    if (args != null) {
      for (String arg : args) {
        nargs.add(arg.replace("\0",field));
      }
    }

    List<String> tests = new ArrayList<String>();

    // Construct xpaths like the following:
    // "//doc[./float[@name='foo_pf']='10.0' and ./float[@name='score']='10.0']"

    for (int i=0; i<results.length; i+=2) {
      String xpath = "//doc[./float[@name='" + "id" + "']='"
              + results[i] + "' and ./float[@name='score']='"
              + results[i+1] + "']";
      tests.add(xpath);
    }

    assertQ(req(nargs.toArray(new String[]{}))
            , tests.toArray(new String[]{})
    );
  }

  void singleTest(String field, String funcTemplate, float... results) {
    singleTest(field, funcTemplate, null, results);
  }

  void doTest(String field) {
    // lrf.args.put("version","2.0");
    float[] vals = new float[] {
      100,-4,0,10,25,5
    };
    createIndex(field,vals);
    createIndex(null, 88);  // id with no value

    // test identity (straight field value)
    singleTest(field, "\0", 10,10);

    // test constant score
    singleTest(field,"1.414213", 10, 1.414213f);
    singleTest(field,"-1.414213", 10, -1.414213f);

    singleTest(field,"sum(\0,1)", 10, 11);
    singleTest(field,"sum(\0,\0)", 10, 20);
    singleTest(field,"sum(\0,\0,5)", 10, 25);

    singleTest(field,"sub(\0,1)", 10, 9);

    singleTest(field,"product(\0,1)", 10, 10);
    singleTest(field,"product(\0,-2,-4)", 10, 80);

    singleTest(field,"log(\0)",10,1, 100,2);
    singleTest(field,"sqrt(\0)",100,10, 25,5, 0,0);
    singleTest(field,"abs(\0)",10,10, -4,4);
    singleTest(field,"pow(\0,\0)",0,1, 5,3125);
    singleTest(field,"pow(\0,0.5)",100,10, 25,5, 0,0);
    singleTest(field,"div(1,\0)",-4,-.25f, 10,.1f, 100,.01f);
    singleTest(field,"div(1,1)",-4,1, 10,1);

    singleTest(field,"sqrt(abs(\0))",-4,2);
    singleTest(field,"sqrt(sum(29,\0))",-4,5);

    singleTest(field,"map(\0,0,0,500)",10,10, -4,-4, 0,500);
    singleTest(field,"map(\0,-4,5,500)",100,100, -4,500, 0,500, 5,500, 10,10, 25,25);

    singleTest(field,"scale(\0,-1,1)",-4,-1, 100,1, 0,-0.9230769f);
    singleTest(field,"scale(\0,-10,1000)",-4,-10, 100,1000, 0,28.846153f);

    // test that infinity doesn't mess up scale function
    singleTest(field,"scale(log(\0),-1000,1000)",100,1000);

    // test use of an ValueSourceParser plugin: nvl function
    singleTest(field,"nvl(\0,1)", 0, 1, 100, 100);
    
    // compose the ValueSourceParser plugin function with another function
    singleTest(field, "nvl(sum(0,\0),1)", 0, 1, 100, 100);

    // test simple embedded query
    singleTest(field,"query({!func v=\0})", 10, 10, 88, 0);
    // test default value for embedded query
    singleTest(field,"query({!lucene v='\0:[* TO *]'},8)", 88, 8);
    singleTest(field,"sum(query({!func v=\0},7.1),query({!func v=\0}))", 10, 20, 100, 200);
    // test with sub-queries specified by other request args
    singleTest(field,"query({!func v=$vv})", Arrays.asList("vv","\0"), 10, 10, 88, 0);
    singleTest(field,"query($vv)",Arrays.asList("vv","{!func}\0"), 10, 10, 88, 0);
    singleTest(field,"sum(query($v1,5),query($v1,7))",
            Arrays.asList("v1","\0:[* TO *]"),  88,12
            );
  }

  public void testFunctions() {
    doTest("foo_pf");  // a plain float field
    doTest("foo_f");  // a sortable float field
    doTest("foo_tf");  // a trie float field
  }

  public void testExternalField() {
    String field = "foo_extf";

    float[] ids = {100,-4,0,10,25,5,77,23,55,-78,-45,-24,63,78,94,22,34,54321,261,-627};

    createIndex(null,ids);

    // Unsorted field, largest first
    makeExternalFile(field, "54321=543210\n0=-999\n25=250","UTF-8");
    // test identity (straight field value)
    singleTest(field, "\0", 54321, 543210, 0,-999, 25,250, 100, 1);
    Object orig = FileFloatSource.onlyForTesting;
    singleTest(field, "log(\0)");
    // make sure the values were cached
    assertTrue(orig == FileFloatSource.onlyForTesting);
    singleTest(field, "sqrt(\0)");
    assertTrue(orig == FileFloatSource.onlyForTesting);

    makeExternalFile(field, "0=1","UTF-8");
    assertU(adoc("id", "10000")); // will get same reader if no index change
    assertU(commit());
    assertTrue(orig != FileFloatSource.onlyForTesting);


    Random r = new Random();
    for (int i=0; i<10; i++) {   // do more iterations for a thorough test
      int len = r.nextInt(ids.length+1);
      boolean sorted = r.nextBoolean();
      // shuffle ids
      for (int j=0; j<ids.length; j++) {
        int other=r.nextInt(ids.length);
        float v=ids[0];
        ids[0] = ids[other];
        ids[other] = v;
      }

      if (sorted) {
        // sort only the first elements
        Arrays.sort(ids,0,len);
      }

      // make random values
      float[] vals = new float[len];
      for (int j=0; j<len; j++) {
        vals[j] = r.nextInt(200)-100;
      }

      // make and write the external file
      StringBuilder sb = new StringBuilder();
      for (int j=0; j<len; j++) {
        sb.append("" + ids[j] + "=" + vals[j]+"\n");        
      }
      makeExternalFile(field, sb.toString(),"UTF-8");

      // make it visible
      assertU(adoc("id", "10001")); // will get same reader if no index change
      assertU(commit());

      // test it
      float[] answers = new float[ids.length*2];
      for (int j=0; j<len; j++) {
        answers[j*2] = ids[j];
        answers[j*2+1] = vals[j];
      }
      for (int j=len; j<ids.length; j++) {
        answers[j*2] = ids[j];
        answers[j*2+1] = 1;  // the default values
      }

      singleTest(field, "\0", answers);
      System.out.println("Done test "+i);
    }
  }

  public void testGeneral() {
    assertU(adoc("id","1", "a_tdt","2009-08-31T12:10:10.123Z", "b_tdt","2009-08-31T12:10:10.124Z"));
    assertU(adoc("id","2"));
    assertU(commit()); // create more than one segment
    assertU(adoc("id","3"));
    assertU(adoc("id","4"));
    assertU(commit()); // create more than one segment
    assertU(adoc("id","5"));
    assertU(adoc("id","6"));
    assertU(commit());

    // test that ord and rord are working on a global index basis, not just
    // at the segment level (since Lucene 2.9 has switched to per-segment searching)
    assertQ(req("fl","*,score","q", "{!func}ord(id)", "fq","id:6"), "//float[@name='score']='6.0'");
    assertQ(req("fl","*,score","q", "{!func}top(ord(id))", "fq","id:6"), "//float[@name='score']='6.0'");
    assertQ(req("fl","*,score","q", "{!func}rord(id)", "fq","id:1"),"//float[@name='score']='6.0'");
    assertQ(req("fl","*,score","q", "{!func}top(rord(id))", "fq","id:1"),"//float[@name='score']='6.0'");


    // test that we can subtract dates to millisecond precision
    assertQ(req("fl","*,score","q", "{!func}ms(a_tdt,b_tdt)", "fq","id:1"), "//float[@name='score']='-1.0'");
    assertQ(req("fl","*,score","q", "{!func}ms(b_tdt,a_tdt)", "fq","id:1"), "//float[@name='score']='1.0'");
    assertQ(req("fl","*,score","q", "{!func}ms(2009-08-31T12:10:10.125Z,2009-08-31T12:10:10.124Z)", "fq","id:1"), "//float[@name='score']='1.0'");
    assertQ(req("fl","*,score","q", "{!func}ms(2009-08-31T12:10:10.124Z,a_tdt)", "fq","id:1"), "//float[@name='score']='1.0'");
    assertQ(req("fl","*,score","q", "{!func}ms(2009-08-31T12:10:10.125Z,b_tdt)", "fq","id:1"), "//float[@name='score']='1.0'");

    assertQ(req("fl","*,score","q", "{!func}ms(2009-08-31T12:10:10.125Z/SECOND,2009-08-31T12:10:10.124Z/SECOND)", "fq","id:1"), "//float[@name='score']='0.0'");

    for (int i=100; i<112; i++) {
      assertU(adoc("id",""+i, "text","batman"));
    }
    assertU(commit());
    assertU(adoc("id","120", "text","batman superman"));   // in a segment by itself
    assertU(commit());

    // batman and superman have the same idf in single-doc segment, but very different in the complete index.
    String q ="{!func}query($qq)";
    String fq="id:120"; 
    assertQ(req("fl","*,score","q", q, "qq","text:batman", "fq",fq), "//float[@name='score']<'1.0'");
    assertQ(req("fl","*,score","q", q, "qq","text:superman", "fq",fq), "//float[@name='score']>'1.0'");

    // test weighting through a function range query
    assertQ(req("fl","*,score", "q", "{!frange l=1 u=10}query($qq)", "qq","text:superman"), "//*[@numFound='1']");

    // test weighting through a complex function
    q ="{!func}sub(div(sum(0.0,product(1,query($qq))),1),0)";
    assertQ(req("fl","*,score","q", q, "qq","text:batman", "fq",fq), "//float[@name='score']<'1.0'");
    assertQ(req("fl","*,score","q", q, "qq","text:superman", "fq",fq), "//float[@name='score']>'1.0'");
  }
}
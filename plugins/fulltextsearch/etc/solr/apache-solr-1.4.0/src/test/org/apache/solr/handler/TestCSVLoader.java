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

import org.apache.solr.util.AbstractSolrTestCase;
import org.apache.solr.request.LocalSolrQueryRequest;
import org.apache.solr.common.util.ContentStream;
import org.apache.solr.common.util.ContentStreamBase;

import java.io.*;
import java.util.List;
import java.util.ArrayList;

public class TestCSVLoader extends AbstractSolrTestCase {

  public String getSchemaFile() { return "schema12.xml"; }
  public String getSolrConfigFile() { return "solrconfig.xml"; }

  String filename = "solr_tmp.csv";
  String def_charset = "UTF-8";
  File file = new File(filename);

  public void setUp() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.setUp();
  }
  public void tearDown() throws Exception {
    // if you override setUp or tearDown, you better call
    // the super classes version
    super.tearDown();
    deleteFile();
  }

  void makeFile(String contents) {
    makeFile(contents,def_charset);
  }

  void makeFile(String contents, String charset) {
    try {
      Writer out = new OutputStreamWriter(new FileOutputStream(filename), charset);
      out.write(contents);
      out.close();
    } catch (Exception e) {
      throw new RuntimeException(e);
    }
  }

  void deleteFile() {
    file.delete();
  }

  void cleanup() {
    assertU(delQ("id:[100 TO 110]"));
    assertU(commit());
  }

  void loadLocal(String... args) throws Exception {
    LocalSolrQueryRequest req =  (LocalSolrQueryRequest)req(args);

    // TODO: stop using locally defined streams once stream.file and
    // stream.body work everywhere
    List<ContentStream> cs = new ArrayList<ContentStream>();
    cs.add(new ContentStreamBase.FileStream(new File(filename)));
    req.setContentStreams(cs);
    h.query("/update/csv",req);
  }

  public void testCSVLoad() throws Exception {
    makeFile("id\n100\n101\n102");
    loadLocal("stream.file",filename);
    // check default commit of false
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='0']");
    assertU(commit());
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='3']");
  }

  public void testCommitFalse() throws Exception {
    makeFile("id\n100\n101\n102");
    loadLocal("stream.file",filename,"commit","false");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='0']");
    assertU(commit());
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='3']");
  }

  public void testCommitTrue() throws Exception {
    makeFile("id\n100\n101\n102");
    loadLocal("stream.file",filename,"commit","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='3']");
  }

  public void testCSV() throws Exception {
    lrf.args.put("version","2.0");
    
    makeFile("id,str_s\n100,\"quoted\"\n101,\n102,\"\"\n103,");
    loadLocal("stream.file",filename,"commit","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted']");
    assertQ(req("id:101"),"count(//str[@name='str_s'])=0");
    // 102 is a quoted zero length field ,"", as opposed to ,,
    // but we can't distinguish this case (and it's debateable
    // if we should).  Does CSV have a way to specify missing
    // from zero-length?
    assertQ(req("id:102"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:103"),"count(//str[@name='str_s'])=0");

    // test overwrite by default
    loadLocal("stream.file",filename, "commit","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");

    // test explicitly adding header=true (the default)
    loadLocal("stream.file",filename, "commit","true","header","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");

    // test no overwrites
    loadLocal("stream.file",filename, "commit","true", "overwrite","false");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='8']");

    // test overwrite
    loadLocal("stream.file",filename, "commit","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");

    // test global value mapping
    loadLocal("stream.file",filename, "commit","true", "map","quoted:QUOTED");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='QUOTED']");
    assertQ(req("id:101"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:102"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:103"),"count(//str[@name='str_s'])=0");

    // test value mapping to empty (remove)
    loadLocal("stream.file",filename, "commit","true", "map","quoted:");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"count(//str[@name='str_s'])=0");

    // test value mapping from empty
    loadLocal("stream.file",filename, "commit","true", "map",":EMPTY");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted']");
    assertQ(req("id:101"),"//str[@name='str_s'][.='EMPTY']");
    assertQ(req("id:102"),"//str[@name='str_s'][.='EMPTY']");
    assertQ(req("id:103"),"//str[@name='str_s'][.='EMPTY']");

    // test multiple map rules
    loadLocal("stream.file",filename, "commit","true", "map",":EMPTY", "map","quoted:QUOTED");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='QUOTED']");
    assertQ(req("id:101"),"//str[@name='str_s'][.='EMPTY']");
    assertQ(req("id:102"),"//str[@name='str_s'][.='EMPTY']");
    assertQ(req("id:103"),"//str[@name='str_s'][.='EMPTY']");

    // test indexing empty fields
    loadLocal("stream.file",filename, "commit","true", "f.str_s.keepEmpty","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted']");
    assertQ(req("id:101"),"//str[@name='str_s'][.='']");
    assertQ(req("id:102"),"//str[@name='str_s'][.='']");
    assertQ(req("id:103"),"//str[@name='str_s'][.='']");

    // test overriding the name of fields
    loadLocal("stream.file",filename, "commit","true",
             "fieldnames","id,my_s", "header","true",
             "f.my_s.map",":EMPTY");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='my_s'][.='quoted']");
    assertQ(req("id:101"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:102"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:103"),"count(//str[@name='str_s'])=0");
    assertQ(req("id:101"),"//str[@name='my_s'][.='EMPTY']");
    assertQ(req("id:102"),"//str[@name='my_s'][.='EMPTY']");
    assertQ(req("id:103"),"//str[@name='my_s'][.='EMPTY']");

    // test that header in file was skipped
    assertQ(req("id:id"),"//*[@numFound='0']");

    // test skipping a field via the "skip" parameter
    loadLocal("stream.file",filename,"commit","true","keepEmpty","true","skip","str_s");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:[100 TO 110]"),"count(//str[@name='str_s'])=0");

    // test skipping a field by specifying an empty name
    loadLocal("stream.file",filename,"commit","true","keepEmpty","true","fieldnames","id,");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:[100 TO 110]"),"count(//str[@name='str_s'])=0");

    // test loading file as if it didn't have a header
    loadLocal("stream.file",filename, "commit","true",
             "fieldnames","id,my_s", "header","false");
    assertQ(req("id:id"),"//*[@numFound='1']");
    assertQ(req("id:100"),"//str[@name='my_s'][.='quoted']");

    // test skipLines
    loadLocal("stream.file",filename, "commit","true",
             "fieldnames","id,my_s", "header","false", "skipLines","1");
    assertQ(req("id:id"),"//*[@numFound='1']");
    assertQ(req("id:100"),"//str[@name='my_s'][.='quoted']");


    // test multi-valued fields via field splitting w/ mapping of subvalues
    makeFile("id,str_s\n"
            +"100,\"quoted\"\n"
            +"101,\"a,b,c\"\n"
            +"102,\"a,,b\"\n"
            +"103,\n");
    loadLocal("stream.file",filename, "commit","true",
              "f.str_s.map",":EMPTY",
              "f.str_s.split","true");
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='4']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[1][.='a']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[2][.='b']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[3][.='c']");
    assertQ(req("id:102"),"//arr[@name='str_s']/str[2][.='EMPTY']");
    assertQ(req("id:103"),"//str[@name='str_s'][.='EMPTY']");


    // test alternate values for delimiters
    makeFile("id|str_s\n"
            +"100|^quoted^\n"
            +"101|a;'b';c\n"
            +"102|a;;b\n"
            +"103|\n"
            +"104|a\\\\b\n"  // no backslash escaping should be done by default
    );

    loadLocal("stream.file",filename, "commit","true",
              "separator","|",
              "encapsulator","^",
              "f.str_s.map",":EMPTY",
              "f.str_s.split","true",
              "f.str_s.separator",";",
              "f.str_s.encapsulator","'"
    );
    assertQ(req("id:[100 TO 110]"),"//*[@numFound='5']");
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[1][.='a']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[2][.='b']");
    assertQ(req("id:101"),"//arr[@name='str_s']/str[3][.='c']");
    assertQ(req("id:102"),"//arr[@name='str_s']/str[2][.='EMPTY']");
    assertQ(req("id:103"),"//str[@name='str_s'][.='EMPTY']");
    assertQ(req("id:104"),"//str[@name='str_s'][.='a\\\\b']");

    // test no escaping + double encapsulator escaping by default
    makeFile("id,str_s\n"
            +"100,\"quoted \"\" \\ string\"\n"
            +"101,unquoted \"\" \\ string\n"     // double encap shouldn't be an escape outside encap
            +"102,end quote \\\n"
    );
    loadLocal("stream.file",filename, "commit","true"
    );
    assertQ(req("id:100"),"//str[@name='str_s'][.='quoted \" \\ string']");
    assertQ(req("id:101"),"//str[@name='str_s'][.='unquoted \"\" \\ string']");
    assertQ(req("id:102"),"//str[@name='str_s'][.='end quote \\']");


    // setting an escape should disable encapsulator
    makeFile("id,str_s\n"
            +"100,\"quoted \"\" \\\" \\\\ string\"\n"  // quotes should be part of value
            +"101,unquoted \"\" \\\" \\, \\\\ string\n"
    );
    loadLocal("stream.file",filename, "commit","true"
            ,"escape","\\"
    );
    assertQ(req("id:100"),"//str[@name='str_s'][.='\"quoted \"\" \" \\ string\"']");
    assertQ(req("id:101"),"//str[@name='str_s'][.='unquoted \"\" \" , \\ string']");

  }

  

}

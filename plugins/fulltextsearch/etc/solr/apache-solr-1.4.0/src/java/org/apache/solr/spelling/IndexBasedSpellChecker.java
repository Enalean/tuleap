package org.apache.solr.spelling;
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

import org.apache.lucene.analysis.WhitespaceAnalyzer;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.store.FSDirectory;
import org.apache.solr.common.util.NamedList;
import org.apache.solr.core.SolrCore;
import org.apache.solr.core.SolrResourceLoader;
import org.apache.solr.schema.FieldType;
import org.apache.solr.schema.IndexSchema;
import org.apache.solr.search.SolrIndexSearcher;
import org.apache.solr.util.HighFrequencyDictionary;

import java.io.IOException;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;


/**
 * <p>
 * A spell checker implementation that loads words from Solr as well as arbitary Lucene indices.
 * </p>
 * 
 * <p>
 * Refer to <a href="http://wiki.apache.org/solr/SpellCheckComponent">SpellCheckComponent</a>
 * for more details.
 * </p>
 * 
 * @since solr 1.3
 **/
public class IndexBasedSpellChecker extends AbstractLuceneSpellChecker {
  private static final Logger log = LoggerFactory.getLogger(IndexBasedSpellChecker.class);

  public static final String THRESHOLD_TOKEN_FREQUENCY = "thresholdTokenFrequency";

  protected float threshold;
  protected IndexReader reader;

  public String init(NamedList config, SolrCore core) {
    super.init(config, core);
    threshold = config.get(THRESHOLD_TOKEN_FREQUENCY) == null ? 0.0f
            : (Float) config.get(THRESHOLD_TOKEN_FREQUENCY);
    initSourceReader();
    return name;
  }

  private void initSourceReader() {
    if (sourceLocation != null) {
      try {
        FSDirectory luceneIndexDir = FSDirectory.getDirectory(sourceLocation);
        this.reader = IndexReader.open(luceneIndexDir);
      } catch (IOException e) {
        throw new RuntimeException(e);
      }
    }
  }

  public void build(SolrCore core, SolrIndexSearcher searcher) {
    IndexReader reader = null;
    try {
      if (sourceLocation == null) {
        // Load from Solr's index
        reader = searcher.getReader();
      } else {
        // Load from Lucene index at given sourceLocation
        reader = this.reader;
      }

      // Create the dictionary
      dictionary = new HighFrequencyDictionary(reader, field,
          threshold);
      spellChecker.clearIndex();
      spellChecker.indexDictionary(dictionary);

    } catch (IOException e) {
      throw new RuntimeException(e);
    }
  }

  @Override
  protected IndexReader determineReader(IndexReader reader) {
    IndexReader result = null;
    if (sourceLocation != null) {
      result = this.reader;
    } else {
      result = reader;
    }
    return result;
  }

  @Override
  public void reload() throws IOException {
    super.reload();
    //reload the source
    initSourceReader();
  }

  public float getThreshold() {
    return threshold;
  }
}

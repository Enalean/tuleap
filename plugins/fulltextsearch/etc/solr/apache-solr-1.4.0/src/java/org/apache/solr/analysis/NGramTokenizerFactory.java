package org.apache.solr.analysis;

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

import org.apache.lucene.analysis.TokenStream;
import org.apache.lucene.analysis.ngram.NGramTokenizer;

import java.io.Reader;
import java.util.Map;

/**
 * Creates new instances of {@link NGramTokenizer}.
 */
public class NGramTokenizerFactory extends BaseTokenizerFactory {
    private int maxGramSize = 0;
    private int minGramSize = 0;
    
    /** Initializes the n-gram min and max sizes and the side from which one should start tokenizing. */
    @Override
    public void init(Map<String, String> args) {
        super.init(args);
        String maxArg = args.get("maxGramSize");
        maxGramSize = (maxArg != null ? Integer.parseInt(maxArg) : NGramTokenizer.DEFAULT_MAX_NGRAM_SIZE);

        String minArg = args.get("minGramSize");
        minGramSize = (minArg != null ? Integer.parseInt(minArg) : NGramTokenizer.DEFAULT_MIN_NGRAM_SIZE);
    }

    /** Creates the {@link TokenStream} of n-grams from the given {@link Reader}. */
    public NGramTokenizer create(Reader input) {
        return new NGramTokenizer(input, minGramSize, maxGramSize);
    }
}

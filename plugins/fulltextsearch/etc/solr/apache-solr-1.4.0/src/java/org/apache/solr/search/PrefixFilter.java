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

package org.apache.solr.search;

import org.apache.lucene.search.Filter;
import org.apache.lucene.search.DocIdSet;
import org.apache.lucene.index.Term;
import org.apache.lucene.index.IndexReader;
import org.apache.lucene.index.TermEnum;
import org.apache.lucene.index.TermDocs;
import org.apache.lucene.util.OpenBitSet;

import java.util.BitSet;
import java.io.IOException;

/**
 * @version $Id: PrefixFilter.java 690332 2008-08-29 16:54:41Z yonik $
 */
public class PrefixFilter extends Filter {
  protected final Term prefix;

  PrefixFilter(Term prefix) {
    this.prefix = prefix;
  }

  Term getPrefix() { return prefix; }

  @Override
  public BitSet bits(IndexReader reader) throws IOException {
    final BitSet bitSet = new BitSet(reader.maxDoc());
    new PrefixGenerator(prefix) {
      public void handleDoc(int doc) {
        bitSet.set(doc);
      }
    }.generate(reader);
    return bitSet;
  }

 @Override
  public DocIdSet getDocIdSet(IndexReader reader) throws IOException {
    final OpenBitSet bitSet = new OpenBitSet(reader.maxDoc());
    new PrefixGenerator(prefix) {
      public void handleDoc(int doc) {
        bitSet.set(doc);
      }
    }.generate(reader);
    return bitSet;
  }

  @Override
  public boolean equals(Object o) {
    return o instanceof PrefixFilter && ((PrefixFilter)o).prefix.equals(this.prefix);
  }

  @Override
  public int hashCode() {
    return 0xcecf7fe2 + prefix.hashCode();
  }

  @Override
  public String toString () {
    StringBuilder sb = new StringBuilder();
    sb.append("PrefixFilter(");
    sb.append(prefix.toString());
    sb.append(")");
    return sb.toString();
  }
}

// keep this protected until I decide if it's a good way
// to separate id generation from collection (or should
// I just reuse hitcollector???)
interface IdGenerator {
  public void generate(IndexReader reader) throws IOException;
  public void handleDoc(int doc);
}


abstract class PrefixGenerator implements IdGenerator {
  protected final Term prefix;

  PrefixGenerator(Term prefix) {
    this.prefix = prefix;
  }

  public void generate(IndexReader reader) throws IOException {
    TermEnum enumerator = reader.terms(prefix);
    TermDocs termDocs = reader.termDocs();

    try {

      String prefixText = prefix.text();
      String prefixField = prefix.field();
      do {
        Term term = enumerator.term();
        if (term != null &&
            term.text().startsWith(prefixText) &&
            term.field() == prefixField)
        {
          termDocs.seek(term);
          while (termDocs.next()) {
            handleDoc(termDocs.doc());
          }
        } else {
          break;
        }
      } while (enumerator.next());
    } finally {
      termDocs.close();
      enumerator.close();
    }
  }
}

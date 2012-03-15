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
package org.apache.solr.handler.dataimport;

import java.io.*;
import java.util.Properties;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;

/**
 * <p>
 * A DataSource which reads from local files
 * </p>
 * <p>
 * The file is read with the default platform encoding. It can be overriden by
 * specifying the encoding in solrconfig.xml
 * </p>
 * <p/>
 * <p>
 * Refer to <a
 * href="http://wiki.apache.org/solr/DataImportHandler">http://wiki.apache.org/solr/DataImportHandler</a>
 * for more details.
 * </p>
 * <p/>
 * <b>This API is experimental and may change in the future.</b>
 *
 * @version $Id: FileDataSource.java 812122 2009-09-07 13:12:01Z shalin $
 * @since solr 1.3
 */
public class FileDataSource extends DataSource<Reader> {
  public static final String BASE_PATH = "basePath";

  /**
   * The basePath for this data source
   */
  protected String basePath;

  /**
   * The encoding using which the given file should be read
   */
  protected String encoding = null;

  private static final Logger LOG = LoggerFactory.getLogger(FileDataSource.class);

  public void init(Context context, Properties initProps) {
    basePath = initProps.getProperty(BASE_PATH);
    if (initProps.get(URLDataSource.ENCODING) != null)
      encoding = initProps.getProperty(URLDataSource.ENCODING);
  }

  /**
   * <p>
   * Returns a reader for the given file.
   * </p>
   * <p>
   * If the given file is not absolute, we try to construct an absolute path
   * using basePath configuration. If that fails, then the relative path is
   * tried. If file is not found a RuntimeException is thrown.
   * </p>
   * <p>
   * <b>It is the responsibility of the calling method to properly close the
   * returned Reader</b>
   * </p>
   */
  public Reader getData(String query) {
    try {
      File file0 = new File(query);
      File file = file0;

      if (!file.isAbsolute())
        file = new File(basePath + query);

      if (file.isFile() && file.canRead()) {
        LOG.debug("Accessing File: " + file.toString());
        return openStream(file);
      } else if (file != file0)
        if (file0.isFile() && file0.canRead()) {
          LOG.debug("Accessing File0: " + file0.toString());
          return openStream(file0);
        }

      throw new FileNotFoundException("Could not find file: " + query);
    } catch (UnsupportedEncodingException e) {
      throw new RuntimeException(e);
    } catch (FileNotFoundException e) {
      throw new RuntimeException(e);
    }
  }

  /**
   * Open a {@link java.io.Reader} for the given file name
   *
   * @param file a {@link java.io.File} instance
   * @return a Reader on the given file
   * @throws FileNotFoundException if the File does not exist
   * @throws UnsupportedEncodingException if the encoding is unsupported
   * @since solr 1.4
   */
  protected Reader openStream(File file) throws FileNotFoundException,
          UnsupportedEncodingException {
    if (encoding == null) {
      return new InputStreamReader(new FileInputStream(file));
    } else {
      return new InputStreamReader(new FileInputStream(file), encoding);
    }
  }

  public void close() {

  }
}

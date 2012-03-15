# The ASF licenses this file to You under the Apache License, Version 2.0
# (the "License"); you may not use this file except in compliance with
# the License.  You may obtain a copy of the License at
#
#     http://www.apache.org/licenses/LICENSE-2.0
#
# Unless required by applicable law or agreed to in writing, software
# distributed under the License is distributed on an "AS IS" BASIS,
# WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
# See the License for the specific language governing permissions and
# limitations under the License.

require 'erb'

# "Abstract" base class, only useful with subclasses that add parameters
class Solr::Request::Select < Solr::Request::Base
      
  attr_reader :query_type
  
  def initialize(qt=nil, params={})
    @query_type = qt
    @select_params = params
  end
  
  def response_format
    :ruby
  end
  
  def handler
    'select'
  end
  
  def content_type
    'application/x-www-form-urlencoded; charset=utf-8'
  end

  def to_hash
    return {:qt => query_type, :wt => 'ruby'}.merge(@select_params)
  end
  
  def to_s
    raw_params = self.to_hash

    http_params = []
    raw_params.each do |key,value|
      if value.respond_to?(:each) && !value.is_a?(String)
        value.each { |v| http_params << "#{key}=#{ERB::Util::url_encode(v)}" unless v.nil?}
      else
        http_params << "#{key}=#{ERB::Util::url_encode(value)}" unless value.nil?
      end
    end

    http_params.join("&")
  end
  
end

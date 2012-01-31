require 'yaml'

class Svn
    
    def initialize(user, pass)
        svn = `which svn`.strip
        @svn = "#{svn} --username #{user} --password #{pass}"
    end
    
    def commit(working_copy, message)
        `cd #{working_copy} && #{@svn} commit -m "#{message}"`
    end
    
    def add(working_copy, file)
        `cd #{working_copy} && #{@svn} add #{file}`
    end
    
    def del(working_copy, file)
        `cd #{working_copy} && #{@svn} del #{file}`
    end
    
    def update(working_copy)
        `cd #{working_copy} && #{@svn} up`
    end
    
    def checkout(repository, working_copy_path)
        result = `cd #{working_copy_path} && #{@svn} co #{repository} .`
        result[/d+/]
    end
    
    def info(working_copy)
        result = `#{@svn} info #{working_copy}`
        yaml = YAML.load(result)
        yaml['Revision'].to_s
    end
end

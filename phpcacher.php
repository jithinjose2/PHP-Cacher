<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
/**
 * PHPCacher is used to cache php output and use it to reduce server load effectivly
 *
 * PHPCacher cache output of a php script using output buffering. This output saved
 * into an HTML static file. This data from this file is presented to the output
 * when same request is repeated. This effectively reduce server downtimes and reduce
 * response time.
 *
 * @http://www.techzonemind.com/php-cacher-improve-performance-webpages/
 * @version 0.1.0
 * @package PHPCacher
 * @author Jithin Jose <jithinjose2@gmail.com> http://jithin.pw/
 */
class PHPCacher {
    
    /* Current request need to be cached or not */
    public static $cache = true;
    
    /* Location at which cached data to be saved */
    public static $location = '/var/www/static/';
    
    /* Maximum lifetime of cached request in seconds */
    public static $ttl = 3600;      // ie, 1 hour
    
    /* the string used to uniquily identify a request []*/
    public static $request_id = 'url';
    
    /* boolean value to disable cashing when a session is set for a particular request */
    public static $disable_on_session = true;
    
    /* Only enable cashing when a server load will above this limit setting value to 0 disable this feature */
    public static $min_system_load = 0;
    
    /* Type of requests to be cached */
    public static $request_types = array("GET");
    
    /* indicate current request is cached or not */
    private static $cached = false;
    
    /* full save location of current request; */
    private static $file = "";
    
    /**
     * Start
     * start caching
     */
    public static function start($section_name=""){
       
        if($section_name != ""){
            self::$request_id = $section_name;
        }
        
        if(!self::$cache){
            return;
        }
        
        if(self::$disable_on_session && self::sessionStarted()){
            return;
        }
        
        if(!in_array($_SERVER['REQUEST_METHOD'],self::$request_types)){
            return;
        }
        
        if(self::$min_system_load>0){
            
            $system_loads = sys_getloadavg();
            
            if($system_loads[0] < self::$min_system_load){
                return;
            }
        }
        
        if(!file_exists(self::$location)){
            echo "Static location you have provided(".self::$location.") not exists, Please provide full location details to PHPCacher::\$location";
            return;
        }
        
        if(!is_writable(self::$location)){
            echo "Static location you have provided(".self::$location.")is not writable, Please provide write permission to the folder";
            return;
        }
        
        
        // create full filename and create directorys if does not exsists
        self::$file = self::$location."/".strtolower($_SERVER['REQUEST_METHOD']);
        if(self::$request_id == 'url') {
            $path       = explode('/', substr($_SERVER['REQUEST_URI'],1) );
            $filename   = array_pop($path );
            if(count($path) > 0 ){
                self::$file = self::$file . "/" . implode('/',$path);
            }
            if(!file_exists(self::$file)){
                mkdir(self::$file,0777,true);
            }
            self::$file = self::$file."/".$filename;
        } else {
            self::$file = self::$file ."/". self::$request_id;
        }
        self::$file = self::$file. ".html";
        
        // if cached file exsists use that cache file
        if(file_exists(self::$file)){
            $timeago = time()-filemtime(self::$file);
            if( $timeago < self::$ttl ) {
                header('X-PHP-CACHER: using cache '.$timeago.' seconds ago');
                echo file_get_contents(self::$file);
                echo die();
            }
        }
        // if reached here, this request should be cached, engange output buffering
        ob_start();
        self::$cached = true;
        
    }
    
    
    /**
     * end
     * function to end output buffering and save buffered details in a file
     */
    public static function end(){
        
        if(self::$cached){
            $content = ob_get_clean();
            echo $content;
            file_put_contents(self::$file , $content);
            self::$cached = false;
            
        }
        
    }
    
    /**
     * sessionStarted
     * check session started or not
     */
    public static function sessionStarted(){
        
        if(function_exists('session_status')){
            
            if(session_status() != PHP_SESSION_ACTIVE){
                return false;
            }
            
        }else{
            
            if(session_id() == '') {
                return false;
            }
            
        }
        
        return true;
        
    }
    
}

// Fire cache stop option if PHPCacher::end(); is not manually fired
function phpcacher_end_caching(){
    PHPCacher::end();
}
register_shutdown_function('phpcacher_end_caching');
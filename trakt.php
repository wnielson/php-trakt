<?php
/**
 * A simple class for accessing the Trakt API.  You can use it like so:
 *
 *  $trakt = new Trakt("You API Key");
 *  $trakt->showSeasons("The Walking Dead", true);
 *
 * You can view the list of available API methods here: http://trakt.tv/api-docs
 * To call a method, such as "search/movies", the ``Trakt`` class will respond
 * to the corresponding method name "searchMovies".  So, in the above example, the
 * following would work:
 *
 *  $trakt->searchMovies("28 Days Later");
 *
 * To call any methods that require authentication, you must first set the
 * authentication data:
 *
 *    $trakt->setAuth("username", "password");
 *
 *
 * Now the following will work:
 *
 *    $trakt->activityFriends();
 *
 *
 * POST requests are also supported and behave in much the same way as GET requests.
 * For example, to test your login credentials, you can do:
 *
 *    $trakt->accountTest("myusername", "mypassword");
 *
 *
 * One important thing to note is that if one or more arguments to a POST-method are
 * listed as "optional" in the API docs, you still need to include them when calling
 * the method.  So, for example "/lists/add/" lists the "description" as optional, so
 * here's how one might use this method:
 *
 *    $trakt->listsAdd("A Test List", "", "public");
 *
 * The order of the arguments is important and follows the order defined in the API
 * docs, but if in doubt, check the source code below.  Also, for those methods that
 * use POST and require authentication, you shouldn't supply the "username" and
 * "password" as arguments, instead use the ``setAuth`` function described above.  So
 * to add a friend via "/friends/add/", one should do:
 *
 *    $trakt->setAuth("myUsername", "myPassword");
 *    $trakt->friendsAdd("myFriendsUsername");
 *
 */


/**
 * Generate and return a slug for a given ``$phrase``.
 */
function slugify($phrase)
{
    $result = strtolower($phrase);
    $result = preg_replace("/[^a-z0-9\s-]/", "", $result);
    $result = trim(preg_replace("/[\s-]+/", " ", $result));
    $result = preg_replace("/\s/", "-", $result);
    
    return $result;
}


class Trakt
{
    public  $errUrl = '';
    public  $errNum = 0;
    public  $errMsg = '';
    
    public  $trackHost = "https://api.trakt.tv";
    
    private $urls = array(
        /**
         * Account methods
         */
        "/account/create/" => array(
            array("name" => "username", "method" => "post"),
            array("name" => "password", "method" => "post"),
            array("name" => "email",    "method" => "post")
        ),
        "/account/test/" => array(
            array("name" => "username", "method" => "post"),
            array("name" => "password", "method" => "post")
        ),
    
        /**
         * Activity methods
         */
        "/activity/community.json/" => array(
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/episodes.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "episode"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/friends.json/" => array(
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/movies.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/seasons.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/shows.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        "/activity/user.json/" => array(
            array("name" => "username"),
            array("name" => "types",     "optional" => true),
            array("name" => "actions",   "optional" => true),
            array("name" => "timestamp", "optional" => true)
        ),
        
        /**
         * Calendar methods
         */
        "/calendar/premieres.json/" => array(
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        "/calendar/shows.json/" => array(
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        
        /**
         * Friends methods
         */
        "/friends/add/" => array(
            array("name" => "friend", "action" => "post")
        ),
        "/friends/all/" => null,
        "/friends/approve/" => array(
            array("name" => "friend", "action" => "post")
        ),
        "/friends/delete/" => array(
            array("name" => "friend", "action" => "post")
        ),
        "/friends/deny/" => array(
            array("name" => "friend", "action" => "post")
        ),
        "/friends/requests/" => null,
        
        /**
         * Genres methods
         */
        "/genres/movies.json/" => null,
        "/genres/shows.json/"  => null,
        
        /**
         * Lists methods
         *    TODO: Add these
         */
        "/lists/add/" => array(
            array("name" => "name",        "method" => "post"),
            array("name" => "description", "method" => "post"), //"optional" => true),
            array("name" => "privacy",     "method" => "post")
        ),
        "/lists/delete/" => array(
            array("name" => "slug", "convert" => slugify, "method" => "post")
        ),
        "/lists/items/add/" => array(
            array("name" => "slug", "convert" => slugify, "method" => "post"),
            array("name" => "items", "method" => "post")
        ),
        "/lists/items/delete/" => array(
            array("name" => "slug", "convert" => slugify, "method" => "post"),
            array("name" => "items", "method" => "post")
        ),
        "/lists/update/" => array(
            array("name" => "slug",        "method" => "post", "convert" => slugify),
            array("name" => "name",        "method" => "post"),
            array("name" => "description", "method" => "post"), //"optional" => true),
            array("name" => "privacy",     "method" => "post")
        ),
        
        /**
         * Movie methods
         */
        "/movie/related.json/" => array(
            array("name" => "titleOrId",   "convert"  => slugify),
            array("name" => "hidewatched", "optional" => true)
        ),
        "/movie/shouts.json/" => array(
            array("name" => "titleOrId",   "convert"  => slugify)
        ),
        "/movie/summary.json/" => array(
            array("name" => "titleOrId",   "convert"  => slugify)
        ),
        "/movie/watchingnow.json/" => array(
            array("name" => "titleOrId",   "convert"  => slugify)
        ),
        
        /**
         * Movies methods
         */
        "/movies/trending.json/" => null,
        
        /**
         * Search methods
         */
        "/search/episodes.json/" => array(
            array("name"=>"query", "convert" => urlencode)
        ),
        "/search/movies.json/" => array(
            array("name"=>"query", "convert" => urlencode)
        ),
        "/search/people.json/" => array(
            array("name"=>"query", "convert" => urlencode)
        ),
        "/search/shows.json/" => array(
            array("name"=>"query", "convert" => urlencode)
        ),
        "/search/users.json/" => array(
            array("name"=>"query", "convert" => urlencode)
        ),
        
        /**
         * Show methods
         */
        "/show/episode/shouts.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/episode/summary.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/episode/watchingnow.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "episode")
        ),
        "/show/related.json/" => array(
            array("name" => "titleOrId",   "convert"  => slugify),
            array("name" => "hidewatched", "optional" => true)
        ),
        "/show/season.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "season",    "convert"  => slugify),
        ),
        "/show/seasons.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
        ),
        "/show/shouts.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify)
        ),
        "/show/summary.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "extended",  "optional" => true)
        ),
        "/show/watchingnow.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify)
        ),
        
        /**
         * Shows methods
         */
        "/shows/trending.json/" => null,
        
        /**
         * User methods
         */
        "/user/calendar/shows.json/"     => array(
            array("name" => "username"),
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        "/user/friends.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/all.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/collection.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/hated.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/movies/loved.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/all.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/collection.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/hated.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/loved.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/library/shows/watched.json/" => array(
            array("name" => "username"),
            array("name" => "extended",  "optional" => true)
        ),
        "/user/list.json/" => array(
            array("name" => "username"),
            array("name" => "slug", "convert"  => slugify)
        ),
        "/user/lists.json/" => array(
            array("name" => "username")
        ),
        "/user/profile.json/" => array(
            array("name" => "username")
        ),
        "/user/watching.json/" => array(
            array("name" => "username")
        ),
        "/user/watchlist/episodes.json/" => array(
            array("name" => "username")
        ),
        "/user/watchlist/movies.json/"   => array(
            array("name" => "username")
        ),
        "/user/watchlist/shows.json/"    => array(
            array("name" => "username")
        )
    );
    
    function Trakt($apiKey, $debug=false)
    {
        $this->apiKey = $apiKey;
        $this->debug = $debug;
        $this->clearAuth();
    }
    
    public function __call($method, $arguments)
    {
        $methodUrl = $this->getMethodUrl($method);
        if (!array_key_exists($methodUrl, $this->urls)) {
            // Try post instead
            $methodUrl = $this->getMethodUrl($method, "");
        }
        
        if (array_key_exists($methodUrl, $this->urls)) {
            $url = $this->buildUrl($methodUrl);
            $post = array();
            
            foreach($arguments as $index => $arg) {
                if (array_key_exists($index, $this->urls[$methodUrl])) {
                    $opts = $this->urls[$methodUrl][$index];
                    
                    // Determine how to represent this field
                    $data = $arg;
                    if (array_key_exists("convert", $opts)) {
                        $data = $opts["convert"]($arg);
                    } else if (array_key_exists("optional", $opts) && $arg === true) {
                        $data = $opts["name"];
                    }
                    
                    // Is this a post field?
                    if (array_key_exists("method", $opts) && $opts["method"] == "post") {
                        $post[$opts["name"]] = $data;
                    } else {
                        $url .= $data."/";
                    }
                }
            }
            $url = rtrim($url, "/");
            
            if ($this->debug) {
                printf("URL: %s\n", $url);
            }
            
            return $this->getUrl($url, $post);
        }
        return false;
    }
    
    public function clearAuth()
    {
        $this->username = null;
        $this->password = null;
    }
    
    public function setAuth($username, $password)
    {
        $this->username = $username;
        $this->password = sha1($password);
    }
    
    /**
     * Given a string like "showSeason", returns "/show/season.json/"
     */
    private function getMethodUrl($method, $format=".json") {
        $method[0] = strtolower($method[0]);
        $func = create_function('$c', 'return "/" . strtolower($c[1]);');
        return "/".preg_replace_callback('/([A-Z])/', $func, $method).$format."/";
    }
    
    /**
     * Builds and returns the URL for the given ``$method``.  This method
     * basically just adds in the API Key.
     */
    private function buildUrl($methodUrl)
    {
        return $this->trackHost.$methodUrl.$this->apiKey."/";
    }
    
    /**
     * Query the ``$url`` and convert the JSON into an associative array.
     * If error are encountered, ``false`` is returned instead.
     */
    private function getUrl($url, $post=null)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_FAILONERROR, false); //trakt sends a 401 with 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
        if ($this->username && $this->password) {
            curl_setopt($ch, CURLOPT_USERPWD, $this->username.":".$this->password);
        }
        
        if ($post) {
            $data = json_encode($post);
            if ($this->debug) {
                var_dump($data);
            }
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        
        $buffer = curl_exec($ch);
        
        $this->errUrl = $url;
        $this->errNum = curl_errno($ch);
        $this->errMsg = curl_error($ch);
        
        curl_close($ch);
        
        //check for errors connecting to site
        if ($this->errNum && $this->errNum != 0)
        {
            return false;
        }
        else
        {
            //check for errors is the returned data
            $decoded = json_decode($buffer, true);
            if (is_object($decoded) && $decoded->status == 'failure')
            {
                $this->errMsg = $decoded->error;
                return false;
            }
            elseif (!is_array($decoded))
            {
                $this->errMsg = 'Nothing returned';
                return false;
            }
            return $decoded;
        }
    }
}

?>
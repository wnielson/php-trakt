<?php

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
 */

class Trakt
{
    public  $errUrl = '';
    public  $errNum = 0;
    public  $errMsg = '';
    
    public  $trackHost = "http://api.trakt.tv";
    
    private $urls = array(
        /**
         * Show methods
         */
        "/show/episode/summary.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "season"),
            array("name" => "episode"),
        ),
        "/show/related.json/" => array(
            array("name" => "titleOrId", "convert" => slugify),
            array("name" => "hidewatched",  "optional" => true)
        ),
        "/show/season.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "season",    "convert"  => slugify),
        ),
        "/show/seasons.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
        ),
        "/show/summary.json/" => array(
            array("name" => "titleOrId", "convert"  => slugify),
            array("name" => "extended",  "optional" => true)
        ),
        
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
         * User methods
         */
        "/user/calendar/shows.json/"     => array(
            array("name" => "username"),
            array("name" => "date", "optional" => true),
            array("name" => "days", "optional" => true)
        ),
        "/user/watchlist/episodes.json/" => array(array("name" => "username")),
        "/user/watchlist/movies.json/"   => array(array("name" => "username")),
        "/user/watchlist/shows.json/"    => array(array("name" => "username"))
    );
    
    function Trakt($apiKey)
    {
        $this->apiKey = $apiKey;
    }
    
    public function __call($method, $arguments)
    {
        $methodUrl = $this->getMethodUrl($method);
        if (array_key_exists($methodUrl, $this->urls)) {
            $url = $this->buildUrl($methodUrl);
            foreach($arguments as $index => $arg) {
                if (array_key_exists($index, $this->urls[$methodUrl])) {
                    
                    $opts = $this->urls[$methodUrl][$index];
                    if (array_key_exists("convert", $opts)) {
                        $url .= $opts["convert"]($arg)."/";
                    } else if (array_key_exists("optional", $opts) && $arg === true) {
                        $url .= $opts["name"]."/";
                    } else {
                        $url .= $arg."/";
                    }
                }
            }
            $url = rtrim($url, "/");
            return $this->getUrl($url);
        }
        return false;
    }
    
    /**
     * Given a string like "showSeason", returns "/show/season.json/"
     */
    private function getMethodUrl($method, $format="json") {
        $method[0] = strtolower($method[0]);
        $func = create_function('$c', 'return "/" . strtolower($c[1]);');
        return "/".preg_replace_callback('/([A-Z])/', $func, $method).".".$format."/";
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
    private function getUrl($url)
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 40);
        curl_setopt($ch, CURLOPT_FAILONERROR, false); //trakt sends a 401 with 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        
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
            elseif (!is_array($decoded) || empty($decoded))
            {
                $this->errMsg = 'Nothing returned';
                return false;
            }
            return $decoded;
        }
    }
}
?>
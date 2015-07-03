<?php
namespace Showtimes\Helpers;
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
 * POST requests are also supported and behave in much the same way as GET requests,
 * except that they accept a single argument which should be an array that matches the
 * signature as described in the API docs.  For example, to test your login credentials,
 * you can do:
 *
 *    $trakt->accountTest(array("username"=>"myusername", "password" => "mypassword"));
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

	public  $debugDomain = "https://api.staging.trakt.tv";
	public  $trackHost = "https://api-v2launch.trakt.tv";
	//public  $trackHost = "https://api.trakt.tv";

	private $urls = array(
		/**
		 * Account methods
		 */
		"/account/create/" => array(
			array("name" => "json", "method" => "post")
		),
		"/account/test/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Activity methods
		 */
		"/activity/community/" => array(
			array("name" => "types",     "optional" => true),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/episodes/" => array(
			array("name" => "titleOrId", "convert" => "slugify"),
			array("name" => "season"),
			array("name" => "episode"),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/friends/" => array(
			array("name" => "types",     "optional" => true),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/movies/" => array(
			array("name" => "titleOrId", "convert"  => "slugify"),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/seasons/" => array(
			array("name" => "titleOrId", "convert" => "slugify"),
			array("name" => "season"),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/shows/" => array(
			array("name" => "titleOrId", "convert"  => "slugify"),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/user/" => array(
			array("name" => "username"),
			array("name" => "types",     "optional" => true),
			array("name" => "actions",   "optional" => true),
			array("name" => "timestamp", "optional" => true)
		),
		"/activity/user/movies/" => array(
			array("name" => "username"),
			array("name" => "imdbid",     "optional" => true),
			array("name" => "actions",   "optional" => true)
		),

		/**
		 * Calendar methods
		 */
		"/calendars/all/premieres/" => array(
			array("name" => "date", "optional" => true),
			array("name" => "days", "optional" => true)
		),
		"/calendars/all/shows/" => array(
			array("name" => "date", "optional" => true),
			array("name" => "days", "optional" => true)
		),
		"/calendars/all/movies/" => array(
			array("name" => "date", "optional" => true),
			array("name" => "days", "optional" => true)
		),

		/**
		 * Friends methods
		 */
		"/friends/add/" => array(
			array("name" => "json", "method" => "post")
		),
		"/friends/all/" => array(
			array("name" => "json", "method" => "post")
		),
		"/friends/approve/" => array(
			array("name" => "json", "method" => "post")
		),
		"/friends/delete/" => array(
			array("name" => "json", "method" => "post")
		),
		"/friends/deny/" => array(
			array("name" => "json", "method" => "post")
		),
		"/friends/requests/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Genres methods
		 */
		"/genres/movies/" => null,
		"/genres/shows/"  => null,

		/**
		 * Lists methods
		 *    TODO: Add these
		 */
		"/lists/add/" => array(
			array("name" => "json", "method" => "post")
		),
		"/lists/delete/" => array(
			array("name" => "json", "method" => "post")
		),
		"/lists/items/add/" => array(
			array("name" => "json", "method" => "post")
		),
		"/lists/items/delete/" => array(
			array("name" => "json", "method" => "post")
		),
		"/lists/update/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Movie methods
		 */
		"/movie/cancelcheckin/"  => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/cancelwatching/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/checkin/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/scrobble/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/seen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/library/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/related/" => array(
			array("name" => "titleOrId",   "convert"  => "slugify"),
			array("name" => "hidewatched", "optional" => true)
		),
		"/movie/shouts/" => array(
			array("name" => "titleOrId",   "convert"  => "slugify")
		),
		"/movie/summary/" => array(
			array("name" => "titleOrId",   "convert"  => "slugify")
		),
		"/movie/unlibrary/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/unseen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/unwatchlist/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/watching/" => array(
			array("name" => "json", "method" => "post")
		),
		"/movie/watchingnow/" => array(
			array("name" => "titleOrId",   "convert"  => "slugify")
		),
		"/movie/watchlist/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Movies methods
		 */
		"/movies/trending/" => null,

		/**
		 * Rate methods
		 */
		"/rate/episode/" => array(
			array("name" => "json", "method" => "post")
		),
		"/rate/movie/" => array(
			array("name" => "json", "method" => "post")
		),
		"/rate/show/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Recommendations methods
		 */
		"/recommendations/movies/" => array(
			array("name" => "json", "method" => "post")
		),
		"/recommendations/movies/dismiss/" => array(
			array("name" => "json", "method" => "post")
		),
		"/recommendations/shows/" => array(
			array("name" => "json", "method" => "post")
		),
		"/recommendations/shows/dismiss/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Search methods
		 */

		"/search/episodes/" => array(
			array("name"=>"query", "convert" => "urlencode")
		),
		"/search/movies/" => array(
			array("name"=>"query", "convert" => "urlencode")
		),
		"/search/people/" => array(
			array("name"=>"query", "convert" => "urlencode")
		),
		"/search/shows/" => array(
			array("name"=>"query", "convert" => "urlencode")
		),
		"/search/users/" => array(
			array("name"=>"query", "convert" => "urlencode")
		),

		/**
		 * Shout methods
		 */
		"/shout/episode/" => array(
			array("name" => "json", "method" => "post")
		),
		"/shout/movie/" => array(
			array("name" => "json", "method" => "post")
		),
		"/shout/show/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Show methods
		 */
		"/show/cancelcheckin/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/cancelwatching/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/checkin/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/library/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/seen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/shouts/" => array(
			array("name" => "titleOrId", "convert" => "slugify"),
			array("name" => "season"),
			array("name" => "episode")
		),
		"/show/episode/summary/" => array(
			array("name" => "titleOrId", "convert" => "slugify"),
			array("name" => "season"),
			array("name" => "episode")
		),
		"/show/episode/unlibrary/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/unseen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/unwatchlist/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/episode/watchingnow/" => array(
			array("name" => "titleOrId", "convert" => "slugify"),
			array("name" => "season"),
			array("name" => "episode")
		),
		"/show/episode/watchlist/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/library/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/related/" => array(
			array("name" => "titleOrId",   "convert"  => "slugify"),
			array("name" => "hidewatched", "optional" => true)
		),
		"/show/scrobble/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/season/" => array(
			array("name" => "titleOrId", "convert"  => "slugify"),
			array("name" => "season",    "convert"  => "slugify"),
		),
		"/show/season/library/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/season/seen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/seasons/" => array(
			array("name" => "titleOrId", "convert"  => "slugify"),
		),
		"/show/seen/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/shouts/" => array(
			array("name" => "titleOrId", "convert"  => "slugify")
		),
		"/show/summary/" => array(
			array("name" => "titleOrId", "convert"  => "slugify"),
			array("name" => "extended",  "optional" => true)
		),
		"/show/unlibrary/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/unwatchlist/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/watching/" => array(
			array("name" => "json", "method" => "post")
		),
		"/show/watchingnow/" => array(
			array("name" => "titleOrId", "convert"  => "slugify")
		),
		"/show/watchlist/" => array(
			array("name" => "json", "method" => "post")
		),

		/**
		 * Shows methods
		 */
		"/shows/trending/" => null,

		/**
		 * User methods
		 */
		"/user/calendar/shows/"     => array(
			array("name" => "username"),
			array("name" => "date", "optional" => true),
			array("name" => "days", "optional" => true)
		),
		"/user/friends/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/movies/all/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/movies/collection/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/movies/hated/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/movies/loved/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/shows/all/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/shows/collection/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/shows/hated/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/shows/loved/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/library/shows/watched/" => array(
			array("name" => "username"),
			array("name" => "extended",  "optional" => true)
		),
		"/user/list/" => array(
			array("name" => "username"),
			array("name" => "slug", "convert"  => "slugify")
		),
		"/user/lists/" => array(
			array("name" => "username")
		),
		"/user/profile/" => array(
			array("name" => "username")
		),
		"/user/ratings/movies/" => array(
			array("name" => "username")
		),
		"/user/ratings/shows/" => array(
			array("name" => "username")
		),
		"/user/watching/" => array(
			array("name" => "username")
		),
		"/user/watchlist/episodes/" => array(
			array("name" => "username")
		),
		"/user/watchlist/movies/"   => array(
			array("name" => "username")
		),
		"/user/watchlist/shows/"    => array(
			array("name" => "username")
		)
	);

	private $clientId;
	private $clientSecret;
	private $redirectUri;
	private $debug = false;
	private $authToken;
	private $userToken;
	private $username;
	private $password;

	function __construct($params, $debug = false)
	{
		$this->clientId = $params["clientId"];
		$this->clientSecret = $params["clientSecret"];
		if (!empty($params["redirectUri"])) {
			$this->redirectUri = $params["redirectUri"];
		}

		if ($params["mode"] == "development") {
			$this->trackHost = $this->debugDomain;
		}

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
			$post = null;

			foreach($arguments as $index => $arg) {
				if (array_key_exists($index, $this->urls[$methodUrl])) {
					$opts = $this->urls[$methodUrl][$index];

					if (array_key_exists("method", $opts) && $opts["method"] == "post") {
						$post = $arg;
						break;
					}

					// Determine how to represent this field
					$data = $arg;
					if (array_key_exists("convert", $opts)) {
						$data = $opts["convert"]($arg);
					} else if (array_key_exists("optional", $opts) && $arg === true) {
						$data = $opts["name"];
					}

					$url .= $data."/";
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

	/**
	 * Sets authentication for all subsequent API calls.  If ``$isHash``
	 * is ``true``, then the ``$password`` is expected to be a valid
	 * sha1 hash of the real password.
	 */
	public function setAuth($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}

	/**
	 * Given a string like "showSeason", returns "/show/season/"
	 */
	private function getMethodUrl($method, $format = "") {
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
		//return $this->trackHost.$methodUrl."/";
		return $this->trackHost.$methodUrl;
	}

	private function login () {
		$response = $this->curl($this->buildUrl("/auth/login"), array(
			'username' => $this->username,
			'password' => $this->password,
		));
	}

	public function getUrl ($url, $post = null) {

		if (!empty($this->password) && empty($this->userToken)) {
			$this->login();
		}
		return $this->curl($url, $post);
	}

	/**
	 * Query the ``$url`` and convert the JSON into an associative array.
	 * If error are encountered, ``false`` is returned instead.
	 */
	private function curl($url, $post = null)
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($ch, CURLOPT_TIMEOUT, 40);
		curl_setopt($ch, CURLOPT_FAILONERROR, false); //trakt sends a 401 with
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
		//curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			"Content-Type: application/json",
			"trakt-api-version: 2",
			"trakt-api-key: ".$this->clientId
		));

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

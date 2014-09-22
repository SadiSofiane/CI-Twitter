<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Twitter library
 *
 * This library get recents tweet from a given username.
 * PHP versions 4 and 5.
 * Tested on CodeIgniter 2.x.
 *
 * @category  Module
 * @package   Twitter-API-PHP
 * @author    Sofiane Sadi <s.sadi@keepthinking.it>
 * @author    Keepthinking <www.keepthinking.it>
 * @license   http://www.php.net/license/3_0.txt  PHP License 3.0
 * @copyright 2014 Keepthinking <www.keepthinking.it>
 */

/**
 * Require TwitterAPIExchange by James Mallison <me@j7mbo.co.uk>: http://github.com/j7mbo/twitter-api-php
 */
require_once(APPPATH . 'libraries/TwitterAPIExchange.php');

/**
* Twitter api url.
*/
$GLOBALS['_TWEET_API_TIMELINE_URL'] = 'https://api.twitter.com/1.1/statuses/user_timeline.json';

/**
 * Twitter library.
 *
 * This library get recents tweet from a given username.
 * The tweets can be cached on a file or using database.
 * If a cache file is used, the file need full access permission (chmod 777 [filepath]).
 *
 * @category Module
 * @package  Twitter
 * @author   Sofiane Sadi <s.sadi@keepthinking.it>
 * @author   Keepthinking <www.keepthinking.it>
 * @todo	 Multi languages
 * @link     www.keepthinking.it
 */
class Twitter 
{
    /**
     * @var settings Config file content.
     */
    private $settings;

	/**
	 * Constructor.
	 *
	 * @param  $configfile string Path to the config file set to application/config/module/twitter by default.
     * @return NULL
	 */
	public function __construct($configfile = 'module/twitter') 
	{
		$this->ci =& get_instance();
		$config = $this->ci->config;
		$config->load($configfile);

		//Load database if necessary.
		if (strtolower($config->item('param_mode')) == 'database' OR strtolower($config->item('mode')) == 'database') 
			$this->ci->load->model('m_twitter');

		// Getting base information from config file
		if (strtolower($config->item('param_mode')) == 'file' ) {
			$this->settings = array(
				'mode'									=> strtolower($config->item('mode')),
				'param_mode'							=> strtolower($config->item('param_mode')),
				'database_twitter_table_name'			=> $config->item('database_twitter_table_name'),
				'database_twitter_table_param_sample'	=> $config->item('database_twitter_table_param_sample'),
				'database_twitter_table_param'			=> $config->item('database_twitter_table_param'),
				'consumer_key'              			=> $config->item('oauth_twitter_app_id'),
				'consumer_secret'           			=> $config->item('oauth_twitter_secret_key'),
				'oauth_access_token'        			=> $config->item('twitter_access_token'),
				'oauth_access_token_secret' 			=> $config->item('twitter_token_secret'),
				'username'								=> $config->item('get_tweet_username'),
				'cache_file_path'						=> APPPATH . $config->item('get_tweet_cache_path'),
				'tweet_count'							=> $config->item('get_tweet_counter'),
				'humanize'								=> $config->item('get_tweet_humanize'),
				'date_format'							=> $config->item('get_tweet_date_format'),
				'refresh_interval'						=> $config->item('get_tweet_refresh_interval')
			);
		} elseif (strtolower($config->item('param_mode')) == 'database' ) {
            $this->settings = array(
                'param_mode'                          => strtolower($config->item('param_mode')),
                'database_twitter_table_name'         => strtolower($config->item('database_twitter_table_name')),
                'database_twitter_table_param'        => strtolower($config->item('database_twitter_table_param')),
                'database_twitter_table_param_sample' => strtolower($config->item('database_twitter_table_param_sample')),
                'database_twitter_table_name'         => strtolower($config->item('database_twitter_table_name')),
                'mode'                                => strtolower($config->item('mode'))
                );

            $database_settings = $this->ci->m_twitter->get_settings($this->settings);
            $this->settings = array_merge($this->settings, $database_settings);
		} else
			throw new Exception("Please use 'database' or 'file' value for the param_mode config");

		$this->_check_params();
	}

	/**
	 * Params checker.
	 * 
	 * This function check if all params are correct. 
	 *
     * @param  NULL
     * @access private
     * @return NULL/Exception Return nothing / An exception if something unexpected append.
	 */
	private function _check_params() 
	{
		// Check if all required field are filled.
		if ( ! isset($this->settings['oauth_access_token'])
			|| ! isset($this->settings['oauth_access_token_secret'])
			|| ! isset($this->settings['consumer_key'])
			|| ! isset($this->settings['consumer_secret'])
			|| ! isset($this->settings['mode'])
			|| ! isset($this->settings['refresh_interval'])
			|| ! isset($this->settings['tweet_count'])
			|| ! isset($this->settings['username'])) {

			throw new Exception('Make sure you are passing all the required parameters.');
		}

	    // Check if specified mode is correct.
		if (strtolower($this->settings['mode']) == 'file') {
	        	// Check if file is writable.
			if ( ! empty($this->settings['cache_file_path'])) {
		        	// If file not exist try to create it.
				if ( ! file_exists($this->settings['cache_file_path'])) {
					$fileHandler = fopen($this->settings['cache_file_path'], 'w') or die("Can't create file, please create your cache file.");
					fclose($fileHandler);
				} elseif (!is_writable($this->settings['cache_file_path'])) {
		        		// If file not writable apply 777 chmod.
		        		if ( !chmod($this->settings['cache_file_path'], 777)) // Set full right for the cache file
		        		throw new Exception("Can't set full right to the cache file, please set chmod 777 to your cache file.");
		        	}
		        } else {
		        	// No cache file path given
		        	throw new Exception("Mode is set to file cache but no path file given. Please fill 'cache_file_path' under your config file.");
		        }
		} elseif (strtolower($this->settings['mode']) == 'database') {
		    $this->write_database();
        } else {
            throw new Exception("Wrong data source specified, please use 'database' or 'file' instead of $this->settings['mode']");
        }

	    if ($this->settings['humanize'] == TRUE) {
	    	if (empty($this->settings['date_format']))
	    		throw new Exception("Please specify a tweet date format");
	    }
	}

	/**
	 * Getter for tweets.
	 *
	 * This function use the twitter GET statuses/user_timeline api.
	 *
	 * @see	   https://dev.twitter.com/docs/api/1.1/get/statuses/user_timeline
     * @param  null
	 * @return array 	List of latest tweets.
	 */
	public function get_tweets() 
	{

		switch (strtolower($this->settings['mode'])) {
			case 'file':		//If file mode -> tweets get from cached file
                $this->write_file();
                $tweets = $this->read_file();
			break;
			case 'database':	//If database mode -> tweets get from database
                $tweets = $this->read_database();
			break;
			default:
                $tweets = NULL;
		}

		return $tweets;
	}

    /**
     * Twitter api caller.
     *
     * This function simply call and return result from the twitter api.
     *
     * @param  null
     * @return StdClass[] List of tweets
     */
	private function _twitter_api_call() {
		/**
		 *	Availables array params.
		 *
         *  <code>
		 *      user_id				- string ID of the user for whom to return results for.
		 *      screen_name			- The screen name of the user for whom to return results for.
		 *      since_id			- Returns results with an ID greater than the specified ID. 
		 *      count				- Specifies the number of tweets to try and retrieve, up to a maximum of 200 per distinct request. 
		 *      trim_user 			- When set to either true, t or 1, each tweet returned in a timeline will include a user object including only the status authors numerical ID. 
		 *      exclude_replies 	- This parameter will prevent replies from appearing in the returned timeline. Using exclude_replies with the count parameter will mean you will receive up-to count tweets. 
		 *      contributor_details - This parameter enhances the contributors element of the status response to include the screen_name of the contributor. By default only the user_id of the contributor is included.
		 *      include_rts 		- When set to false, the timeline will strip any native retweets (though they will still count toward both the maximal length of the timeline and the slice selected by the count parameter). 
         * 	</code>
		 */ 
		$urlparams = array(
			'screen_name' 		=> $this->settings['username'],
			'count'       		=> $this->settings['tweet_count'],
			'exclude_replies' 	=> 'false',
			'include_rts'		=> 'false');

		$getparams = '?screen_name='.$urlparams['screen_name'].
		'&count='.$urlparams['count'].
		'&include_rts='.$urlparams['include_rts'].
		'&exclude_replies='.$urlparams['exclude_replies'];
		$twitter = new TwitterAPIExchange($this->settings);

		$jsontweet = $twitter->setGetfield($getparams)->buildOauth($GLOBALS['_TWEET_API_TIMELINE_URL'], 'GET')->performRequest();
		$tweets = json_decode($jsontweet); 

		if (empty($tweets))
			throw new Exception('No tweets for the given username');

		if ($this->settings['humanize'])
			$tweets = $this->_humanize($tweets);

		return $tweets;
	}

	/**
	 * Tweets file writer. 
	 *
	 * This function look at the cache/tweets.php file.
	 * If the file is not modified since X minutes (refresh_interval param) the function call the get_tweets() and write tweets data inside the cache file.
	 *
	 * @param	NULL
	 * @return	void	Write into cache/tweets.php file the latest tweets.
	 */
	public function write_file()
	{
		// Flag will be set to true if cache file do need an update.
		$flagcache = false;
		//Check if file exists
		if (file_exists($this->settings['cache_file_path'])) {
			// Unserialize the data to get the timestamp.
			$data = unserialize(file_get_contents($this->settings['cache_file_path']));
			$time = time() - $this->settings['refresh_interval'] * 60;
			// 10 min cache.
			if ($data['timestamp'] < $time) {
				// Set the flag at true and take tweets from the file.
				$flagcache = true;
			}
		} else {
			throw new Exception("No cache file found");
		}
		
		// Cache doesn't exist or is older than 10 mins.
		if ($flagcache) {
			// We get the tweets from the API.
			$tweets = $this->_twitter_api_call();
			// Add the tweets and the timestamp.
			$data = array('tweets' => $tweets, 'timestamp' => time());
			// Write the tweets to the file.
			if ( ! file_put_contents($this->settings['cache_file_path'], serialize($data)))
				throw new Exception('Error during file writing.');
		}
	}

	/**
	 * Tweets file reader.
	 *
	 * Simply looking at the cache/tweets.php files and unserialize tweets data.
	 *
	 * @param  null
	 * @return array The latest tweets.
	 */
	public function read_file()
	{
		$tweets = unserialize(file_get_contents($this->settings['cache_file_path']));
		return $tweets['tweets'];
	}

	/**
	 * URL linkifier. 
	 *
	 * This function look at the cache/tweets.php file.
	 * If the file is not modified since 10 minutes the function call the get_tweets() and write tweets data inside the cache file.
	 *
	 * @param	 NULL
	 * @category Tools
	 * @return	 void	Write into cache/tweets.php file the latest tweets.
	 */
	private function _linkify($string) {
		$pattern = "/(http|https|ftp|ftps)\:\/\/[a-zA-Z0-9\-\.]+\.[a-zA-Z]{2,3}(\/\S*)?/";
		$new_string = preg_replace($pattern, "<a href=\"\\0\">\\0</a>", $string);
		$pattern = '/@([a-zA-Z0-9_]+)/';
		$replace = '<a href="http://twitter.com/\1">@\1</a>';
		$new_string = preg_replace($pattern, $replace, $new_string);
		$new_string = preg_replace('/>http:\\/\\//', '/>', $new_string);

		return $new_string;
	}

	/**
	 * Tweets humanizer. 
	 *
	 * This function return an humanized version of tweets.
	 *
	 * @param	tweets
	 * @category Tools
	 * @return	array[stdClass]	Array object of humanized tweets.
	 */
	private function _humanize($tweets) {
		$humanized_tweets = array();

		foreach($tweets as $key => &$tweet) {
			// Add hyperlink html tags to any urls, twitter ids or hashtags in the tweet.
			$humanized_tweets[$key]->id = $tweet->id_str;
			$humanized_tweets[$key]->content = $this->_linkify($tweet->text);
			$humanized_tweets[$key]->username = $this->_linkify('@'.$tweet->user->screen_name);

			// Convert Tweet display time to a UNIX timestamp. Twitter timestamps are in UTC/GMT time.
			$humanized_tweets[$key]->created_at = strtotime($tweet->created_at);

			// Current UNIX timestamp.
			$current_time = time();

			// Time diff. (used for since ... x x ago)
			$time_diff = abs($current_time - $tweet->created_at);
			switch ($time_diff)
			{
				case ($time_diff < 60):
				$humanized_tweets[$key]->date = $time_diff.' seconds ago';
				break;
				case ($time_diff >= 60 && $time_diff < 3600):
				$min = floor($time_diff/60);
				$humanized_tweets[$key]->date = $min.' minutes ago';
				break;
				case ($time_diff >= 3600 && $time_diff < 86400):
				$hour = floor($time_diff/3600);
				$humanized_tweets[$key]->date = 'about '.$hour.' hour';
				if ($hour > 1){ $tweet->date .= 's'; }
				$humanized_tweets[$key]->date .= ' ago';
				break;
				default:
				$humanized_tweets[$key]->date = date($this->settings['date_format'], strtotime($tweet->created_at));
				break;
			}
		}

		return $humanized_tweets;
	}

	/**
	 * Tweets database writer. 
	 *
	 * This function look at the cache/tweets.php file.
	 * If the file is not modified since 10 minutes the function call the get_tweets() and write tweets data inside the cache file.
	 *
	 * @param	NULL
	 * @return	void	Write into cache/tweets.php file the latest tweets.
	 */
	public function write_database()
	{
    	// Flag will be set to true if cache file do need an update.
		$flagcache = false;
		//Check if file exists
        // Unserialize the data to get the timestamp.
        $time = time() - $this->settings['refresh_interval'] * 60;
        // 10 min cache.
        if ($this->settings['timestamp'] < $time) {
            // Set the flag at true and take tweets from the file.
            $flagcache = true;
		}
		
		// Cache doesn't exist or is older than 10 mins.
		if ($flagcache) {
			// We get the tweets from the API.
			$tweets = $this->_twitter_api_call();
			// Add the tweets and the timestamp.
			$data = array('tweets' => $tweets, 'timestamp' => time());
			// Write the tweets to the file.
			$this->ci->m_twitter->set_tweets($this->settings['database_twitter_table_name'], $this->settings['database_twitter_table_param'], $data);
		}
	}

	/**
	 * Tweets database reader. 
	 *
	 * This function look at the cache/tweets.php file.
	 * If the file is not modified since 10 minutes the function call the get_tweets() and write tweets data inside the cache file.
	 *
	 * @param	NULL
	 * @return	void	Write into cache/tweets.php file the latest tweets.
	 */
	public function read_database()
	{
        return $this->ci->m_twitter->get_tweets($this->settings);
	}
}

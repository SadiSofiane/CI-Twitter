<?php  if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Base configuration
 *
 * @var string $param_mode  file|database Get params from database or file.
 * @var string $mode        file|database Persistence on database or file.
 */
$config['param_mode']	= 'file';  
$config['mode'] 		= "file";	
$config['load_get_tweet'] = TRUE;

/** 
 * Database configuration 
 *
 * @var string database_twitter_table_name          "[STRING]";
 * @var string database_twitter_table_param         "[STRING]";
 * @var string database_twitter_table_param_sample  "[STRING]";
 */
$config['database_twitter_table_name']			= 'twitter';            // Tweets table name.
$config['database_twitter_table_param']			= 'twitter_preference'; // Twitter param table name.
$config['database_twitter_table_param_sample']	= TRUE;                 // Insert sample data.

/**
 * Base Twitter params.
 *
 * @var string $oauth_twitter_app_id      Your twitter api ID.
 * @var string $oauth_twitter_secret_key  Your twitter api secret key.
 * @var string $twitter_access_token      Your twitter access token.
 * @var string $twitter_token_secret      Your twitter secret token.
 */
$config['oauth_twitter_app_id'] 	= "6E1ypf8Zlcge325CzKx4aa1UE";
$config['oauth_twitter_secret_key'] = "prW3lrwBYTIFe12kAPHVX2t1nVrpnQ0QSytoqhTfidFl8OiITg";
$config['twitter_access_token'] 	= "1511123108-pZX3qVTm13rSN6xEOVPuxFKVbPQ68DE37f0R9yd";
$config['twitter_token_secret'] 	= "wgpY6LvVG347E6fvKbf4nfnfeQcxEAYSFv0WkXFW5P4ai";

/** 
 * Get Tweet API params.
 *
 * @var string $get_tweet_username         Twitter username to get tweets from.
 * @var string $get_tweet_cache_path       Required if mode is set to "FILE": CACHE FILE PATH case sensitive if unix system.
 * @var bool   $get_tweet_linkify          Return Linkified URL: [TRUE/FALSE].
 * @var bool   $get_tweet_count            Number of tweets returned.
 * @var bool   $get_tweet_humanize         Number of tweets returned.
 * @var string $get_tweet_date_format      String - Date format (if humanized).
 * @var string $get_tweet_refresh_interval String - Date format (if humanized).
 */
$config['get_tweet_username']         = "BurlingtonMag";
$config['get_tweet_cache_path']       = "cache/tweets.php";
$config['get_tweet_linkify']          = TRUE;
$config['get_tweet_counter']          = 3;
$config['get_tweet_humanize']         = TRUE;
$config['get_tweet_date_format']      = 'F j';
$config['get_tweet_refresh_interval'] = 10;

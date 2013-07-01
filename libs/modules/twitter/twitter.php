<?php

function twitter_init() {
    require ('twitteroauth/twitteroauth.php');
    require 'phirehose/Phirehose.php';
    require 'phirehose/OauthPhirehose.php';
    require 'twitter.tweet.class.php';
    require 'twitter.tweet.search.class.php';
    require 'twitter.ratelimit.php';
    //require 'twitter.query.php';
    //require 'twitter.cohort.php';
    require 'twitter.user.php';
    require 'twitter.accesstoken.class.php';
    require 'twitter.list.class.php';
    require 'twitter.user.search.class.php';
    require 'twitter.user.lookup.class.php';
    require 'twitter.stream.class.php';
    require 'twitter.followers.class.php';
    require 'twitter.friends.class.php';

}

function twitter($access_token = null) {
    global $twitter_connection;
    if (!$twitter_connection || $access_token) {
        if (!$access_token) {
            if (!session()->access_token || !isset(session()->access_token['oauth_token'])) {

                redirect(get_url('/twitter/connect/'));
            }

            $at = new AccessToken(session()->access_token);
            $access_token =  session()->access_token;
        }

        if (is_object($access_token)) {
            $access_token = $access_token->data;
        }

        $twitter_connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET, $access_token['oauth_token'], $access_token['oauth_token_secret']);
    }
    return $twitter_connection;
}

function twitter_routes() {
    $paths = array();
    $paths['twitter/connect'] = array('callback' => 'twitter_connect');
    $paths['twitter/callback'] = array('callback' => 'twitter_callback');
    return $paths;
}

function twitter_connect() {
    /* Build TwitterOAuth object with client credentials. */
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET);

    /* Get temporary credentials. */
    $request_token = $connection->getRequestToken(TWITTER_OAUTH_CALLBACK);

    /* Save temporary credentials to session. */
    session(1)->oauth_token = $token = $request_token['oauth_token'];
    session(1)->oauth_token_secret = $request_token['oauth_token_secret'];

    $url = $connection->getAuthorizeURL($token);
    $header = 'Location: ' . $url;
    header($header);
    die();
}

function twitter_callback() {
    /* Create TwitteroAuth object with app key/secret and token key/secret from default phase */
    $connection = new TwitterOAuth(TWITTER_CONSUMER_KEY, TWITTER_CONSUMER_SECRET,  session()->oauth_token,  session()->oauth_token_secret);
    /* Request access tokens from twitter */
    $access_token = $connection->getAccessToken($_REQUEST['oauth_verifier']);
    $blah = new AccessToken($access_token);
    /* Save the access tokens. Normally these would be saved in a database for future use. */
    session(1)->access_token = $access_token;
    unset(session(1)->oauth_token);
    unset(session(1)->oauth_token_secret);
    session()->persist();
    message('Sucessfully connected to twitter');
    redirect(get_url('/user/'));
}

function twitter_connect_button(){

    return l('Connect with Twitter', '/twitter/connect/', 'btn btn-primary');

}

function twitter_active_user(){
    static $user;
    if(isset($user)){
        return $user;
    }
    if(session()->access_token){
        $user = new TwitterUser(session()->access_token['user_id']);
        if(!$user->exists){
            $search = new twitterUserLookup(array(session()->access_token['user_id']));
            $user = $search->fine_one();
        }
        return $user;
    }else{
        return false;
    }
}
//used by all routes that require a logged in user
function twitter_auth_callback(){
    if(sys()->cli){return true;}
    $user = twitter_active_user();
    if(!$user){
        redirect('/twitter/connect');
    }else{
        return true;
    }
}

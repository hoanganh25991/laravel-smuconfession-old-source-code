<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphObject;
use Facebook\FacebookRequestException;

class BaseController extends Controller {

	protected $fbSession = '';
	protected $fbApp = array();
	protected $dataContainer = array();
	protected $tbl_prefix = '';

	/**
	 * Setup the layout used by the controller.
	 *
	 * @return void
	 */
	protected function setupLayout()
	{
		if ( ! is_null($this->layout))
		{
			$this->layout = View::make($this->layout);
		}
	}

	public function fbInit($slug){
		$this->retrieveMetaData($slug);
		$this->fbApp = array(
			'fbappid' => $this->dataContainer['meta']['fbappid'],
			'fbappsecret' => $this->dataContainer['meta']['fbappsecret'],
			'fbpageid' => $this->dataContainer['meta']['fbpageid']);
		FacebookSession::setDefaultApplication($this->fbApp['fbappid'],$this->fbApp['fbappsecret']);
		$this->fbSession = FacebookSession::newAppSession();
	}

	public function retrieveMetaData($slug){
		$this->setCookie();
		$this->dataContainer['meta'] = array();
		$result = DB::table('page')->where('slug', $slug)->first(); 
		if(sizeof($result)>0){
			foreach ($result as $key => $value) {
				$this->dataContainer['meta'][$key] = $value;
			}
			$this->tbl_prefix = $this->dataContainer['meta']['prefix'];
		} else {
			App::abort(404);
		}
	}

	public function setCookie(){
		if(Cookie::get('CI_UID') !== false) {
			Cookie::queue('CI_UID', Uuid::generate(5, 'ci_confessing', 'da39a3ee-5e6b-4b0d-3255-bfef95601890'), 2628000);
		}
	}

	public function truncate($text, $chars = 200) {
		$oldLength = strlen($text);
		if($oldLength>$chars){
			$text = $text." ";
		    $text = substr($text,0,$chars);
		    $text = substr($text,0,strrpos($text,' '));
		    if($oldLength>strlen($text)){
		    	$text = $text."...";
		    }
		}
	    return $text;
	}

	public function retrieveUrl($string){
	    $regex = '/https?\:\/\/[^\" ]+/i';
	    preg_match($regex, $string, $matches);
	    if(empty($matches)){
	    	return false;
	    }
	    return ($matches[0]);
	}

	public function urlExist($string){
		$handle = curl_init($url);
        if (false === $handle){
            return false;
        }
        curl_setopt($handle, CURLOPT_HEADER, false);
        curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
        curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_2) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.74 Safari/537.36") ); // request as if Chrome on Yosemite
        curl_setopt($handle, CURLOPT_NOBODY, true);
        curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($handle, CURLOPT_FOLLOWLOCATION, true);
        $connectable = curl_exec($handle);
        ##print $connectable;
        curl_close($handle);
        return $connectable;
	}

	public function updatePost($slug, $postid=null, $adminid=null, $batch=false){
		$postsToCheck = array();
		/*
		triggers: 
		- individual posts
		- batch

		- individual posts
		run update script, to update old posts individually:
		limits:
		for post < 3 days old: if last update is more than 30 minutes away
		for post < 1 week old: if last update is more than 2 hours away
		for post > 1 week old: if last update is more than 6 hours away
		for post > 1 month old: if last update is more than 12 hours away
		for post > 3 months old: if last update is more than 1 day old
		for post > 6 months old: if last update is more than 3 days old
		for post > 1 year old: if last update is more than 7 days old

		-batch
		run update script, to update old posts in batches of 10% in each strata
		limits:
		for post < 1 week old: not updated in a day
		for post < 1 month old: not updated in a 1 week
		for post < 3 months old: not updated in 2 weeks
		for post < 6 months old: not update in 1 month
		for post < 1 year old: not update in 2 months

		required: post id, slug. optional: adminid
		*/
		if($batch==false && $postid!=null){
			$post = DB::table($this->tbl_prefix.'_approved')->select('approveddate','checkeddate')->where('confessionId', $postid)->first();
			$diff1 = time() - strtotime($post['approveddate']);
			$diff2 = time() - strtotime($post['checkeddate'] ? $post['checkeddate'] : -1);
			if(($diff1 < 259200 && $diff2 > 1800) || ($diff1 < 604800 && $diff2 > 7200) || ($diff1 < 2592000 && $diff2 > 21600) || ($diff1 < 7776000 && $diff2 > 43200) || ($diff1 < 15552000 && $diff2 > 86400) || ($diff1 < 15778463 && $diff2 > 259200) || ($diff1 < 31104000 && $diff2 > 604800) || ($diff1 >= 31104000 && $diff2 >= 1800)){
				$postsToCheck[] = $postid;
			}
		} elseif($batch) {
			$post = DB::table($this->tbl_prefix.'_approved')->select('confessionid')->where('checkeddate', '<', date('Y-m-d H:i:s', strtotime(time()-86400)))->orWhereNull('checkeddate')->limit(50)->get();
			foreach ($post as $key => $value) {
				$postsToCheck[] = $value['confessionid'];
			}
		}
		if(count($postsToCheck)==0){
			return 'No posts!';
		}
		$this->fbInit($slug);
		$access_token = null;
		if($adminid!=null){
			$access_token = DB::table($this->tbl_prefix.'_admin')->select('page_access_token')->where('profileId',$adminid)->first();
		} else {
			$access_token = DB::table($this->tbl_prefix.'_admin')->select('page_access_token')->orderBy('page_access_token_create_time', 'desc')->first();
		}
		$access_token = $access_token['page_access_token'];

		//create new Facebook Session using access token.
		$this->fbSession = new FacebookSession($access_token);
		try {
			$this->fbSession->validate();
		} catch (FacebookRequestException $ex) {
			// Session not valid, Graph API returned an exception with the reason.
			return 'No valid session';
		} catch (\Exception $ex) {
			// Graph API returned info, but it may mismatch the current app or have expired.
			return 'some other graph api error';
		}

		foreach ($postsToCheck as $key => $value) {
			//get facebook id from postid
			$fbid = DB::table($this->tbl_prefix.'_approved')->select('fbid', 'isDeleted')->where('confessionId', $value)->first();
			$isDeleted = $fbid['isDeleted'];
			$fbid = $fbid['fbid'];
			if($isDeleted != null || $isDeleted != 1){
				try{
					//this is a redundant but neccessary call to check if the post is deleted. just because likes and comments edges may not throw exception. bah.
					$request = new FacebookRequest(
						$this->fbSession,
						'GET',
						'/'.$fbid);
					$response = $request->execute();
					$request = new FacebookRequest(
						$this->fbSession,
						'GET',
						'/'.$fbid.'/likes?summary=true');
					$response = $request->execute();
					$likes = $response->getGraphObject()->asArray();
					$likesCount = $likes['summary']->total_count;

					$request = new FacebookRequest(
						$this->fbSession,
						'GET',
						'/'.$fbid.'/comments?summary=true');
					$response = $request->execute();
					$comments = $response->getGraphObject()->asArray();
					$commentsCount = $comments['summary']->total_count;
					$update = DB::table($this->tbl_prefix.'_approved')->where('confessionId', $value)->update(array(
						'fbLikeCount'=>$likesCount,
						'fbCommentCount'=>$commentsCount,
						'checkeddate'=>date('Y-m-d H:i:s')));
				} catch (FacebookRequestException $e){
					// most likely to be deleted. remove from 
					$update = DB::table($this->tbl_prefix.'_approved')->where('confessionId', $value)->update(array(
						'isDeleted' => 1,
						'checkeddate'=>date('Y-m-d H:i:s')));
				}
			}			
		}
		return 'done updating, i think';
	}
}

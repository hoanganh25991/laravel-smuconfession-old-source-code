<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\FacebookRequestException;

class AdminController extends BaseController {

	protected $fbSession = '';
	protected $fbApp = array();
	protected $layout = 'master';


	public function getIndex($slug){
		$this->retrieveMetaData($slug);
		$this->fbInit($slug);
		if(!$this->verifyFbAuth()){
			return Redirect::to(url($slug));
		}
		$this->retrievePendingConfessions();
		$this->layout->content = View::make('post/admin')->with('data', $this->dataContainer);
	}

	public function retrievePendingConfessions(){
		// date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 5, date("Y")))
		$results = DB::select(DB::raw("SELECT * FROM ".$this->tbl_prefix."_confession WHERE `timestamp` > '".date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 7, date("Y")))." 00:00:00' AND reject = 0 AND `id` NOT IN (SELECT confessionid FROM `".$this->tbl_prefix."_approved` WHERE `approveddate` > '".date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 14, date("Y")))." 00:00:00') ORDER BY `timestamp` ASC"));
		$this->dataContainer['pending'] = $results;
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

	public function postAction(){
		return json_encode(Input::all());
	}

	public function getLogin($slug){
		$this->fbInit($slug);
		$helper = new LaravelFacebookRedirectLoginHelper(url($slug.'/0/login-fb-callback'));
		$scope = array('public_profile', 'email','manage_pages', 'publish_actions', 'user_friends');
	    return Redirect::to($helper->getLoginUrl($scope));
	}

	public function getLoginFbCallback($slug){
		$this->fbInit($slug);
		$helper = new LaravelFacebookRedirectLoginHelper(url($slug.'/0/login-fb-callback'));
		$this->fbSession = $helper->getSessionFromRedirect();
		try {
			//get extended Token
			$this->fbSession = $this->fbSession->getLongLivedSession($this->fbApp['fbappid'], $this->fbApp['fbappsecret']);
			$userToken = $this->fbSession->getToken();
			//get page Token
			$pageToken = '';
			$accountsResponse = (new FacebookRequest($this->fbSession, 'GET', '/me/accounts'))->execute()->getGraphObject()->asArray();
			
			foreach ($accountsResponse['data'] as $key => $value) {
				if($value->id == $this->fbApp['fbpageid']){
					$pageToken = $value->access_token;
					break;
				}
			}
			if(empty($pageToken)){
				return Redirect::to(url($slug.''));
			}
			//store user information from /me
			$meResponse = (new FacebookRequest($this->fbSession, 'GET', '/me'))->execute()->getGraphObject()->asArray();
			//store admin information from page/roles
			$fbPageSession = new FacebookSession($pageToken);
			$pageRolesResponse = (new FacebookRequest($fbPageSession, 'GET', '/'.$this->fbApp['fbpageid'].'/roles'))->execute()->getGraphObject()->asArray();
			$pageRoles = array();
			foreach ($pageRolesResponse['data'] as $key => $value) {
				$pageRoles[$value->id] = array(
					'id' => $value->id,
					'name' => $value->name,
					'role' => $value->role);
			}
			$ids = array_keys($pageRoles);
			$current_ids = DB::table($this->tbl_prefix.'_admin')->select('profileId')->get();
			// check for new admin
			$current_id = array();
			$new_id = array();
			$old_id = array();
			if(sizeof($current_ids)==0){
				// all new admin
				$new_id = $ids;
			} else {
				// check for current admin
				// check for old admin
				foreach($current_ids as $key => $value) {
					$pid = $value['profileId'];
					if(array_search($pid, $ids)){
						$current_id[] = $pid;
					} else {
						$old_id[] = $pid;
					}
				}
				$new_id = array_diff($ids, $current_id, $old_id);
			}
			if(sizeof($old_id)>0){
				foreach ($old_id as $key => $value) {
					DB::table($this->tbl_prefix.'_admin')
					->where('profileId', $value)
					->update(array(
						'is_active'=>0
						));
				}
			}
			if(sizeof($current_id)>0){
				foreach ($current_id as $key => $value) {
					DB::table($this->tbl_prefix.'_admin')
					->where('profileId', $value)
					->update(array(
						'role'=> $pageRoles[$value]['role'],
						'is_active' => 1
						));
					if($value == $meResponse['id']){
						DB::table($this->tbl_prefix.'_admin')
						->where('profileId', $value)
						->where('updated_time', '<', date("Y-m-d H:i:s", strtotime($meResponse['updated_time'])))
						->update(array(
							'email' => $meResponse['email'],
							'first_name' => $meResponse['first_name'],
							'gender' => $meResponse['gender'],
							'last_name' => $meResponse['last_name'],
							'link' => $meResponse['link'],
							'locale' => $meResponse['locale'],
							'name' => $meResponse['name'],
							'timezone' => $meResponse['timezone'],
							'updated_time' => date("Y-m-d H:i:s", strtotime($meResponse['updated_time'])),
							'verified' => $meResponse['verified'],
							));
						DB::table($this->tbl_prefix.'_admin')
						->where('profileId', $value)
						->update(array(
							'page_access_token' => $pageToken,
							'page_access_token_create_time' => date("Y-m-d H:i:s")
							));
					}
				}
			}
			if(sizeof($new_id)>0){
				foreach ($new_id as $key => $value) {
					DB::table($this->tbl_prefix.'_admin')
					->where('profileId', $value)
					->update(array(
						'is_active' => 0
						));
				}
			}
			$this->setFbAuth($meResponse['id'], $pageToken);
			return Redirect::to(url($slug.'/0'));
		} catch (FacebookRequestException $ex) {
		  print_r($ex);
		} catch (\Exception $ex) {
		  print_r($ex);
		}
	}

	public function setFbAuth($id,$pageToken){
		$accessKey = Crypt::encrypt(json_encode(array($id,$pageToken)));
		Session::put('fbid', $id);
		Session::put('fbAccessKey', $accessKey);
	}

	public function getLogout($slug){
		if (Session::has('fbid') && Session::has('fbAccessKey')) {
			Session::forget('fbid');
			Session::forget('fbAccessKey');
		}
		return Redirect::to(url($slug));
	}

	public function verifyFbAuth(){
		if(Session::has('fbid')&&Session::has('fbAccessKey')){
			$decodeKey = json_decode(Crypt::decrypt(Session::get('fbAccessKey')));
			// get from database and compare
			$user = DB::table($this->tbl_prefix.'_admin')->select('page_access_token')->where('profileId',Session::get('fbid'))->first();
			if(sizeof($user)>0){
				if($user['page_access_token'] == $decodeKey[1]) {
					try{
						//attempt authorisation
						$this->fbSession = new FacebookSession($user['page_access_token']);
						return true;
					} catch (FacebookRequestException $e){
						return false;
					}
				}
			}
		}
		return false;
	}
}
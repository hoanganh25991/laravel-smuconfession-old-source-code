<?php

use Facebook\FacebookSession;
use Facebook\FacebookRequest;
use Facebook\GraphObject;
use Facebook\FacebookRequestException;

class AdminController extends BaseController {

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
		$results = DB::select(DB::raw("SELECT * FROM ".$this->tbl_prefix."_confession WHERE `timestamp` > '".date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 30, date("Y")))." 00:00:00' AND reject = 0 AND `id` NOT IN (SELECT confessionid FROM `".$this->tbl_prefix."_approved` WHERE `approveddate` > '".date('Y-m-d', mktime(0, 0, 0, date("m") , date("d") - 30, date("Y")))." 00:00:00') ORDER BY `timestamp` ASC"));
		$this->dataContainer['pending'] = $results;
	}

	public function postAction($slug){
		$this->fbInit($slug);
		if($this->verifyFbAuth()){
			$id = Input::get('id');
			$action = Input::get('action');
			$confession = Input::get('confession');
			
			switch ($action) {
				case 'approve':
					$this->publishToFacebook($confession, $id, $slug);
					break;
				case 'decline':
					$this->reject($id);
					break;
				default:
					return Response::json(array('msg'=>'No such action'), 400);
					break;
			}
		}
		return Response::json(array('msg'=>'Error in posting'), 400);
	
	}

	public function publishToFacebook($text, $id, $slug){
		$check = DB::table($this->tbl_prefix.'_approved')->select('confessionid')->where('confessionid', $id)->count();
		if($check > 0){
			return Responese::json(array(
				'status' => true, 
				'msg' => 'Approved by another admin'), 400);
		}
		$pageid = $this->dataContainer['meta']['fbpageid'];
		$textToSend = "#".$id.PHP_EOL."==========".PHP_EOL.$text.PHP_EOL."==========".PHP_EOL."#".$id;
		$textToSend .= ': http://confessing.in/'.$slug.'/'.$id;
		$textToSend .= PHP_EOL.PHP_EOL."Confess at: http://confessing.in/smusg/confess";
		// approve in database first, then send to facebook, then update with facebok id.
		DB::table($this->tbl_prefix.'_approved')->insert(array(
			'confessionid' => $id,
			'fbText' => $text,
			'adminId' => Session::get('fbid')));
		// then send to facebook, then update with facebook id.
		$url = $this->retrieveUrl($text);
		$toSend = array(
			'message' => $textToSend
			);
		if($url == true){
			//check if $url exists:
			if($this->urlExist($url) == true){
				$toSend['link'] = $url;
			}
		}
		try{
			$response = (new FacebookRequest(
				$this->fbSession,
				'POST',
				'/'.$pageid.'/feed',
				$toSend
				))->execute()->getGraphObject();
			$status = (new FacebookRequest(
				$this->fbSession,
				'GET',
				'/'.$response->getProperty('id')
				))->execute()->getGraphObject()->asArray();
			$fbLink = $status['actions'][0]['link'];
			DB::table($this->tbl_prefix.'_approved')->where('confessionid', $id)->update(array(
				'fbUrl' => $fbLink,
				'fbid' => $response->getProperty('id')));
		} catch (FacebookRequestException $ex){
			return Response::json(array(
				'status' => false,
				'code' => $ex->getCode(),
				'msg' => $ex->getMessage()), 400);
		}
		return Response::json(array(
			'status' => true,
			'msg' => 'Approved '.$id));
	}

	public function reject($id){
		DB::table($this->tbl_prefix.'_confession')->where('id', $id)->update(array(
			'reject' => Session::get('fbid'),
			'rejectDate' => gmdate("Y-m-d H:i:s", time())
			));
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
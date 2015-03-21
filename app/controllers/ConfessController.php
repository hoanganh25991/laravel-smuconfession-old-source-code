<?php

class ConfessController extends \BaseController {

    /**
     * Redirect get requests to slug
     * 
     */

    public function index($slug){
        return Redirect::to($slug);
    }

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store($slug){
		$this->retrieveMetaData($slug);
		$rules = array(
			'confessor' => 'honeypot',
			'confess_time' => 'required|honeytime:5',
			'confessing-in' => 'required|existing',
			'g-recaptcha-response' => 'required|recaptcha',
			);
		//custom Validator rule to check if the confession is a repeated post.
		Validator::extend('existing', function($attribute, $value, $parameters){
			$result = DB::table($this->tbl_prefix.'_confession')->select('id')->where('confession', $value);
			if($result->count() > 0){
				throw new \Exception("There is an existing confession that is similar to the one you are inputting. Please confess again.");
			}
			return true;
		});
		$validator = Validator::make(Input::get(), $rules);
		if($validator->fails()){
			return Redirect::to($slug)
				->withErrors($validator)
				->withInput(Input::get());
		} else {
			$receive = array(
				'confession' => Input::get('confessing-in'),
				'ipaddress' => Request::getClientIp(),
				'cookieID' => Cookie::get('CI_UID'),
				'version' => 3,
			);
			$user_agent = $_SERVER['HTTP_USER_AGENT'];
			$result = DB::table('useragentstring')->select('id')->where('string', $user_agent);
			if($result->count()>0){
				$receive['useragentid'] = $result->first()->id;
			} else {
				$uaid = DB::table('useragentstring')->insertGetId(array('string'=> $user_agent));
				$receive['useragentid'] = $uaid;
			}
			$result = DB::table($this->tbl_prefix.'_confession')->insertGetId($receive);
			return Redirect::to($slug)->withSuccess('Confession received.');
		}
		return Redirect::to($slug)->withError('There is an error in application. Please try again later');
	}

}

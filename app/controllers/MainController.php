<?php

class MainController extends BaseController {

	protected $layout = 'master';
	protected $dataContainer = array();

	public function decideRoute($slug,$postid=null,$pageid=null){
		$postid = (int) $postid;
		if($postid==null || $postid=='page'){
			//route to main view
			$this->showMain($slug,$pageid);
		} elseif($postid==0) {
			//route to admin view
			$this->showAdmin($slug);
		} elseif($postid>0){
			//route to individual post view
			$this->showIndividualPost($slug,$postid);
		} 
	}

	public function showMain($slug,$pageid=null){
		// load main page view.
		$this->layout->content = View::make('post/main')->with('slug','smusg');
	}

	public function showIndividualPost($slug,$postid){
		// load individual posts.	
		$this->layout->content = View::make('post/single')->with('slug','smusg');
	}

	public function showAdmin($slug){
		// load admin
	}

	public function truncate($text, $chars = 25) {
	    $text = $text." ";
	    $text = substr($text,0,$chars);
	    $text = substr($text,0,strrpos($text,' '));
	    $text = $text."...";
	    return $text;
	}

}
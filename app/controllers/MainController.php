<?php

class MainController extends BaseController {

	protected $layout = 'master';
	protected $metaContainer = array();
	protected $limit = 20;

	public function decideRoute($slug,$postid=null,$pageid=1){
		$this->retrieveMetaData($slug);
		$postid = (int) $postid;
		if($postid==null || $postid=='page'){
			//route to main view
			$this->showMain($slug,$pageid);
		} elseif($postid>0){
			//route to individual post view
			$this->showIndividualPost($slug,$postid);
		} else {
			App::abort(404);
		}
	}

	public function showMain($slug,$pageid=1){
		// load main page view.
		$this->retrieveAdditionalMeta($slug,$pageid);
		$this->retrieveApproved($pageid);
		$this->layout->content = View::make('post/main')->with('data', $this->dataContainer);
	}

	public function showIndividualPost($slug,$postid){
		// load individual posts.	
		$this->retrieveSingleDetails($postid);
		$this->layout->content = View::make('post/single')->with('data', $this->dataContainer);
	}

	public function retrieveAdditionalMeta($slug,$currentpage=1,$limit=null){
		if ($limit == null){
			$limit = $this->limit;
		}
		$results = DB::select(DB::raw("SELECT confessionid, replace(fbText, '\n', '<br>') as fbText, fbUrl, fbid, approveddate FROM ".$this->tbl_prefix.'_approved'." WHERE isDeleted IS NULL ORDER BY approveddate DESC"));
		$count = sizeof($results);
		$maxpage = ceil($count/$limit);
		$this->dataContainer['meta']['maxpage'] = $maxpage;
		$this->dataContainer['meta']['currentpage'] = $currentpage;
	}

	public function retrieveApproved($pageid=1,$limit=null) {
		if($limit==null){
			$limit = $this->limit;
		}
		$start = $limit*($pageid-1);
		$results = DB::select(DB::raw("SELECT confessionid, replace(fbText, '\n', '<br>') as fbText, fbUrl, fbid, approveddate FROM ".$this->tbl_prefix.'_approved'." WHERE isDeleted IS NULL ORDER BY approveddate DESC limit ".$start.",".$limit));
		foreach ($results as $key => $value) {
			$results[$key]['fbText'] = $this->truncate($results[$key]['fbText']);
		}
		$this->dataContainer['approved'] = $results;
	}

	public function retrieveSingleDetails($postid){
		$results = DB::table($this->tbl_prefix.'_approved')->where('confessionid',$postid)->first();
		if($results['isDeleted'] == 1){
			App::abort(404);
		}
		if(sizeof($results)!=0){
			$this->dataContainer['single'] = $results;
			$admin = DB::table($this->tbl_prefix.'_admin')->select('nick')->where('profileId',$results['adminid'])->first();
			$this->dataContainer['single']['adminid'] = $admin['nick'];
			$submit = DB::table($this->tbl_prefix.'_confession')->select('timestamp')->where('id',$postid)->first();
			$this->dataContainer['single']['submitdate'] = $submit['timestamp'];
			$previous = DB::table($this->tbl_prefix.'_approved')->select('confessionid')->where('approveddate', '<', $results['approveddate'])->whereNull('isDeleted')->orderby('approveddate', 'desc')->take(1)->first();
			$next = DB::table($this->tbl_prefix.'_approved')->select('confessionid')->where('approveddate', '>', $results['approveddate'])->whereNull('isDeleted')->orderby('approveddate','asc')->take(1)->first();
			$this->dataContainer['single']['previous'] = $previous['confessionid'];
			$this->dataContainer['single']['next'] = $next['confessionid'];
		} else {
			App::abort(404);
		}
	}

	

}
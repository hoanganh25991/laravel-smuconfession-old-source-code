<?php

class BaseController extends Controller {


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

	public function retrieveMetaData($slug){
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


}

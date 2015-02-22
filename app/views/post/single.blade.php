@extends('master')

@section('title')
Confessing In {{$data['meta']['name']}}@stop

@section('meta_app_id')
{{$data['meta']['fbappid']}}@stop

@section('meta_og_tags')
<meta property="og_title" content="{{$data['meta']['name']}} ID: {{$data['single']['confessionid']}}"/>
<meta property="og_site_name" content="Confessing In Network" />
<meta property="og_description" content="{{$data['single']['fbText']}}" />
<meta property="og_url" content="{{Request::url()}}" />
@stop

@section('content')
<div class="row">
	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
    	<div class="fb-post" data-href="{{$data['single']['fbUrl']}}" data-width="750px"></div>
		<div class="readmore">
			<p>Submitted: <span id="submitted-date">{{$data['single']['submitdate']}}</span> | Published: <span id="published-date">{{$data['single']['approveddate']}}</span> | Approved by: <span id="approved-by">{{$data['single']['adminid']}}</span></p>
		</div>
	</div>
</div>
<div class="row"> 
	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
		<nav>
			<ul class="pager">
				@if(empty($data['single']['previous'])) <li class="previous disabled"><a href="#"> @else <li class="previous"><a href="{{url($data['meta']['slug'].'/'.$data['single']['previous'])}}"> @endif<span aria-hidden="true">&larr;</span> Previous</a></li>
    			@if(empty($data['single']['next'])) <li class="next disabled"><a href="#"> @else <li class="next"><a href="{{url($data['meta']['slug'].'/'.$data['single']['next'])}}">@endif Next <span aria-hidden="true">&rarr;</span></a></li>
			</ul>
		</nav>
	</div>
</div>
<div class="row">
	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
	<div class="fb-comments" data-href="{{Request::url()}}" data-numposts="5" data-colorscheme="light" data-width="100%"></div>
	</div>
</div>
@stop
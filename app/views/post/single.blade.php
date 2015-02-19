@extends('master')

@section('title')
Confessing In {{$slug}}
@stop

@section('meta_app_id')
148520945306244 @stop

@section('content')
<div class="row">
	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
    	<div class="fb-post" data-href="https://www.facebook.com/SMUConfessionsPage/posts/784028918351614" data-width="750px"></div>
		<div class="readmore">
			<p>Submitted: <span id="submitted-date"></span> | Published: <span id="published-date"></span> | Approved by: <span id="approved-by"></span></p>
		</div>
	</div>
</div>
<div class="row"> 
	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
		<nav>
			<ul class="pager">
				<li class="previous"><a href="#"><span aria-hidden="true">&larr;</span> Previous</a></li>
    			<li class="next"><a href="#">Next <span aria-hidden="true">&rarr;</span></a></li>
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
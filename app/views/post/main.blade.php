@extends('master')

@section('title')
Confessing In {{$slug}}
@stop

@section('meta_app_id')
148520945306244 @stop

@section('content')

    <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
            <div class="row"> 
            {{ Form::open(array('url' => 'confess.submit', 'method' => 'post', 'id' => 'confess-form')) }}
            <div class="col-xs-12 text-center">
                {{ Form::label('confessing-in', 'Confessing In '.'smusg'.':')}}
            </div>
            <div class="col-xs-12"> 
                {{ Form::textarea('confessing-in')}}
            </div>
            <div class="col-xs-12 text-center"> 
                {{ Form::submit('Confess!', array('id'=>'btn-confess',))}}
            </div>
            {{ Form::close() }}
            </div>
        </div>
    </div>
    <div class="row" id="confessions-header">
    	<h3 class="col-xs-12 col-md-8 col-md-offset-2">Past confessions</h3>
    </div>
    <div class="row" id="confessions">
    	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
    	#26811
==========
While the confessions lately have been pretty heated, am I the only one here who is more interested in finding places around the area to eat at? So.. Someone, anyone, leave a comment for my ever so hungry tummy! Hehe.
==========
#26811
Confess at: http://confessing.in/smusg/confess
			<div class="readmore"><p class="text-right">Published: . On Facebook: 0 likes, 0 comments. On site: 0 likes, 0 comments. Read more</p></div>
    	</div>
    	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
    	#26811
==========
While the confessions lately have been pretty heated, am I the only one here who is more interested in finding places around the area to eat at? So.. Someone, anyone, leave a comment for my ever so hungry tummy! Hehe.
==========
#26811
Confess at: http://confessing.in/smusg/confess
			<div class="readmore"><p class="text-right"><a href="{{url('smusg/1')}}">Published: . On Facebook: 0 likes, 0 comments. On site: 0 likes, <fb:comments-count href={{url('smusg/1')}}/></fb:comments-count> comments. Read more</a></p></div>
    	</div>
    </div>

@stop

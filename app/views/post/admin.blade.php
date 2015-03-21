@extends('master')

@section('title')
Confessing In {{$data['meta']['name']}}@stop

@section('meta_app_id')
{{$data['meta']['fbappid']}}@stop

@section('moreJS')
<script src="{{url('js/admin.js')}}"></script>
@stop

@section('header_link')
{{url($data['meta']['slug'])}}@stop

@section('header_img')
{{$data['meta']['header_img']}}@stop

@section('content')
    <!--
    <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
            <div class="row"> 
            {{ Form::open(array('url' => 'confess.submit', 'method' => 'post', 'id' => 'confess-form')) }}
            <div class="col-xs-12 text-center">
                {{ Form::label('confessing-in', 'Confess in '.$data['meta']['institution'].':')}}
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
    -->
    <div class="row" id="confessions-header">
    	<h3 class="col-xs-12 col-md-8 col-md-offset-2">Confessions</h3>
    </div>
    <div class="row">
    	@foreach($data['pending'] as $pending)
		<div class="col-xs-12 col-md-8 col-md-offset-2 white-template" id="box-{{$pending['id']}}">
			<div contenteditable class="contentedit" id="content-{{$pending['id']}}">{{$pending['confession']}}</div>
			<div class="readmore row">
				<div class="col-xs-12 col-md-8">#{{$pending['id']}} {{$pending['timestamp']}} {{$pending['ipaddress']}}</div>
				<div class="col-xs-12 col-md-4"><p class='text-center'><a class="action" data-action="approve" data-id="{{$pending['id']}}">Approve</a> | <a class="action" data-action="decline" data-id="{{$pending['id']}}">Decline</a></p></div>
			</div>
		</div>
		@endforeach
	</div>
@stop
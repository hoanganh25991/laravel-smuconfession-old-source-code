@extends('master')

@section('title')
Confessing In {{$data['meta']['name']}}@stop

@section('meta_app_id')
{{$data['meta']['fbappid']}}@stop

@section('content')

    <div class="row">
        <div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
            <div class="row"> 
            {{ Form::open(array('url' => $data['meta']['slug'].'/confess', 'method' => 'post', 'id' => 'confess-form')) }}
                {{ Form::honeypot('confessor', 'confess_time') }}
                <div class="col-xs-12 text-center">
                    {{ Form::label('confessing-in', 'Confess in '.$data['meta']['institution'].':')}}
                </div>
                <div class="col-xs-12"> 
                    {{ Form::textarea('confessing-in', Input::old('confessing-in'))}}
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12 col-md-6 text-right">
                    {{ Form::captcha() }}
                </div>
                <div class="col-xs-12 col-md-6"> 
                    {{ Form::submit('Confess!', array('id'=>'btn-confess',))}}
                </div>
            {{ Form::close() }}
            </div>
            <div class="row">
                @if ($errors->has())
                <div class="col-xs-12 bg-warning">
                    @foreach ($errors->all() as $error)
                    <p> {{ $error }}</p>
                    @endforeach
                </div>
                @elseif (isset($success))
                <div class="col-xs-12 bg-success"> 
                    <p> {{$success}} </p>
                </div>
                @endif
            </div>
        </div>
    </div>
    <div class="row" id="confessions-header">
    	<h3 class="col-xs-12 col-md-8 col-md-offset-2">Past confessions</h3>
    </div>
    <div class="row" id="confessions">
    	@foreach($data['approved'] as $approved)
    	<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
    		{{$approved['fbText']}}
    		<div class="readmore"><p class="text-right"><a href="{{url($data['meta']['slug'].'/'.$approved['confessionid'])}}">Published: {{$approved['approveddate']}}. On Facebook: 0 likes, 0 comments. On site: <fb:comments-count href={{url($data['meta']['slug'].'/'.$approved['confessionid'])}}/></fb:comments-count> comments. Read more</a></p></div>
    	</div>
    	@endforeach
    </div>
    <div class="row"> 
		<div class="col-xs-12 col-md-8 col-md-offset-2 white-template">
			<nav>
				<ul class="pager">
					@if ($data['meta']['currentpage']==1) <li class="previous disabled"><a href="#"> @else <li class="previous"><a href="{{url($data['meta']['slug'].'/page/'.($data['meta']['currentpage']-1))}}"> @endif <span aria-hidden="true">&larr;</span> Previous</a></li>
	    			@if ($data['meta']['maxpage']==$data['meta']['currentpage']) <li class="next disabled"><a href="#"> @else <li class="next"><a href="{{url($data['meta']['slug'].'/page/'.($data['meta']['currentpage']+1))}}"> @endif Next <span aria-hidden="true">&rarr;</span></a></li>
				</ul>
			</nav>
		</div>
	</div>
@stop

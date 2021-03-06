@extends('layouts.main.simplebox')

@section('title', "Register")

@section('body')
{!! Form::open([
	'url'    => url('/cp/auth/register),
	'method' => "PUT",
	'id'     => "create-form",
	'class'  => "form-auth",
]) !!}
	@include($c->template('panel.auth.register.form'))
	
	<div class="field row-submit">
		<button type="submit" class="field-submit">@lang('panel.field.register')</button>
	</div>
@endsection

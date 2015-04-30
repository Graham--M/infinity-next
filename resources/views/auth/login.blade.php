@extends('layouts.main')

@section('title', "Login")

@section('content')
<main>
	<section class="auth-form grid-container smooth-box">
		@if (count($errors) > 0)
			<ul class="alerts grid-100">
			@foreach ($errors->all() as $error)
				<li class="alert">{{ $error }}</li>
			@endforeach
			</ul>
		@endif
		
		<div class="grid-100">
			<form class="form-auth" role="form" method="POST" action="{{ url('/cp/auth/login') }}">
				<input type="hidden" name="_token" value="{{ csrf_token() }}" />
				
				<fieldset class="form-fields">
					<legend class="form-legend">Login</legend>
					
					<div class="field row-username">
						<label class="field-label" for="username">Username</label>
						<input class="field-control" id="username" name="username" type="text" maxlength="64" />
					</div>
					
					<div class="field row-password">
						<label class="field-label" for="password">Password</label>
						<input class="field-control" id="password" name="password" type="password" maxlength="255" />
					</div>
					
					<div class="field row-remember">
						<a href="{{ url('/cp/password/email') }}">Forgot Your Password?</a>
					</div>
					
					<div class="field row-submit">
						<button type="submit" class="field-submit">Login</button>
						<label class="field-label-inline"><input type="checkbox" name="remember"/> Remember Me</label>
					</div>
				</fieldset>
			</form>
		</div>
	</section>
</main>
@endsection

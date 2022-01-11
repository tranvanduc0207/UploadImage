@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                    <div class="row">
                        <div class="col-md-6">
                            <a class="btn btn-secondary" href="https://www.dropbox.com/oauth2/authorize?client_id=g0dpgfzmgjshnlm&token_access_type=offline&response_type=code&redirect_uri=http://localhost/create">Start</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

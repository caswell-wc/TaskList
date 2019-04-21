@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">Your Token</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif
                    <div>The token for <span class="text-info">{{ $user->name }}</span> is:</div>
                    <div class="text-info">{{ $user->api_token }}</div>
                    <div class="text-danger">*This is your private key. Do not share it with anyone.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

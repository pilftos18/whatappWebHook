<!-- resources/views/auth/passwords/email.blade.php -->
@extends('layout')

@section('content')
<main id="main">
    <div class="row">
        <div class="col-lg-12 margin-tb">
            <div class="pagetitle justify-content-between d-flex">
                <h1>Reset Password</h1>
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger">
            <strong>Error!</strong> Please check the form fields.<br><br>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <?php 
        // echo "asdfasd<pre>"; print_r($email);die;
        $emailId = $email;
        if(Session::get('data.userRole') === 'user')
        {
            $emailId = Session::get('data.userEmail');
        }

    ?>
    @if ($message = Session::get('success'))
    <div class="alert alert-success">
        <p>{{ $message }}</p>
    </div>
    @endif
    <div class="card pt-4">
        <div class="card-header">{{ __('Reset Password') }}</div>

        <div class="card-body">
            @if (session('status'))
                <div class="alert alert-success" role="alert">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf
                <div class="row">
                    <div class="col-lg-6">
                        <div class="mb-4">
                            <label class="form-label" for="email">{{ __('E-Mail Address') }}</label>
                            <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"
                                name="email" value="{{ $emailId }}" required autocomplete="email" autofocus>

                            @error('email')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ $message }}</strong>
                            </span>
                            @enderror
                        </div>
                        <div class="mb-4 mb-0">
                            <button type="submit" class="btn btn-primary">
                                {{ __('Send Reset Link') }}
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</main>
@endsection
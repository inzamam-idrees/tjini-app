@extends('admin.layouts.authapp')

@section('content')
<div class="auth-main">
  <div class="auth-wrapper v3">
    <div class="auth-form justify-content-evenly">
      <div class="auth-header">
        <a href="#"><img src="{{ asset('public/assets/images/tjiniapp-logo-dark.png') }}" alt="img" style="max-width: 200px;"></a>
      </div>
      <div class="card my-5">
        <div class="card-body">
          <div class="d-flex justify-content-between align-items-end mb-4">
            <h3 class="mb-0"><b>Login</b></h3>
            <a href="#" class="link-primary">Don't have an account?</a>
          </div>

          {{-- Login form --}}
          <form method="POST" action="{{ route('login.attempt') }}">
            @csrf
            <div class="form-group mb-3">
              <label class="form-label">Email Address</label>
              <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" placeholder="Email Address" value="{{ old('email') }}" autofocus>
              @error('email')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
            <div class="form-group mb-3">
              <label class="form-label">Password</label>
              <input type="password" name="password" class="form-control @error('password') is-invalid @enderror" placeholder="Password">
              @error('password')
                <span class="invalid-feedback" role="alert"><strong>{{ $message }}</strong></span>
              @enderror
            </div>
            <div class="d-flex mt-1 justify-content-between">
              <div class="form-check">
                <input class="form-check-input input-primary" name="remember" type="checkbox" id="customCheckc1" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label text-muted" for="customCheckc1">Keep me sign in</label>
              </div>
              <h5 class="text-secondary f-w-400">Forgot Password?</h5>
            </div>
            <div class="d-grid mt-4">
              <button type="submit" class="btn btn-primary">Login</button>
            </div>
          </form>

          <!-- <div class="saprator mt-3">
            <span>Login with</span>
          </div>
          <div class="row">
            <div class="col-4">
              <div class="d-grid">
                <button type="button" class="btn mt-2 btn-light-primary bg-light text-muted">
                  <img src="{{ asset('public/assets/images/authentication/google.svg') }}" alt="img"> <span class="d-none d-sm-inline-block"> Google</span>
                </button>
              </div>
            </div>
            <div class="col-4">
              <div class="d-grid">
                <button type="button" class="btn mt-2 btn-light-primary bg-light text-muted">
                  <img src="{{ asset('public/assets/images/authentication/twitter.svg') }}" alt="img"> <span class="d-none d-sm-inline-block"> Twitter</span>
                </button>
              </div>
            </div>
            <div class="col-4">
              <div class="d-grid">
                <button type="button" class="btn mt-2 btn-light-primary bg-light text-muted">
                  <img src="{{ asset('public/assets/images/authentication/facebook.svg') }}" alt="img"> <span class="d-none d-sm-inline-block"> Facebook</span>
                </button>
              </div>
            </div>
          </div> -->
        </div>
      </div>
      <div class="auth-footer row d-none">
        <!-- <div class=""> -->
        <div class="col my-1">
          <p class="m-0">Copyright © <a href="https://inzamam-idrees.netlify.app" target="_blank">Inzamam Idrees</a></p>
        </div>
        <div class="col-auto my-1">
          <ul class="list-inline footer-link mb-0">
            <li class="list-inline-item"><a href="#">Home</a></li>
            <li class="list-inline-item"><a href="#">Privacy Policy</a></li>
            <li class="list-inline-item"><a href="#">Contact us</a></li>
          </ul>
        </div>
        <!-- </div> -->
      </div>
    </div>
  </div>
</div>
@endsection
@extends('admin.layouts.app')


@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.users.index', $role) }}">{{ $title }}</a></li>
<li class="breadcrumb-item" aria-current="page">{{ isset($user) ? 'Edit' : 'Create' }}</li>
@endsection

@section('content')
<div class="row">
    <!-- [ Form Validation ] start -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>{{ $title }}</h5>
            </div>
            <div class="card-body">
                <form class="validate-me" id="validate-me" data-validate method="POST" action="{{ isset($user) ? route('admin.users.update', [$role, $user->id]) : route('admin.users.store', $role) }}" enctype="multipart/form-data">
                    @csrf

                    @if(isset($user))
                        @method('PUT')
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">First Name:</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="first_name" id="first_name" required value="{{ old('first_name', $user->first_name ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">Last Name:</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="last_name" id="last_name" required value="{{ old('last_name', $user->last_name ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">Email:</label>
                        <div class="col-lg-6">
                            <input
                                type="email"
                                name="email"
                                id="email"
                                class="form-control"
                                data-bouncer-message="The domain portion of the email address is invalid (the portion after the @)."
                                required
                                value="{{ old('email', $user->email ?? '') }}"
                            />
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">School:</label>
                        <div class="col-lg-6">
                            <select class="form-control" name="school_id" id="school_id" required>
                                <option label="select"></option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id', $user->school_id ?? '') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($role == 'parent')
                        <div class="form-group row align-items-center">
                            <label class="col-lg-4 col-form-label text-lg-end">Is Primary:</label>
                            <div class="col-lg-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary" required>
                                    <label class="form-check-label" for="is_primary"> Is Primary </label>
                                </div>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Child Name:</label>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="child_name" id="child_name" required value="{{ old('child_name', $user->child_name ?? '') }}">
                            </div>
                        </div>
                    @endif

                    @if($role == 'staff')
                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Role:</label>
                            <div class="col-lg-6">
                                <select class="form-control" name="role" id="role" required>
                                    <option label="select"></option>
                                    <option value="viewer" {{ old('role', $user->role ?? '') == 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    <option value="dispatcher" {{ old('role', $user->role ?? '') == 'dispatcher' ? 'selected' : '' }}>Dispatcher</option>
                                </select>
                            </div>
                        </div>
                    @endif

                    @if(empty($user))
                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Password</label>
                            <div class="col-lg-6">
                            <input
                                type="password"
                                class="form-control"
                                name="password"
                                id="password"
                                data-bouncer-message="Please choose a password that includes at least 1 uppercase character, 1 lowercase character, and 1 number."
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?!.*\s).*"
                                required
                            >
                            <small class="form-text text-muted">At least 1 uppercase character, 1 lowercase character, and 1 number</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Confirm Password</label>
                            <div class="col-lg-6">
                                <input
                                    type="password"
                                    name="confirm_password"
                                    id="confirm-password"
                                    data-bouncer-match="#password"
                                    class="form-control"
                                    data-bouncer-mismatch-message="Your passwords do not match."
                                    required
                                >
                                <small class="form-text text-muted">must match the field above</small>
                            </div>
                        </div>
                    @endif
                    
                    <div class="form-group row mb-0">
                        <div class="col-lg-4 col-form-label"></div>
                        <div class="col-lg-6">
                            <input type="submit" class="btn btn-primary" value="{{ isset($user) ? 'Update' : 'Submit' }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <!-- [ Form Validation ] end -->
</div>
@endsection

@push('scripts')
<script src="{{ asset('public/assets/js/plugins/bouncer.min.js') }}"></script>
<script src="{{ asset('public/assets/js/pages/form-validation.js') }}"></script>
@endpush
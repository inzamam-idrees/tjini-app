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
                                <option value="" disabled selected>Select School</option>
                                @foreach ($schools as $school)
                                    <option value="{{ $school->id }}" {{ old('school_id', $user->school_id ?? '') == $school->id ? 'selected' : '' }}>{{ $school->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    @if($role == 'parent')
                        <div class="form-group row align-items-center">
                            <label class="col-lg-4 col-form-label text-lg-end" for="is_primary">Is Primary:</label>
                            <div class="col-lg-6">
                                <div class="form-check">
                                    <input type="hidden" name="is_primary" value="0">
                                    <input class="form-check-input" type="checkbox" name="is_primary" id="is_primary" value="1" {{ old('is_primary', $user->is_primary ?? false) ? 'checked' : '' }}>
                                    <!-- <label class="form-check-label" for="is_primary"> Is Primary </label> -->
                                </div>
                            </div>
                        </div>

                        <div class="form-group row" id="primary-parent-row" style="{{ old('is_primary', $user->is_primary ?? false) ? 'display:none;' : '' }}">
                            <label class="col-lg-4 col-form-label text-lg-end">Primary Parent User:</label>
                            <div class="col-lg-6">
                                <select class="form-control" name="primary_parent_id" id="primary_parent_id">
                                    <option value="">Select Primary Parent</option>
                                    @isset($primaryParents)
                                        @foreach($primaryParents as $pp)
                                            <option value="{{ $pp->id }}" {{ old('primary_parent_id') == $pp->id ? 'selected' : '' }}>{{ $pp->first_name }} {{ $pp->last_name }} ({{ $pp->email }})</option>
                                        @endforeach
                                    @endisset
                                </select>
                                <small class="form-text text-muted">Choose an existing primary parent (if applicable).</small>
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Relation:</label>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="relation" id="relation" value="{{ old('relation', $user->relation ?? '') }}">
                            </div>
                        </div>

                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Child Name:</label>
                            <div class="col-lg-6">
                                <input type="text" class="form-control" name="child_name" id="child_name" value="{{ old('child_name', $user->child_name ?? '') }}">
                            </div>
                        </div>
                    @endif

                    @if($role == 'staff')
                        <div class="form-group row">
                            <label class="col-lg-4 col-form-label text-lg-end">Staff Role:</label>
                            <div class="col-lg-6">
                                <select class="form-control" name="staff_role" id="staff_role" required>
                                    <option value="" disabled selected>Select Role</option>
                                    <option value="viewer" {{ old('role', isset($user) ? $user->roles->first()->name : '') == 'viewer' ? 'selected' : '' }}>Viewer</option>
                                    <option value="dispatcher" {{ old('role', isset($user) ? $user->roles->first()->name : '') == 'dispatcher' ? 'selected' : '' }}>Dispatcher</option>
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
                                    name="password_confirmation"
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
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var isPrimary = document.getElementById('is_primary');
        var primaryRow = document.getElementById('primary-parent-row');
        function togglePrimaryRow() {
            if (!isPrimary || !primaryRow) return;
            primaryRow.style.display = isPrimary.checked ? 'none' : '';
        }
        if (isPrimary) {
            isPrimary.addEventListener('change', togglePrimaryRow);
            // initialize
            togglePrimaryRow();
        }
    });
</script>
@endpush
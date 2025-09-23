@extends('admin.layouts.app')


@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item"><a href="{{ route('admin.schools') }}">Schools</a></li>
<li class="breadcrumb-item" aria-current="page">{{ isset($school) ? 'Edit' : 'Create' }}</li>
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
                <form class="validate-me" id="validate-me" data-validate method="POST" action="{{ isset($school) ? route('admin.schools.update', $school->id) : route('admin.schools.store') }}" enctype="multipart/form-data">
                    @csrf

                    @if(isset($school))
                        @method('PUT')
                    @endif

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">Name:</label>
                        <div class="col-lg-6">
                            <input type="text" class="form-control" name="name" id="name" required value="{{ old('name', $school->name ?? '') }}">
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">Start Time:</label>
                        <div class="col-lg-6">
                            <input type="time" class="form-control" name="start_time" id="start_time" required value="{{ old('start_time', isset($school->start_time) ? \Carbon\Carbon::parse($school->start_time)->format('H:i') : '') }}">
                            <small class="form-text text-muted">HH:MM (24-hour time)</small>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">End Time:</label>
                        <div class="col-lg-6">
                            <input type="time" class="form-control" name="end_time" id="end_time" required value="{{ old('end_time', isset($school->end_time) ? \Carbon\Carbon::parse($school->end_time)->format('H:i') : '') }}">
                            <small class="form-text text-muted">HH:MM (24-hour time)</small>
                        </div>
                    </div>

                    <div class="form-group row mb-0">
                        <div class="col-lg-4 col-form-label"></div>
                        <div class="col-lg-6">
                            <input type="submit" class="btn btn-primary" value="{{ isset($school) ? 'Update' : 'Submit' }}">
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
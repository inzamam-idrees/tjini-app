@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('public/assets/css/plugins/dataTables.bootstrap5.min.css') }}">
@endpush

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item" aria-current="page">Schools</li>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="float-start">
                    <h5>Schools</h5>
                    <small>List of all schools</small>
                </div>
                <a href="{{ route('admin.schools.create') }}" class="btn btn-primary float-end">Add New School</a>
            </div>
            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table id="dom-table" class="table table-striped table-bordered nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schools as $school)
                            <tr>
                                <td>{{ $school->name }}</td>
                                <td>{{ $school->start_time }}</td>
                                <td>{{ $school->end_time }}</td>
                                <td>
                                    <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-primary">Edit</a>
                                    <form action="{{ route('admin.schools.destroy', $school->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="btn btn-danger btn-delete">Delete</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
<script src="{{ asset('public/assets/js/plugins/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('public/assets/js/plugins/dataTables.bootstrap5.min.js') }}"></script>
<script>
    $(document).ready(function() {
        $('#dom-table').DataTable();
    });
</script>
@endpush
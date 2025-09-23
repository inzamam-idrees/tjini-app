@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('public/assets/css/plugins/dataTables.bootstrap5.min.css') }}">
@endpush

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Schools</h5>
                <small>List of all schools</small>
            </div>
            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table id="dom-table" class="table table-striped table-bordered nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($schools as $school)
                            <tr>
                                <td>{{ $school->name }}</td>
                                <td>{{ $school->start_time }}</td>
                                <td>{{ $school->end_time }}</td>
                                <td>{{ $school->status }}</td>
                                <td>
                                    <a href="{{ route('admin.schools.edit', $school->id) }}" class="btn btn-primary">Edit</a>
                                    <a href="{{ route('admin.schools.destroy', $school->id) }}" class="btn btn-danger">Delete</a>
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
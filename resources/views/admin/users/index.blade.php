@extends('admin.layouts.app')

@push('styles')
<link rel="stylesheet" href="{{ asset('public/assets/css/plugins/dataTables.bootstrap5.min.css') }}">
@endpush

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item" aria-current="page">{{ $title }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="float-start">
                    <h5>{{ $title }}</h5>
                    <small>List of all {{ $title }}</small>
                </div>
                <a href="{{ route('admin.users.create', $role) }}" class="btn btn-primary float-end">Add New {{ $title }}</a>
            </div>
            <div class="card-body">
                <div class="dt-responsive table-responsive">
                    <table id="dom-table" class="table table-striped table-bordered nowrap">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>School</th>
                                @if($role === 'parent')
                                    <th>Relation</th>
                                    <th>IsPrimary</th>
                                @endif
                                @if($role === 'staff')
                                    <th>Role</th>
                                @endif
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($users as $user)
                            <tr>
                                <td>{{ $user->fullName() }}</td>
                                <td>{{ $user->email }}</td>
                                <td>{{ $user->school ? $user->school->name : 'N/A' }}</td>
                                @if($role === 'parent')
                                    <td>{{ $user->relation }}</td>
                                    <td>
                                        @if($user->is_primary)
                                            <span class="badge bg-success">True</span>
                                        @else
                                            <span class="badge bg-danger">False</span>
                                        @endif
                                    </td>
                                @endif
                                @if($role === 'staff')
                                    <td>
                                        <span class="badge bg-info text-capitalize">{{ $user->roles->first()->name ?? 'N/A' }}</span>
                                    </td>
                                @endif
                                <td>
                                    <a href="{{ route('admin.users.edit', [$role, $user->id]) }}" class="btn btn-primary">Edit</a>
                                    <form action="{{ route('admin.users.destroy', [$role, $user->id]) }}" method="POST" style="display:inline;">
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
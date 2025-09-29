@extends('admin.layouts.app')

@section('breadcrumb')
<li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
<li class="breadcrumb-item" aria-current="page">Import Users</li>
@endsection

@section('content')
<div class="row">
    <!-- [ Form Validation ] start -->
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <h5>Import Users</h5>
            </div>
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <h5 class="alert-heading mb-1">Import Errors</h5>
                        <ul class="list-unstyled mb-0">
                            @foreach ($errors->all() as $error)
                                <li><i class="ti ti-alert-circle"></i> {{ $error }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if (session('success'))
                    <div class="alert alert-success alert-custom">
                        <i class="ti ti-check"></i> {{ session('success') }}
                    </div>
                @endif

                @if (session('error'))
                    <div class="alert alert-danger alert-custom">
                        <i class="ti ti-alert-circle"></i> {{ session('error') }}
                    </div>
                @endif

                <form id="importForm" class="validate-me" data-validate method="POST" action="{{ route('admin.user.import') }}" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">User Role:</label>
                        <div class="col-lg-6">
                            <select id="role" name="role" class="form-select" required onchange="updateTemplateLink(this.value)">
                                <option value="parent">Parent</option>
                                <option value="viewer">Viewer</option>
                                <option value="dispatcher">Dispatcher</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group row">
                        <label class="col-lg-4 col-form-label text-lg-end">Excel/CSV File:</label>
                        <div class="col-lg-6">
                            <input type="file" name="file" class="form-control" accept=".xlsx,.xls,.csv" required>
                            <div class="mt-2">
                                <a id="templateLink" href="{{ route('admin.user.import.template', ['role' => 'parent']) }}" 
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="ti ti-download me-1"></i> Download Template
                                </a>
                            </div>
                        </div>
                    </div>                                                    <div class="form-group row">
                        <div class="col-lg-6 offset-lg-4">
                            <div class="alert alert-light border mb-4">
                                <h6 class="alert-heading fw-bold mb-2">Import Guidelines:</h6>
                                <ul class="list-unstyled mb-0">
                                    <li><i class="ti ti-info-circle me-2"></i>File must be in Excel (.xlsx, .xls) or CSV format</li>
                                    <li><i class="ti ti-info-circle me-2"></i>First row should contain column headers</li>
                                    <li><i class="ti ti-info-circle me-2"></i>Required columns vary by role type</li>
                                    <li><i class="ti ti-info-circle me-2"></i>Download template for correct format</li>
                                    <li><i class="ti ti-info-circle me-2"></i>Duplicate emails will be skipped</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="form-group row">
                        <div class="col-lg-6 offset-lg-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ti ti-upload me-1"></i> Import Users
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Progress Section -->
                <div id="progressSection" class="mt-4 d-none">
                    <div class="form-group row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-3">Import Progress</h5>
                                    <div class="progress" style="height: 24px;">
                                        <div id="progressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                             role="progressbar" style="width: 0%">
                                            <span id="progressText" class="fw-bold">0%</span>
                                        </div>
                                    </div>
                                    <p class="text-center text-muted mt-3 mb-0" id="progressStats">Processing 0/0 Records</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Results Section -->
                <div id="resultsSection" class="mt-4 d-none">
                    <div class="form-group row">
                        <div class="col-lg-8 offset-lg-2">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="mb-4">Import Results</h5>
                                    
                                    <div class="row g-3 mb-4">
                                        <div class="col-sm-4">
                                            <div class="card border bg-light">
                                                <div class="card-body p-3 text-center">
                                                    <h6 class="text-muted mb-2">Total Records</h6>
                                                    <h3 id="totalRecords" class="mb-0">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="card border border-success">
                                                <div class="card-body p-3 text-center">
                                                    <h6 class="text-muted mb-2">Successfully Imported</h6>
                                                    <h3 id="successCount" class="mb-0 text-success">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-sm-4">
                                            <div class="card border border-warning">
                                                <div class="card-body p-3 text-center">
                                                    <h6 class="text-muted mb-2">Skipped Records</h6>
                                                    <h3 id="skipCount" class="mb-0 text-warning">0</h3>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <h5 class="mb-3">Import Log</h5>
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-striped m-0 align-middle">
                                            <thead>
                                                <tr>
                                                    <th class="text-center" style="width: 80px;">Row</th>
                                                    <th style="width: 120px;">Status</th>
                                                    <th>Message</th>
                                                </tr>
                                            </thead>
                                            <tbody id="importLogs">
                                                <!-- Logs will be inserted here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- [ Main Content ] end -->
    </div>
</div>
@endsection

@section('script')
    <script type="text/javascript">
        function updateTemplateLink(role) {
            const link = document.getElementById('templateLink');
            link.href = `/admin/user/import/template?role=${role}`;
        }

        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const button = this.querySelector('button[type="submit"]');
            const originalText = button.innerHTML;
            
            // Show progress section and hide results section
            document.getElementById('progressSection').classList.remove('d-none');
            document.getElementById('resultsSection').classList.add('d-none');
            
            // Update button state
            button.disabled = true;
            button.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Importing...';
            
            // Start the import
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    startProgressPolling(data.batch_id);
                } else {
                    throw new Error(data.message || 'Import failed');
                }
            })
            .catch(error => {
                document.getElementById('progressSection').classList.add('d-none');
                // Show error alert
                showAlert('error', error.message || 'An error occurred during import');
                // Reset button
                button.disabled = false;
                button.innerHTML = originalText;
            });
        });

        function startProgressPolling(batchId) {
            const progressCheck = () => {
                fetch(`/admin/user/import/progress/${batchId}`, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    credentials: 'same-origin'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            throw new Error(data.error);
                        }

                        updateProgress(data);

                        if (!data.completed) {
                            setTimeout(progressCheck, 2000);
                        } else {
                            displayResults(data);
                        }
                    })
                    .catch(error => {
                        showAlert('error', error.message || 'Error checking progress');
                    });
            };

            progressCheck();
        }

        function updateProgress(data) {
            const percentage = Math.round((data.processed / data.total) * 100);
            document.getElementById('progressBar').style.width = `${percentage}%`;
            document.getElementById('progressText').textContent = `${percentage}%`;
            document.getElementById('progressStats').textContent = `${data.processed}/${data.total} Records`;
            
            if (data.completed) {
                displayResults(data);
            }
        }

        function displayResults(data) {
            // Reset submit button
            const button = document.querySelector('#importForm button[type="submit"]');
            button.disabled = false;
            button.innerHTML = '<i class="ti ti-upload me-1"></i> Import Users';
            
            // Show results section
            document.getElementById('resultsSection').classList.remove('d-none');
            
            // Update summary stats
            document.getElementById('totalRecords').textContent = data.total;
            document.getElementById('successCount').textContent = data.success;
            document.getElementById('skipCount').textContent = data.skipped;
            
            // Clear and update logs
            const logsContainer = document.getElementById('importLogs');
            logsContainer.innerHTML = '';
            
            data.logs.forEach(log => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="text-center">${log.row}</td>
                    <td>
                        <span class="badge ${log.type === 'success' ? 'bg-success' : 'bg-warning'} rounded-pill">
                            ${log.type}
                        </span>
                    </td>
                    <td>${log.message}</td>
                `;
                logsContainer.appendChild(row);
            });
            
            // Show success message
            if (data.success > 0) {
                showAlert('success', 'Import completed successfully');
            }
        }
        
        function showAlert(type, message) {
            // Remove existing alerts
            document.querySelectorAll('.alert-custom').forEach(alert => alert.remove());
            
            // Create new alert
            const alert = document.createElement('div');
            alert.className = `alert alert-${type} alert-custom`;
            alert.innerHTML = `<i class="ti ti-${type === 'success' ? 'check' : 'alert-circle'}"></i> ${message}`;
            
            // Insert after form
            document.getElementById('importForm').insertAdjacentElement('beforebegin', alert);
            
            // Auto hide after 5 seconds if it's a success message
            if (type === 'success') {
                setTimeout(() => alert.remove(), 5000);
            }
        }
    </script>
@endsection
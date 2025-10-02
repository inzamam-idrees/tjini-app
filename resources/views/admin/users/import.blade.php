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
                                <small class="text-muted">Tip: For CSV, save from Excel as UTF-8 CSV (comma separated). Avoid Excel's default semicolon/locale CSV if possible.</small>
                                <br />
                                <a id="templateLink" href="{{ route('admin.user.import.template', ['role' => 'parent']) }}" 
                                    class="btn btn-outline-primary btn-sm">
                                    <i class="ti ti-download me-1"></i> Download Template
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group row">
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

<script type="text/javascript">
    function updateTemplateLink(role) {
        const link = document.getElementById('templateLink');
        link.href = `/admin/user/import/template?role=${role}`;
    }
    
    document.addEventListener('DOMContentLoaded', function() {
        updateTemplateLink(document.getElementById('role').value);
        
        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = this;
            const fileInput = form.querySelector('input[type="file"][name="file"]');
            const button = form.querySelector('button[type="submit"]');
            const originalText = button ? button.innerHTML : '';

            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                showAlert('error', 'Please choose a file to upload.');
                return;
            }

            const file = fileInput.files[0];
            console.log('Preparing upload:', file.name, file.type, file.size);

            // Basic client-side extension check (optional)
            const allowedExt = ['csv', 'xlsx', 'xls'];
            const nameParts = file.name.split('.');
            const ext = nameParts.length > 1 ? nameParts.pop().toLowerCase() : '';
            if (!allowedExt.includes(ext)) {
                showAlert('error', 'File extension not allowed. Use xlsx, xls or csv.');
                return;
            }

            const formData = new FormData(form);

            // Show progress section and hide results section
            const progressSection = document.getElementById('progressSection');
            const resultsSection = document.getElementById('resultsSection');
            if (progressSection) progressSection.classList.remove('d-none');
            if (resultsSection) resultsSection.classList.add('d-none');

            // Update button state
            if (button) {
                button.disabled = true;
                button.innerHTML = '<i class="ti ti-loader ti-spin me-1"></i> Importing...';
            }

            fetch(form.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                credentials: 'same-origin'
            })
            .then(async response => {
                const contentType = response.headers.get('content-type') || '';
                let body = null;
                if (contentType.includes('application/json')) {
                    body = await response.json();
                } else {
                    const text = await response.text();
                    try { body = JSON.parse(text); } catch(e) { body = { message: text }; }
                }

                if (response.status === 422) {
                    // Validation errors
                    const errors = body.errors || body;
                    const messages = [];
                    if (errors && typeof errors === 'object') {
                        for (const key in errors) {
                            if (Array.isArray(errors[key])) {
                                messages.push(...errors[key]);
                            } else if (typeof errors[key] === 'string') {
                                messages.push(errors[key]);
                            }
                        }
                    }
                    showAlert('error', messages.join('\n') || (body.message || 'Validation failed'));
                    throw new Error('Validation failed');
                }

                if (!response.ok) {
                    const errMsg = (body && (body.message || body.error)) || `Request failed with status ${response.status}`;
                    showAlert('error', errMsg);
                    throw new Error(errMsg);
                }

                return body;
            })
            .then(data => {
                if (data && data.success) {
                    startProgressPolling(data.batch_id);
                } else {
                    const msg = (data && (data.message || data.error)) || 'Import failed to start';
                    showAlert('error', msg);
                    if (button) {
                        button.disabled = false;
                        button.innerHTML = originalText;
                    }
                }
            })
            .catch(error => {
                console.error('Upload error', error);
                if (progressSection) progressSection.classList.add('d-none');
                if (button) {
                    button.disabled = false;
                    button.innerHTML = originalText;
                }
            });
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
                .then(raw => {
                    // Support both shapes: { status: 'completed', data: {...} } and { total, processed, ... }
                    let wrapper = raw;
                    let payload = raw;
                    if (raw && raw.status && raw.data) {
                        wrapper = raw;
                        payload = raw.data;
                        payload.completed = (raw.status === 'completed') || !!payload.completed;
                    }

                    // Ensure payload has processed count if not present
                    if (typeof payload.processed === 'undefined') {
                        // derive from success+skipped if available
                        if (typeof payload.success !== 'undefined' && typeof payload.skipped !== 'undefined') {
                            payload.processed = Number(payload.success) + Number(payload.skipped);
                        } else if (typeof payload.total !== 'undefined' && payload.completed) {
                            payload.processed = payload.total;
                        } else {
                            payload.processed = 0;
                        }
                    }

                    updateProgress(payload);

                    if (!payload.completed) {
                        setTimeout(progressCheck, 1500);
                    } else {
                        displayResults(payload);
                    }
                })
                .catch(error => {
                    console.error('Progress check error', error);
                    showAlert('danger', error.message || 'Error checking progress');
                });
        };

        progressCheck();
    }

    function updateProgress(data) {
        const total = Number(data.total || 0);
        const processed = Number(data.processed || 0);
        const percentage = total > 0 ? Math.round((processed / total) * 100) : 0;
        const progressBar = document.getElementById('progressBar');
        const progressText = document.getElementById('progressText');
        const progressStats = document.getElementById('progressStats');

        if (progressBar) progressBar.style.width = `${percentage}%`;
        if (progressText) progressText.textContent = `${percentage}%`;
        if (progressStats) progressStats.textContent = `${processed}/${total} Records`;

        if (data.completed) {
            displayResults(data);
        }
    }

    function displayResults(data) {
        // Reset submit button
        const button = document.querySelector('#importForm button[type="submit"]');
        if (button) {
            button.disabled = false;
            button.innerHTML = '<i class="ti ti-upload me-1"></i> Import Users';
        }

        // Show results section
        const resultsSection = document.getElementById('resultsSection');
        if (resultsSection) resultsSection.classList.remove('d-none');

        // Accept wrapped shape too
        const payload = (data && data.data) ? data.data : data;

        // Update summary stats
        document.getElementById('totalRecords').textContent = payload.total || 0;
        document.getElementById('successCount').textContent = payload.success || 0;
        document.getElementById('skipCount').textContent = payload.skipped || 0;

        // Clear and update logs
        const logsContainer = document.getElementById('importLogs');
        if (logsContainer) {
            logsContainer.innerHTML = '';
            (payload.logs || []).forEach(log => {
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
        }

        // Show success message
        if ((payload.success || 0) > 0) {
            showAlert('success', 'Import completed successfully');
        }
    }
    
    function showAlert(type, message) {
        // Normalize type for Swal (accept 'error' or 'danger')
        const swalIcon = (type === 'error' || type === 'danger') ? 'error' : (type === 'success' ? 'success' : type);

        // Use SweetAlert2 toast to match footer_script behavior
        Swal.fire({
            toast: true,
            position: 'top-end',
            icon: swalIcon,
            title: message,
            showConfirmButton: false,
            timer: swalIcon === 'success' ? 3000 : 4000,
            timerProgressBar: true,
            didOpen: (toast) => {
                // match existing handlers in footer_script
                toast.onmouseenter = Swal.stopTimer;
                toast.onmouseleave = Swal.resumeTimer;
            }
        });
    }
</script>
@endsection

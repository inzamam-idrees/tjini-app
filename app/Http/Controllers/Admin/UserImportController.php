<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Imports\UsersImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class UserImportController extends Controller
{
    public function showImportForm()
    {
        $title = 'Import Users';
        return view('admin.users.import', compact('title'));
    }

    public function import(Request $request)
    {
        // Validate role first
        $request->validate([
            'role' => 'required|in:parent,viewer,dispatcher',
        ]);

        // Basic file presence check
        if (!$request->hasFile('file')) {
            return response()->json([
                'success' => false,
                'message' => 'No file was uploaded. Please attach a CSV or Excel file.'
            ], 422);
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $ext = strtolower($file->getClientOriginalExtension() ?? '');
        $mime = $file->getClientMimeType();

        // Common allowed extensions and MIME types for Excel/CSV
        $allowedExt = ['xlsx', 'xls', 'csv'];
        $allowedMimes = [
            'text/csv',
            'text/plain',
            'application/csv',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/octet-stream'
        ];

        $extOk = in_array($ext, $allowedExt);
        $mimeOk = in_array(strtolower($mime), $allowedMimes);

        // Sometimes browsers send wrong mime or extension is missing; try to infer from filename
        if (!$extOk) {
            $nameParts = explode('.', $originalName);
            $maybeExt = strtolower(end($nameParts));
            if (in_array($maybeExt, $allowedExt)) {
                $ext = $maybeExt;
                $extOk = true;
            }
        }

        if (!($extOk || $mimeOk)) {
            return response()->json([
                'success' => false,
                'message' => "Invalid file type. Detected extension: .{$ext} (original name: {$originalName}), mime: {$mime}. Allowed: xlsx, xls, csv."
            ], 422);
        }

        try {
            $school_id = Auth::user()->school_id;
            $batch_id = Str::uuid()->toString();
            
            // Store initial progress
            Cache::put("import_progress_{$batch_id}", [
                'total' => 0,
                'processed' => 0,
                'success' => 0,
                'skipped' => 0,
                'percentage' => 0
            ], now()->addHours(1));

            // Start import process
            Excel::import(new UsersImport($request->role, $school_id, $batch_id), $request->file('file'));

            return response()->json([
                'success' => true,
                'message' => 'Import started successfully',
                'batch_id' => $batch_id
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error starting import: ' . $e->getMessage()
            ], 500);
        }
    }

    public function checkProgress($batchId)
    {
        $progress = Cache::get("import_progress_{$batchId}");
        $results = Cache::get("import_results_{$batchId}");

        if ($results) {
            return response()->json([
                'status' => 'completed',
                'data' => $results
            ]);
        }

        if ($progress) {
            return response()->json([
                'status' => 'in_progress',
                'data' => $progress
            ]);
        }

        return response()->json([
            'status' => 'not_found'
        ], 404);
    }

    public function downloadTemplate(Request $request)
    {
        $role = $request->query('role', 'parent');
        $headers = ['First Name', 'Last Name', 'Email'];
        
        if ($role === 'parent') {
            $headers = array_merge($headers, ['Child Name', 'Relation', 'Is Primary']);
        } elseif ($role === 'staff') {
            $headers = array_merge($headers, ['Staff Role']);
        }

        // Create CSV template
        $output = fopen('php://temp', 'r+');
        fputcsv($output, $headers);

        // Example row
        $exampleRow = ['John', 'Doe', 'john.doe@example.com'];
        if ($role === 'parent') {
            $exampleRow = array_merge($exampleRow, ['Jane Doe', 'Father', '1']);
        } elseif ($role === 'staff') {
            $exampleRow = array_merge($exampleRow, ['viewer']);
        }
        fputcsv($output, $exampleRow);

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return response($content)
            ->header('Content-Type', 'text/csv')
            ->header('Content-Disposition', "attachment; filename={$role}_import_template.csv");
    }
}
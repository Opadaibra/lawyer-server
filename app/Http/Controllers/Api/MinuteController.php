<?php
// app/Http/Controllers/Api/MinuteController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Minute;
use App\Models\CaseFile;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MinuteController extends Controller
{
    /**
     * GET /api/minutes
     * جلب كل المحاضر للمستخدم الحالي
     */
    public function index(Request $request)
{
    try {
        
        $query = Task::where('user_id', Auth::id())
                    ->with(['case', 'files']);

        // فلترة حسب الحالة
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // فلترة حسب القضية
        if ($request->has('case_id')) {
            $query->where('case_file_id', $request->case_id);
        }

        // المهام المؤرشفة أو غير المؤرشفة
        if ($request->has('archived')) {
            if ($request->archived) {
                $query->whereNotNull('archived_at');
            } else {
                $query->whereNull('archived_at');
            }
        }

        \Log::info('SQL Query', ['sql' => $query->toSql(), 'bindings' => $query->getBindings()]);
        
        $tasks = $query->latest()->get();
        
        \Log::info('Tasks found', ['count' => $tasks->count()]);

        return response()->json([
            'status' => 'success',
            'count' => $tasks->count(),
            'data' => $tasks
        ]);
        
    } catch (\Exception $e) {
        \Log::error('Task index error', [
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
        
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to load tasks: ' . $e->getMessage()
        ], 500);
    }
}

    /**
     * POST /api/minutes
     * إنشاء محضر جديد
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'case_file_id' => 'required|exists:case_files,id',
            'title' => 'required|string|max:255',
            'content' => 'required|string',  // تأكد من وجودها هنا
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من أن القضية تخص المستخدم
        $case = CaseFile::where('user_id', Auth::id())
            ->where('id', $request->case_file_id)
            ->first();

        if (!$case) {
            return response()->json([
                'status' => 'error',
                'message' => 'Case not found or does not belong to you'
            ], 403);
        }

        // استخدم only أو array مباشرة
        $minute = Minute::create([
            'user_id' => Auth::id(),
            'case_file_id' => $request->case_file_id,
            'title' => $request->title,
            'content' =>  $request->input('content'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Minute created successfully',
            'data' => $minute->load(['case', 'files'])
        ], 201);
    }

    /**
     * GET /api/minutes/{id}
     * جلب محضر محدد
     */
    public function show($id)
    {
        $minute = Minute::where('user_id', Auth::id())
            ->with(['case', 'files'])
            ->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        return response()->json([
            'status' => 'success',
            'data' => $minute
        ]);
    }

    /**
     * PUT/PATCH /api/minutes/{id}
     * تحديث محضر
     */
    public function update(Request $request, $id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $minute->update($request->only(['title', 'content']));

        return response()->json([
            'status' => 'success',
            'message' => 'Minute updated successfully',
            'data' => $minute->fresh(['case', 'files'])
        ]);
    }

    /**
     * DELETE /api/minutes/{id}
     * حذف محضر
     */
    public function destroy($id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        // حذف العلاقات مع الملفات أولاً
        $minute->files()->detach();
        $minute->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Minute deleted successfully'
        ]);
    }

    /**
     * POST /api/minutes/{id}/archive
     * أرشفة محضر
     */
    public function archive($id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        if ($minute->archived_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute is already archived'
            ], 400);
        }

        $minute->update(['archived_at' => now()]);

        return response()->json([
            'status' => 'success',
            'message' => 'Minute archived successfully',
            'data' => $minute
        ]);
    }

    /**
     * POST /api/minutes/{id}/unarchive
     * إلغاء أرشفة محضر
     */
    public function unarchive($id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        if (!$minute->archived_at) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute is not archived'
            ], 400);
        }

        $minute->update(['archived_at' => null]);

        return response()->json([
            'status' => 'success',
            'message' => 'Minute unarchived successfully',
            'data' => $minute
        ]);
    }

    /**
     * POST /api/minutes/{id}/attach-files
     * إضافة ملفات للمحضر
     */
    public function attachFiles(Request $request, $id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:files,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        // التحقق من أن الملفات تخص المستخدم
        $files = \App\Models\File::whereIn('id', $request->file_ids)
            ->where('user_id', Auth::id())
            ->pluck('id')
            ->toArray();

        if (count($files) !== count($request->file_ids)) {
            return response()->json([
                'status' => 'error',
                'message' => 'One or more files do not belong to you'
            ], 403);
        }

        $minute->files()->syncWithoutDetaching($request->file_ids);

        return response()->json([
            'status' => 'success',
            'message' => 'Files attached successfully',
            'data' => $minute->load('files')
        ]);
    }

    /**
     * POST /api/minutes/{id}/detach-file
     * إزالة ملف من المحضر
     */
    public function detachFile(Request $request, $id)
    {
        $minute = Minute::where('user_id', Auth::id())->find($id);

        if (!$minute) {
            return response()->json([
                'status' => 'error',
                'message' => 'Minute not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'file_id' => 'required|exists:files,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'errors' => $validator->errors()
            ], 422);
        }

        $minute->files()->detach($request->file_id);

        return response()->json([
            'status' => 'success',
            'message' => 'File detached successfully'
        ]);
    }

    /**
     * GET /api/minutes/case/{caseId}
     * جلب كل محاضر قضية معينة
     */
    public function getCaseMinutes($caseId)
    {
        $case = CaseFile::where('user_id', Auth::id())
            ->where('id', $caseId)
            ->firstOrFail();

        $minutes = Minute::where('user_id', Auth::id())
            ->where('case_file_id', $caseId)
            ->with(['files'])
            ->latest()
            ->get();

        return response()->json([
            'status' => 'success',
            'case_id' => (int) $caseId,
            'case_number' => $case->case_number,
            'count' => $minutes->count(),
            'data' => $minutes
        ]);
    }
}
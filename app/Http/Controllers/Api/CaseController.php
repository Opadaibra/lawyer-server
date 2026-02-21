<?php
// app/Http/Controllers/Api/CaseController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CaseFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CaseController extends Controller
{
    /**
     * عرض جميع الدعاوى للمستخدم الحالي
     */
    public function index()
    {
        $cases = CaseFile::where('user_id', auth()->id())
                        ->with(['client', 'tasks', 'minutes', 'files'])
                        ->latest()
                        ->get();

        return response()->json($cases);
    }

    /**
     * إنشاء دعوى جديدة
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'client_id' => 'required|exists:clients,id',
            'case_number' => 'required|string|unique:case_files',
            'case_type' => 'required|string',
            'court' => 'nullable|string',
            'status' => 'nullable|string|in:open,closed,pending,archived'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case = CaseFile::create([
            'user_id' => auth()->id(),
            'client_id' => $request->client_id,
            'case_number' => $request->case_number,
            'case_type' => $request->case_type,
            'court' => $request->court,
            'status' => $request->status ?? 'open'
        ]);

        return response()->json([
            'message' => 'Case created successfully',
            'case' => $case->load(['client', 'files'])
        ], 201);
    }

    /**
     * عرض دعوى محددة
     */
    public function show($id)
    {
        $case = CaseFile::where('user_id', auth()->id())
                        ->with(['client', 'tasks', 'minutes', 'files'])
                        ->findOrFail($id);

        return response()->json($case);
    }

    /**
     * تحديث دعوى
     */
    public function update(Request $request, $id)
    {
        $case = CaseFile::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'client_id' => 'sometimes|exists:clients,id',
            'case_number' => 'sometimes|string|unique:case_files,case_number,' . $id,
            'case_type' => 'sometimes|string',
            'court' => 'nullable|string',
            'status' => 'nullable|string|in:open,closed,pending,archived'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case->update($request->all());

        return response()->json([
            'message' => 'Case updated successfully',
            'case' => $case->fresh(['client', 'files'])
        ]);
    }

    /**
     * أرشفة دعوى (soft delete)
     */
 /**
 * أرشفة دعوى (soft delete)
 */
public function archive($id)
{
    $case = CaseFile::where('user_id', auth()->id())->find($id); // استخدم find() بدل findOrFail()

    if (!$case) {
        return response()->json([
            'status' => 'error',
            'message' => 'Case not found or does not belong to you'
        ], 404);
    }

    // إذا كانت القضية مؤرشفة بالفعل
    if ($case->archived_at) {
        return response()->json([
            'status' => 'error',
            'message' => 'Case is already archived'
        ], 400);
    }

    $case->update(['archived_at' => now()]);

    return response()->json([
        'status' => 'success',
        'message' => 'Case archived successfully',
        'case' => $case
    ]);
}

    /**
     * إلغاء أرشفة دعوى
     */
    public function unarchive($id)
    {
        $case = CaseFile::where('user_id', auth()->id())->findOrFail($id);
        $case->update(['archived_at' => null]);

        return response()->json(['message' => 'Case unarchived successfully']);
    }

    /**
     * حذف دعوى نهائياً
     */
    public function destroy($id)
    {
        $case = CaseFile::where('user_id', auth()->id())->findOrFail($id);
        $case->delete();

        return response()->json(['message' => 'Case deleted successfully']);
    }

    /**
     * إضافة ملفات إلى الدعوى
     */
    public function attachFiles(Request $request, $id)
    {
        $case = CaseFile::where('user_id', auth()->id())->findOrFail($id);

        $validator = Validator::make($request->all(), [
            'file_ids' => 'required|array',
            'file_ids.*' => 'exists:files,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $case->files()->syncWithoutDetaching($request->file_ids);

        return response()->json([
            'message' => 'Files attached successfully',
            'case' => $case->load('files')
        ]);
    }
}
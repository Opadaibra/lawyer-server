<?php
// app/Models/Minute.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Minute extends Model
{
    use HasFactory;
    // use SoftDeletes; // إذا بدك soft delete، فك التعليق

    protected $fillable = [
        'user_id',
        'case_file_id',
        'title',
        'content',
        'archived_at'
    ];

    protected $casts = [
        'archived_at' => 'datetime'
    ];

    // العلاقات
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function case()
    {
        return $this->belongsTo(CaseFile::class, 'case_file_id');
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'file_minute');
    }
}
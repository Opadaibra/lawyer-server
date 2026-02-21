<?php
// app/Models/CaseFile.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseFile extends Model
{
    use HasFactory;

    protected $table = 'case_files';

    protected $fillable = [
        'user_id',
        'client_id',
        'case_number',
        'case_type',
        'court',
        'status',
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

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function minutes()
    {
        return $this->hasMany(Minute::class);
    }

    public function files()
    {
        return $this->belongsToMany(File::class, 'case_file_file');
    }
}
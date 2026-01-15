<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class AttendanceDocument extends Model implements HasMedia {
    use InteractsWithMedia;

    protected $fillable = ['user_id', 'month', 'status'];
    protected $casts = ['month' => 'date'];

    public function user() {
        return $this->belongsTo(User::class);
    }

    public function registerMediaCollections(): void {
        $this->addMediaCollection('signed_sheets')->singleFile();
    }
}

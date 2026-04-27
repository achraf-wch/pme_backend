<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class Media extends Model {
    protected $fillable = ['file_name', 'file_url', 'file_type', 'file_size', 'uploaded_by'];
    public function uploader() { return $this->belongsTo(User::class, 'uploaded_by'); }
}
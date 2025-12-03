<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class PointOfSaleUpload extends Model
{
    use HasFactory;

    protected $fillable = [
        'point_of_sale_id',
        'upload_id',
        'type',
    ];

    protected $appends = ['url', 'name', 'file_path'];

    public function pointOfSale()
    {
        return $this->belongsTo(PointOfSale::class);
    }

    private function findFile()
    {
        $basePath = $this->getBasePath();
        $files = Storage::disk('public')->files($basePath);
        
        foreach ($files as $file) {
            if (str_starts_with(basename($file), $this->upload_id)) {
                return $file;
            }
        }
        
        return null;
    }

    public function getUrlAttribute()
    {
        $file = $this->findFile();
        return $file ? url('storage/' . $file) : null;
    }

    public function getNameAttribute()
    {
        $file = $this->findFile();
        return $file ? basename($file) : $this->upload_id;
    }

    public function getFilePathAttribute()
    {
        return $this->findFile();
    }

    private function getBasePath()
    {
        $paths = [
            'id_document' => 'uploads/id_documents',
            'photo' => 'uploads/photos',
            'fiscal_document' => 'uploads/fiscal_documents',
        ];
        
        return $paths[$this->attributes['type']] ?? 'uploads';
    }
}

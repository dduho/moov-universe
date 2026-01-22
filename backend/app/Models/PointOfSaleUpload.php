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
        'file_path',
        'mime_type',
        'type',
    ];

    protected $appends = ['url', 'name', 'mime_type'];

    public function pointOfSale()
    {
        return $this->belongsTo(PointOfSale::class);
    }

    public function getUrlAttribute()
    {
        // Si file_path est stocké, chercher le fichier avec cette extension
        if ($this->file_path) {
            $basePath = dirname($this->file_path);
            $basename = basename($this->file_path);
            
            // D'abord essayer le chemin exact
            if (Storage::disk('public')->exists($this->file_path)) {
                return Storage::url($this->file_path);
            }
            
            // Si pas trouvé, chercher avec wildcard pour l'extension
            $files = Storage::disk('public')->files($basePath);
            foreach ($files as $file) {
                if (str_starts_with(basename($file), $basename)) {
                    return Storage::url($file);
                }
            }
        }
        
        // Fallback: chercher par upload_id dans le répertoire approprié
        if ($this->upload_id) {
            $basePath = $this->getBasePath();
            $files = Storage::disk('public')->files($basePath);
            
            foreach ($files as $file) {
                if (str_starts_with(basename($file), $this->upload_id)) {
                    return Storage::url($file);
                }
            }
        }
        
        return null;
    }

    public function getNameAttribute()
    {
        if ($this->file_path) {
            return basename($this->file_path);
        }
        
        $basePath = $this->getBasePath();
        $files = Storage::disk('public')->files($basePath);
        
        foreach ($files as $file) {
            if (str_starts_with(basename($file), $this->upload_id)) {
                return basename($file);
            }
        }
        
        return $this->upload_id;
    }

    public function getMimeTypeAttribute()
    {
        // Si stocké, le retourner
        if ($this->attributes['mime_type'] ?? null) {
            return $this->attributes['mime_type'];
        }
        
        // Sinon, le déduire du nom de fichier
        $filename = $this->name;
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        $mimeTypes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ];
        
        return $mimeTypes[$extension] ?? 'application/octet-stream';
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


<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UploadController extends Controller
{
    public function uploadMultiple(Request $request)
    {
        $request->validate([
            'files' => 'required|array',
            'files.*' => 'required|file|max:10240', // 10MB max per file
            'type' => 'required|string|in:id_document,photo,fiscal_document',
        ]);

        try {
            $files = $request->file('files');
            $type = $request->input('type');
            $uploadedFiles = [];
            
            foreach ($files as $file) {
                // Generate unique filename
                $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
                
                // Determine storage path based on type
                $path = match($type) {
                    'id_document' => 'uploads/id_documents',
                    'photo' => 'uploads/photos',
                    'fiscal_document' => 'uploads/fiscal_documents',
                    default => 'uploads/misc'
                };
                
                // Store file
                $filePath = $file->storeAs($path, $filename, 'public');
                
                $uploadedFiles[] = [
                    'id' => Str::uuid(),
                    'name' => $file->getClientOriginalName(),
                    'path' => $filePath,
                    'url' => Storage::url($filePath),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString()
                ];
            }
            
            return response()->json([
                'success' => true,
                'files' => $uploadedFiles
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement des fichiers',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function upload(Request $request)
    {
        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
            'type' => 'required|string|in:id_document,photo,fiscal_document',
        ]);

        try {
            $file = $request->file('file');
            $type = $request->input('type');
            
            // Generate unique filename
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            
            // Determine storage path based on type
            $path = match($type) {
                'id_document' => 'uploads/id_documents',
                'photo' => 'uploads/photos',
                'fiscal_document' => 'uploads/fiscal_documents',
                default => 'uploads/misc'
            };
            
            // Store file
            $filePath = $file->storeAs($path, $filename, 'public');
            
            return response()->json([
                'success' => true,
                'file' => [
                    'id' => Str::uuid(),
                    'name' => $file->getClientOriginalName(),
                    'path' => $filePath,
                    'url' => Storage::url($filePath),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'uploaded_at' => now()->toISOString()
                ]
            ], 201);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du téléchargement du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete(Request $request)
    {
        $request->validate([
            'path' => 'required|string'
        ]);

        try {
            $path = $request->input('path');
            
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Fichier supprimé avec succès'
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Fichier non trouvé'
            ], 404);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du fichier',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

<?php

namespace App\Services;

use App\Models\DocumentTemplate;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DocumentPdfService
{
    /**
     * @param  array<string, mixed>  $variables
     */
    public function render(string $templateCode, array $variables): string
    {
        $template = DocumentTemplate::query()
            ->where('code', $templateCode)
            ->where('is_active', true)
            ->first();

        if (! $template) {
            throw ValidationException::withMessages([
                'template' => "Template dokumen '{$templateCode}' belum tersedia atau tidak aktif.",
            ]);
        }

        $replacements = [];
        foreach ($variables as $key => $value) {
            $replacements['{'.$key.'}'] = nl2br(e((string) ($value ?? '—')));
        }

        $html = strtr($template->body, $replacements);

        return (string) preg_replace('/\{[a-z0-9_]+\}/i', '—', $html);
    }

    /**
     * @param  array<string, mixed>  $variables
     */
    public function generate(string $templateCode, array $variables, string $folder, string $filename): string
    {
        $html = $this->render($templateCode, $variables);
        $output = Pdf::loadHTML($html)->setPaper('a4')->output();

        $path = 'documents/'.trim($folder, '/').'/'.Str::slug($filename).'.pdf';
        Storage::disk('local')->put($path, $output);

        return $path;
    }
}

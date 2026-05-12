<?php

namespace App\Services;

class SimplePdfService
{
    public function make(array $lines): string
    {
        $pageLines = [];
        $current = [];

        foreach ($lines as $line) {
            foreach ($this->wrap((string) $line, 96) as $wrappedLine) {
                $current[] = $wrappedLine;
                if (count($current) >= 48) {
                    $pageLines[] = $current;
                    $current = [];
                }
            }
        }

        if ($current) {
            $pageLines[] = $current;
        }

        $objects = [];
        $pages = [];

        foreach ($pageLines as $index => $linesOnPage) {
            $contentId = 4 + ($index * 2);
            $pageId = $contentId + 1;
            $pages[] = $pageId;

            $stream = "BT\n/F1 10 Tf\n50 790 Td\n14 TL\n";
            foreach ($linesOnPage as $line) {
                $stream .= '(' . $this->escape($this->ascii($line)) . ") Tj\nT*\n";
            }
            $stream .= "ET";

            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = "<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 3 0 R >> >> /MediaBox [0 0 595 842] /Contents {$contentId} 0 R >>";
        }

        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', array_map(fn ($id) => "{$id} 0 R", $pages)) . '] /Count ' . count($pages) . ' >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n";
        $offsets = [0];

        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    private function wrap(string $line, int $width): array
    {
        if ($line === '') {
            return [''];
        }

        return explode("\n", wordwrap($line, $width, "\n", true));
    }

    private function escape(string $value): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
    }

    private function ascii(string $value): string
    {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted === false ? preg_replace('/[^\x20-\x7E]/', '', $value) : $converted;
    }
}

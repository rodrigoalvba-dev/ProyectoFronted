<?php
/**
 * Generador de PDF mínimo (sin dependencias). Hoja Carta, fuentes Helvetica.
 * Coordenadas en puntos, origen arriba a la izquierda.
 */
class SimplePDF {
    public int $w = 612;
    public int $h = 792;
    private array $pages = [];
    private string $cur = '';
    private bool $open = false;

    // Anchos de Helvetica para ASCII 32..126 (en milésimas de em)
    private const CW = [278,278,355,556,556,889,667,191,333,333,389,584,278,333,278,278,
        556,556,556,556,556,556,556,556,556,556,278,278,584,584,584,556,1015,
        667,667,722,722,667,611,778,722,278,500,667,556,833,722,778,667,778,722,667,611,722,667,944,667,667,611,
        278,278,278,469,556,333,
        556,556,500,556,556,278,556,556,222,222,500,222,833,556,556,556,556,333,500,278,556,500,722,500,500,500,
        334,260,334,584];

    public function addPage(): void {
        if ($this->open) $this->pages[] = $this->cur;
        $this->cur = '';
        $this->open = true;
    }

    private function enc(string $s): string {
        $r = @iconv('UTF-8', 'CP1252//TRANSLIT//IGNORE', $s);
        return $r === false ? $s : $r;
    }
    private function esc(string $s): string { return str_replace(['\\', '(', ')', "\r"], ['\\\\', '\\(', '\\)', ''], $s); }
    private function col(array $c): string { return sprintf('%.3F %.3F %.3F', $c[0] / 255, $c[1] / 255, $c[2] / 255); }
    private function f(float $n): string { return sprintf('%.2F', $n); }

    public function width(string $s, float $size = 10, bool $bold = false): float {
        $e = $this->enc($s); $w = 0;
        for ($i = 0, $n = strlen($e); $i < $n; $i++) {
            $o = ord($e[$i]);
            $w += ($o >= 32 && $o <= 126) ? self::CW[$o - 32] : 556;
        }
        return $w * $size / 1000 * ($bold ? 1.06 : 1);
    }

    public function text(float $x, float $y, string $s, float $size = 10, bool $bold = false, array $color = [20, 20, 19]): void {
        $font = $bold ? 'F2' : 'F1';
        $this->cur .= sprintf("BT /%s %s Tf %s rg %s %s Td (%s) Tj ET\n",
            $font, $this->f($size), $this->col($color), $this->f($x), $this->f($this->h - $y), $this->esc($this->enc($s)));
    }
    public function textRight(float $xr, float $y, string $s, float $size = 10, bool $bold = false, array $color = [20, 20, 19]): void {
        $this->text($xr - $this->width($s, $size, $bold), $y, $s, $size, $bold, $color);
    }
    public function rect(float $x, float $y, float $w, float $h, array $fill): void {
        $this->cur .= sprintf("%s rg %s %s %s %s re f\n", $this->col($fill), $this->f($x), $this->f($this->h - $y - $h), $this->f($w), $this->f($h));
    }
    public function line(float $x1, float $y1, float $x2, float $y2, array $color = [200, 205, 202], float $lw = 0.6): void {
        $this->cur .= sprintf("%s RG %s w %s %s m %s %s l S\n", $this->col($color), $this->f($lw),
            $this->f($x1), $this->f($this->h - $y1), $this->f($x2), $this->f($this->h - $y2));
    }

    public function output(): string {
        if ($this->open) { $this->pages[] = $this->cur; $this->open = false; }
        $objs = [];
        $n = count($this->pages);
        $objs[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $kids = [];
        for ($i = 0; $i < $n; $i++) $kids[] = (5 + $i * 2) . ' 0 R';
        $objs[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . $n . ' >>';
        $objs[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objs[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';
        foreach ($this->pages as $i => $content) {
            $po = 5 + $i * 2; $co = $po + 1;
            $objs[$po] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$this->w} {$this->h}] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents $co 0 R >>";
            $objs[$co] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
        }
        $pdf = "%PDF-1.4\n";
        $off = [];
        ksort($objs);
        foreach ($objs as $num => $body) {
            $off[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$body\nendobj\n";
        }
        $xref = strlen($pdf);
        $max = max(array_keys($objs));
        $pdf .= "xref\n0 " . ($max + 1) . "\n0000000000 65535 f \n";
        for ($i = 1; $i <= $max; $i++) $pdf .= sprintf("%010d 00000 n \n", $off[$i]);
        $pdf .= "trailer\n<< /Size " . ($max + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF";
        return $pdf;
    }
}

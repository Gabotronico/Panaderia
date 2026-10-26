<?php

namespace App\Support\Reportes;

use Carbon\CarbonInterface;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Libro de Excel con el formato común de los reportes: encabezado con el
 * negocio, el título y el período, tablas con encabezado de color, montos en
 * bolivianos, fechas de Excel de verdad (se ordenan y filtran) y totales con
 * fórmulas, para que sigan cuadrando si el usuario edita una celda.
 */
final class LibroExcel
{
    private const AZUL = 'FF12304F';
    private const ORO = 'FFD9A441';
    private const GRIS = 'FFF1F5F9';

    /** Formato de número por tipo de columna. */
    private const FORMATOS = [
        'moneda' => '"Bs "#,##0.00',
        'entero' => '#,##0',
        'decimal' => '#,##0.00',
        'porcentaje' => '0.0%',
        'fecha' => 'dd/mm/yyyy',
        'fechahora' => 'dd/mm/yyyy hh:mm',
        'hora' => 'hh:mm',
    ];

    private Spreadsheet $libro;

    private bool $primeraHoja = true;

    public function __construct(private string $titulo, private ?string $periodo = null)
    {
        $this->libro = new Spreadsheet;
        $this->libro->getProperties()
            ->setCreator((string) config('app.name'))
            ->setTitle($titulo);
        $this->libro->getDefaultStyle()->getFont()->setName('Calibri')->setSize(11);
    }

    /**
     * Agrega una hoja con el encabezado del reporte y devuelve la hoja y la
     * primera fila libre debajo del encabezado.
     *
     * @return array{0: Worksheet, 1: int}
     */
    public function hoja(string $nombre): array
    {
        if ($this->primeraHoja) {
            $hoja = $this->libro->getActiveSheet();
            $this->primeraHoja = false;
        } else {
            $hoja = $this->libro->createSheet();
        }
        $hoja->setTitle(mb_substr($nombre, 0, 31));

        $hoja->setCellValue('A1', (string) config('app.name'));
        $hoja->getStyle('A1')->getFont()->setBold(true)->setSize(15)->getColor()->setARGB(self::AZUL);
        $hoja->setCellValue('A2', $this->titulo.' — '.$nombre);
        $hoja->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $hoja->setCellValue('A3', trim(($this->periodo ? $this->periodo.'  ·  ' : '').'Generado el '.now()->format('d/m/Y H:i')));
        $hoja->getStyle('A3')->getFont()->setItalic(true)->setSize(9)->getColor()->setARGB('FF64748B');

        // Combinadas para que el ancho automático de la columna A no tome el
        // largo del título: las celdas combinadas no cuentan para el cálculo.
        foreach ([1, 2, 3] as $f) {
            $hoja->mergeCells("A{$f}:H{$f}");
        }

        return [$hoja, 5];
    }

    /**
     * Pares "indicador → valor" en dos columnas, para los resúmenes.
     *
     * @param  array<string, array{0: mixed, 1: string}>  $datos  etiqueta => [valor, tipo]
     */
    public function indicadores(Worksheet $hoja, int $fila, string $subtitulo, array $datos): int
    {
        $fila = $this->subtitulo($hoja, $fila, $subtitulo);

        $anchos = [0, 0];
        foreach ($datos as $etiqueta => [$valor, $tipo]) {
            $anchos = [max($anchos[0], mb_strlen($etiqueta)), max($anchos[1], $this->largo($valor, $tipo))];
            $hoja->setCellValue([1, $fila], $etiqueta);
            $hoja->getStyle([1, $fila])->getFont()->getColor()->setARGB('FF475569');
            $this->escribir($hoja, 2, $fila, $valor, $tipo);
            $hoja->getStyle([2, $fila])->getFont()->setBold(true);
            $fila++;
        }

        $this->ajustarAnchos($hoja, $anchos);

        return $fila + 1;
    }

    /**
     * Escribe una tabla y devuelve la primera fila libre debajo.
     *
     * @param  list<array{titulo: string, tipo?: string}>  $columnas
     * @param  iterable<array<int, mixed>>  $filas  cada fila, un valor por columna
     * @param  array<int, string>  $totales  índice de columna (0..n) => 'suma' o un texto
     * @param  bool  $filtros  filtro por columna (solo una tabla por hoja)
     */
    public function tabla(
        Worksheet $hoja,
        int $fila,
        array $columnas,
        iterable $filas,
        array $totales = [],
        ?string $subtitulo = null,
        bool $filtros = false,
    ): int {
        if ($subtitulo !== null) {
            $fila = $this->subtitulo($hoja, $fila, $subtitulo);
        }

        $cantidad = \count($columnas);
        $encabezado = $fila;
        // El encabezado se parte en dos líneas si hace falta: cuenta hasta 16.
        $anchos = array_map(fn ($c) => min(16, mb_strlen($c['titulo'])), $columnas);

        foreach ($columnas as $i => $c) {
            $hoja->setCellValue([$i + 1, $fila], $c['titulo']);
        }
        $estilo = $hoja->getStyle([1, $fila, $cantidad, $fila]);
        $estilo->getFont()->setBold(true)->getColor()->setARGB('FFFFFFFF');
        $estilo->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::AZUL);
        $estilo->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setWrapText(true);
        $hoja->getRowDimension($fila)->setRowHeight(22);

        $primera = $fila + 1;
        $fila = $primera;
        foreach ($filas as $valores) {
            foreach (array_values($valores) as $i => $valor) {
                $tipo = $columnas[$i]['tipo'] ?? 'texto';
                $this->escribir($hoja, $i + 1, $fila, $valor, $tipo);
                $anchos[$i] = max($anchos[$i], $this->largo($valor, $tipo));
            }
            if (($fila - $primera) % 2 === 1) {
                $hoja->getStyle([1, $fila, $cantidad, $fila])->getFill()
                    ->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB(self::GRIS);
            }
            $fila++;
        }
        $ultima = $fila - 1;

        if ($ultima < $primera) {
            $hoja->setCellValue([1, $fila], 'Sin datos en el período.');
            $hoja->getStyle([1, $fila])->getFont()->setItalic(true)->getColor()->setARGB('FF64748B');
            $fila++;
        } elseif ($totales !== []) {
            foreach ($totales as $i => $total) {
                $letra = Coordinate::stringFromColumnIndex($i + 1);
                if ($total === 'suma') {
                    $hoja->setCellValue([$i + 1, $fila], "=SUM({$letra}{$primera}:{$letra}{$ultima})");
                    $formato = self::FORMATOS[$columnas[$i]['tipo'] ?? ''] ?? null;
                    if ($formato) {
                        $hoja->getStyle([$i + 1, $fila])->getNumberFormat()->setFormatCode($formato);
                    }
                } else {
                    $hoja->setCellValue([$i + 1, $fila], $total);
                }
            }
            $estilo = $hoja->getStyle([1, $fila, $cantidad, $fila]);
            $estilo->getFont()->setBold(true);
            $estilo->getBorders()->getTop()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setARGB(self::ORO);
            $fila++;
        }

        if ($filtros && $ultima >= $primera) {
            $desde = Coordinate::stringFromColumnIndex(1).$encabezado;
            $hasta = Coordinate::stringFromColumnIndex($cantidad).$ultima;
            $hoja->setAutoFilter("{$desde}:{$hasta}");
            $hoja->freezePane('A'.($encabezado + 1));
        }

        $this->ajustarAnchos($hoja, $anchos);

        return $fila + 1;
    }

    public function descargar(string $archivo): StreamedResponse
    {
        $this->libro->setActiveSheetIndex(0);
        $escritor = new Xlsx($this->libro);

        return new StreamedResponse(fn () => $escritor->save('php://output'), 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="'.$archivo.'"',
            'Cache-Control' => 'max-age=0, no-store',
        ]);
    }

    private function subtitulo(Worksheet $hoja, int $fila, string $texto): int
    {
        $hoja->setCellValue([1, $fila], $texto);
        $hoja->getStyle([1, $fila])->getFont()->setBold(true)->setSize(12)->getColor()->setARGB(self::AZUL);

        return $fila + 1;
    }

    private function escribir(Worksheet $hoja, int $columna, int $fila, mixed $valor, string $tipo): void
    {
        if ($valor === null || $valor === '') {
            return;
        }

        if ($valor instanceof CarbonInterface) {
            $valor = FechaExcel::PHPToExcel($valor);
        } elseif (\in_array($tipo, ['moneda', 'entero', 'decimal', 'porcentaje'], true) && is_numeric($valor)) {
            $valor = (float) $valor;
        }

        $hoja->setCellValue([$columna, $fila], $valor);

        if (isset(self::FORMATOS[$tipo])) {
            $hoja->getStyle([$columna, $fila])->getNumberFormat()->setFormatCode(self::FORMATOS[$tipo]);
        }
    }

    /**
     * Ancho de cada columna según su texto más largo. El ancho automático de
     * PhpSpreadsheet es una estimación que se queda corta con encabezados de
     * dos líneas y partía los nombres. Una columna compartida por varias
     * tablas de la misma hoja queda con el ancho mayor.
     *
     * @param  array<int, int>  $anchos  caracteres por columna (desde 0)
     */
    private function ajustarAnchos(Worksheet $hoja, array $anchos): void
    {
        foreach (array_values($anchos) as $i => $caracteres) {
            $dimension = $hoja->getColumnDimension(Coordinate::stringFromColumnIndex($i + 1));
            $ancho = min(60, max(9, $caracteres + 3));
            $dimension->setWidth(max($ancho, $dimension->getWidth()));
        }
    }

    /** Caracteres que ocupa un valor ya formateado ("Bs 20,728.00", "21/10/2026 20:59"). */
    private function largo(mixed $valor, string $tipo): int
    {
        if ($valor === null || $valor === '') {
            return 0;
        }

        return match ($tipo) {
            'moneda' => mb_strlen('Bs '.number_format((float) $valor, 2)),
            'entero' => mb_strlen(number_format((float) $valor)),
            'decimal' => mb_strlen(number_format((float) $valor, 2)),
            'porcentaje' => 7,
            'fecha' => 10,
            'fechahora' => 16,
            default => mb_strlen((string) $valor),
        };
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;
use PhpOffice\PhpWord\SimpleType\TblWidth;
use PhpOffice\PhpWord\Shared\Converter;

class GenerarDocumentacion extends Command
{
    protected $signature   = 'docs:generar {--salida=documentacion/Manual-Panaderia-Luna.docx}';
    protected $description = 'Genera el manual del sistema en formato Word (.docx)';

    private PhpWord $doc;
    private $seccion;

    // Paleta
    private const AZUL   = '1F3864';
    private const AZUL2  = '2E5C9A';
    private const GRIS   = '595959';
    private const VERDE  = '1E7145';
    private const ROJO   = 'B02418';
    private const FONDO  = 'F2F5FA';
    private const BORDE  = 'C5CEDD';

    public function handle(): int
    {
        $this->info('Generando manual del sistema…');

        $this->doc = new PhpWord();
        $this->configurarEstilos();

        $this->portada();
        $this->indice();
        $this->introduccion();
        $this->instalacion();
        $this->usuariosYRoles();
        $this->modulos();
        $this->baseDeDatos();
        $this->reglasDeNegocio();
        $this->guiaDeUso();
        $this->mantenimiento();

        $ruta = base_path($this->option('salida'));
        if (!is_dir(dirname($ruta))) {
            mkdir(dirname($ruta), 0755, true);
        }

        IOFactory::createWriter($this->doc, 'Word2007')->save($ruta);

        $this->newLine();
        $this->info('Documento generado:');
        $this->line('  ' . $ruta);
        $this->line('  ' . number_format(filesize($ruta) / 1024, 1) . ' KB');
        $this->newLine();
        $this->comment('Al abrirlo en Word, hacé clic derecho sobre el índice y elegí');
        $this->comment('"Actualizar campos" para que se numeren las páginas.');

        return self::SUCCESS;
    }

    // ─────────────────────────────────────────────────────────────
    //  ESTILOS
    // ─────────────────────────────────────────────────────────────
    private function configurarEstilos(): void
    {
        $this->doc->setDefaultFontName('Calibri');
        $this->doc->setDefaultFontSize(11);

        $props = $this->doc->getDocInfo();
        $props->setCreator('Panadería Luna');
        $props->setTitle('Manual del Sistema de Gestión');
        $props->setDescription('Funcionamiento, base de datos y guía de uso');

        $this->doc->addTitleStyle(1,
            ['size' => 20, 'bold' => true, 'color' => self::AZUL],
            ['spaceBefore' => 360, 'spaceAfter' => 180, 'keepNext' => true]);
        $this->doc->addTitleStyle(2,
            ['size' => 15, 'bold' => true, 'color' => self::AZUL2],
            ['spaceBefore' => 300, 'spaceAfter' => 120, 'keepNext' => true]);
        $this->doc->addTitleStyle(3,
            ['size' => 12, 'bold' => true, 'color' => self::GRIS],
            ['spaceBefore' => 200, 'spaceAfter' => 80, 'keepNext' => true]);

        // Tabla de datos estándar
        $this->doc->addTableStyle('datos', [
            'borderColor' => self::BORDE, 'borderSize' => 6,
            'cellMargin'  => 70, 'width' => 100 * 50, 'unit' => TblWidth::PERCENT,
        ], [
            'bgColor' => self::AZUL, 'borderColor' => self::AZUL,
        ]);

        // Tabla sin bordes para bloques informativos
        $this->doc->addTableStyle('bloque', [
            'borderColor' => self::BORDE, 'borderSize' => 6,
            'cellMargin'  => 120, 'width' => 100 * 50, 'unit' => TblWidth::PERCENT,
            'bgColor'     => self::FONDO,
        ]);

        // Marco para capturas de pantalla
        $this->doc->addTableStyle('captura', [
            'borderColor' => '9AA9C0', 'borderSize' => 12, 'borderStyle' => 'dashed',
            'cellMargin'  => 200, 'width' => 100 * 50, 'unit' => TblWidth::PERCENT,
        ]);
    }

    private function nuevaSeccion(): void
    {
        $this->seccion = $this->doc->addSection([
            'pageSizeW'    => (int) round(Converter::cmToTwip(21.0)),
            'pageSizeH'    => (int) round(Converter::cmToTwip(29.7)),
            'marginTop'    => (int) round(Converter::cmToTwip(2.2)),
            'marginBottom' => (int) round(Converter::cmToTwip(2.0)),
            'marginLeft'   => (int) round(Converter::cmToTwip(2.5)),
            'marginRight'  => (int) round(Converter::cmToTwip(2.2)),
        ]);

        $enc = $this->seccion->addHeader();
        $enc->addText('Panadería Luna · Manual del Sistema',
            ['size' => 8, 'color' => self::GRIS], ['alignment' => Jc::END]);

        $pie = $this->seccion->addFooter();
        $pie->addPreserveText('Página {PAGE} de {NUMPAGES}',
            ['size' => 8, 'color' => self::GRIS], ['alignment' => Jc::CENTER]);
    }

    // ─────────────────────────────────────────────────────────────
    //  HELPERS DE CONTENIDO
    // ─────────────────────────────────────────────────────────────

    /** Párrafo normal justificado. */
    private function p(string $texto, array $fuente = []): void
    {
        $this->seccion->addText($texto, $fuente,
            ['alignment' => Jc::BOTH, 'spaceAfter' => 120, 'lineHeight' => 1.15]);
    }

    /** Ítem de lista con viñeta. */
    private function vinieta(string $texto, int $nivel = 0): void
    {
        $this->seccion->addListItem($texto, $nivel, [],
            ['listType' => \PhpOffice\PhpWord\Style\ListItem::TYPE_BULLET_FILLED],
            ['spaceAfter' => 60, 'lineHeight' => 1.1]);
    }

    /** Bloque destacado con fondo gris. */
    private function nota(string $titulo, string $cuerpo, string $color = self::AZUL2): void
    {
        $t = $this->seccion->addTable('bloque');
        $t->addRow();
        $c = $t->addCell(null, ['bgColor' => self::FONDO]);
        $c->addText($titulo, ['bold' => true, 'size' => 10, 'color' => $color],
            ['spaceAfter' => 60]);
        $c->addText($cuerpo, ['size' => 10],
            ['alignment' => Jc::BOTH, 'spaceAfter' => 0, 'lineHeight' => 1.1]);
        $this->seccion->addTextBreak(1, ['size' => 6]);
    }

    /** Marco donde el usuario pegará una captura de pantalla. */
    private function captura(string $queMostrar, string $donde): void
    {
        $t = $this->seccion->addTable('captura');
        $t->addRow((int) round(Converter::cmToTwip(3.4)));
        $c = $t->addCell(null, ['valign' => 'center']);
        $c->addText('[ ESPACIO PARA CAPTURA DE PANTALLA ]',
            ['bold' => true, 'size' => 10, 'color' => '7A8AA5'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);
        $c->addText($queMostrar,
            ['size' => 10, 'color' => self::GRIS],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 40]);
        $c->addText('Ruta: ' . $donde,
            ['size' => 9, 'italic' => true, 'color' => '8C8C8C'],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 0]);
        $this->seccion->addTextBreak(1, ['size' => 8]);
    }

    /** Tabla con encabezado. $filas = [[c1,c2,…], …] */
    private function tabla(array $encabezados, array $filas, array $anchos = []): void
    {
        $t = $this->seccion->addTable('datos');

        $t->addRow(null, ['tblHeader' => true]);
        foreach ($encabezados as $i => $h) {
            $t->addCell($anchos[$i] ?? null, ['bgColor' => self::AZUL, 'valign' => 'center'])
              ->addText($h, ['bold' => true, 'size' => 9, 'color' => 'FFFFFF'],
                        ['spaceAfter' => 0]);
        }

        foreach ($filas as $n => $fila) {
            $t->addRow();
            $fondo = $n % 2 === 0 ? 'FFFFFF' : 'F7F9FC';
            foreach ($fila as $i => $celda) {
                $t->addCell($anchos[$i] ?? null, ['bgColor' => $fondo, 'valign' => 'center'])
                  ->addText((string) $celda, ['size' => 9], ['spaceAfter' => 0]);
            }
        }
        $this->seccion->addTextBreak(1, ['size' => 8]);
    }

    /** Bloque de código / comandos. */
    private function codigo(array $lineas): void
    {
        $t = $this->seccion->addTable('bloque');
        $t->addRow();
        $c = $t->addCell(null, ['bgColor' => '2B2B2B']);
        foreach ($lineas as $l) {
            $c->addText($l, ['name' => 'Consolas', 'size' => 9, 'color' => 'E8E8E8'],
                ['spaceAfter' => 20]);
        }
        $this->seccion->addTextBreak(1, ['size' => 8]);
    }

    // ─────────────────────────────────────────────────────────────
    //  1. PORTADA
    // ─────────────────────────────────────────────────────────────
    private function portada(): void
    {
        $s = $this->doc->addSection([
            'pageSizeW' => (int) round(Converter::cmToTwip(21.0)),
            'pageSizeH' => (int) round(Converter::cmToTwip(29.7)),
            'marginTop' => (int) round(Converter::cmToTwip(6.0)),
        ]);

        $s->addText('SISTEMA DE GESTIÓN',
            ['size' => 15, 'color' => self::GRIS, 'spacing' => 60],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 60]);

        $s->addText('Panadería Luna',
            ['size' => 40, 'bold' => true, 'color' => self::AZUL],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 200]);

        $s->addText('Manual de funcionamiento, base de datos y guía de uso',
            ['size' => 13, 'color' => self::AZUL2],
            ['alignment' => Jc::CENTER, 'spaceAfter' => 600]);

        $t = $s->addTable(['width' => 55 * 50, 'unit' => TblWidth::PERCENT,
                           'alignment' => Jc::CENTER]);
        $datos = [
            ['Versión del documento', '1.1'],
            ['Fecha de emisión', now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY')],
            ['Plataforma', 'NativePHP Desktop · Laravel ' . app()->version() . ' · PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION],
            ['Base de datos', 'SQLite'],
        ];
        foreach ($datos as $d) {
            $t->addRow();
            $t->addCell(3400)->addText($d[0], ['size' => 10, 'bold' => true, 'color' => self::GRIS], ['spaceAfter' => 40]);
            $t->addCell(3400)->addText($d[1], ['size' => 10], ['spaceAfter' => 40]);
        }
    }

    // ─────────────────────────────────────────────────────────────
    //  2. ÍNDICE
    // ─────────────────────────────────────────────────────────────
    private function indice(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addText('Contenido',
            ['size' => 18, 'bold' => true, 'color' => self::AZUL],
            ['spaceAfter' => 240]);

        $this->seccion->addTOC(['size' => 11], ['tabLeader' => 'dot'], 1, 2);

        $this->seccion->addTextBreak(2);
        $this->nota('Cómo actualizar este índice',
            'Al abrir el documento en Word, hacé clic derecho sobre el índice y elegí ' .
            '«Actualizar campos» → «Actualizar toda la tabla». Word calculará los números ' .
            'de página automáticamente.');
    }

    // ─────────────────────────────────────────────────────────────
    //  3. INTRODUCCIÓN
    // ─────────────────────────────────────────────────────────────
    private function introduccion(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('1. Introducción', 1);

        $this->seccion->addTitle('1.1 Qué es el sistema', 2);
        $this->p('El Sistema de Gestión de Panadería Luna es una aplicación de escritorio que administra ' .
            'la operación completa del negocio: desde la compra de insumos y la producción con ' .
            'recetas, hasta la venta al público, el pago del personal y el control de los gastos ' .
            'fijos del local.');
        $this->p('La aplicación se instala en una sola PC y abre en su propia ventana. Incluye el entorno ' .
            'necesario para ejecutar Laravel y guarda los datos localmente con SQLite; no requiere ' .
            'XAMPP, MySQL, Composer ni un navegador. La conexión a Internet solo es necesaria para ' .
            'descargar el instalador o una actualización, no para el uso diario.');

        $this->seccion->addTitle('1.2 Qué problemas resuelve', 2);
        $this->vinieta('Saber cuánto stock hay, tanto en bodega como en cada punto de venta.');
        $this->vinieta('Registrar las ventas y cuadrar la caja al cierre de cada turno.');
        $this->vinieta('Controlar el consumo de insumos al producir mediante recetas.');
        $this->vinieta('Llevar la asistencia del personal y generar las planillas de pago.');
        $this->vinieta('No olvidar el pago de alquiler, luz, agua y demás gastos recurrentes.');
        $this->vinieta('Responder la pregunta más importante: ¿el negocio está ganando o perdiendo dinero?');

        $this->seccion->addTitle('1.3 Módulos que lo componen', 2);
        $this->tabla(
            ['Módulo', 'Para qué sirve', 'Quién lo usa'],
            [
                ['Catálogo',           'Categorías y productos que se venden',              'Administrador'],
                ['Insumos',            'Materia prima, compras y mermas',                   'Administrador'],
                ['Recetas',            'Fórmulas de producción y consumo de insumos',       'Administrador'],
                ['Almacenes',          'Puntos de venta y distribución de stock',           'Administrador'],
                ['Ventas',             'Registro de ventas al público',                     'Cajero y Administrador'],
                ['Cortes de caja',     'Apertura y cierre de turno con arqueo',             'Cajero y Administrador'],
                ['Recursos Humanos',   'Empleados, asistencia, planillas y adelantos',      'Administrador'],
                ['Gastos Fijos',       'Alquiler, servicios e impuestos recurrentes',       'Administrador'],
                ['Resumen Financiero', 'Estado de resultados consolidado del negocio',      'Administrador'],
                ['Reportes',           'Consultas de ventas, inventario y cortes',          'Administrador'],
            ],
            [1800, 4600, 2600]
        );

        $this->seccion->addTitle('1.4 Tecnologías utilizadas', 2);
        $this->tabla(
            ['Componente', 'Tecnología', 'Función'],
            [
                ['Lenguaje',        'PHP ' . PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION, 'Lógica del negocio'],
                ['Framework',       'Laravel ' . app()->version(),   'Estructura de la aplicación'],
                ['Base de datos',   'SQLite',                        'Almacenamiento local en un solo archivo'],
                ['Escritorio',      'NativePHP + Electron',          'Ventana, runtime e instalador de Windows'],
                ['Interfaz',        'Bootstrap 5',                   'Diseño de las pantallas'],
                ['Permisos',        'Spatie Laravel Permission',     'Roles y accesos por usuario'],
                ['Reportes PDF',    'DomPDF',                        'Generación de planillas imprimibles'],
                ['Gráficos',        'Chart.js y SVG propio',         'Visualización de datos'],
            ],
            [2200, 3000, 3800]
        );
    }

    // ─────────────────────────────────────────────────────────────
    //  4. INSTALACIÓN
    // ─────────────────────────────────────────────────────────────
    private function instalacion(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('2. Instalación y puesta en marcha', 1);

        $this->seccion->addTitle('2.1 Requisitos previos', 2);
        $this->tabla(
            ['Requisito', 'Recomendación', 'Observación'],
            [
                ['Sistema operativo', 'Windows 10 u 11 de 64 bits', 'Con actualizaciones de seguridad'],
                ['Espacio libre',      '1 GB o más',                  'Aplicación, datos, imágenes y respaldos'],
                ['Permisos de Windows','Usuario que pueda instalar', 'Solo se necesitan durante la instalación'],
                ['Internet',           'Para descargar el instalador','El funcionamiento diario es local'],
            ],
            [2600, 3000, 3400]
        );

        $this->seccion->addTitle('2.2 Pasos de instalación', 2);
        $this->pasos([
            'Cerrá Panadería Escritorio si hay una versión anterior abierta.',
            'Ejecutá el archivo «Panadería Escritorio-x.y.z-setup.exe».',
            'Seguí el asistente de Windows hasta completar la instalación.',
            'Abrí «Panadería Escritorio» desde el menú Inicio o su acceso directo.',
            'En el primer inicio, completá el nombre del negocio y creá el usuario administrador.',
            'Ingresá con ese usuario y verificá que aparezca el panel principal.',
        ]);

        $this->nota('Actualizar sin perder los datos',
            'Para actualizar, cerrá la aplicación y ejecutá el instalador nuevo sobre la versión ' .
            'existente. No desinstales ni borres la carpeta de datos de AppData. Antes de actualizar, ' .
            'creá un respaldo desde «Sistema → Respaldos».', self::AZUL2);

        $this->seccion->addTitle('2.3 Base de datos local', 2);
        $this->p('La aplicación utiliza SQLite y configura la base automáticamente. No hay que crear ' .
            'una base, instalar un servicio ni editar archivos de configuración. La información se ' .
            'guarda dentro del perfil de Windows del usuario que ejecuta la aplicación.');

        $this->codigo([
            'Motor: SQLite',
            'Archivo principal: database.sqlite',
            'Ubicación: carpeta de datos local de Panadería Escritorio',
            'Servicio externo: no requiere MySQL ni XAMPP',
        ]);

        $this->nota('No editar el archivo manualmente',
            'No abras ni reemplaces «database.sqlite» mientras la aplicación esté funcionando. ' .
            'Para copiar o restaurar información usá siempre la pantalla «Sistema → Respaldos».');

        $this->seccion->addTitle('2.4 Acceso al sistema', 2);
        $this->p('Abrí Panadería Escritorio desde Windows. En el primer inicio aparecerá el asistente ' .
            'de configuración; en los siguientes inicios verás directamente la pantalla de acceso.');

        $this->captura('Pantalla de inicio de sesión del sistema',
            'Aplicación Panadería Escritorio → Inicio de sesión');
    }

    // ─────────────────────────────────────────────────────────────
    //  5. USUARIOS Y ROLES
    // ─────────────────────────────────────────────────────────────
    private function usuariosYRoles(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('3. Usuarios y roles', 1);

        $this->p('El sistema distingue dos tipos de usuario. Lo que cada uno puede ver y hacer ' .
            'está definido por su rol, y el menú lateral se adapta automáticamente para mostrar ' .
            'solo las secciones a las que tiene acceso.');

        $this->seccion->addTitle('3.1 Diferencias entre roles', 2);
        $this->tabla(
            ['Función', 'Administrador', 'Cajero'],
            [
                ['Ver el panel principal',            'Sí', 'Sí (solo sus datos)'],
                ['Registrar ventas',                  'Sí', 'Sí'],
                ['Abrir y cerrar caja',               'Sí', 'Sí'],
                ['Consultar productos y precios',     'Sí', 'Sí'],
                ['Crear o modificar productos',       'Sí', 'No'],
                ['Administrar insumos y recetas',     'Sí', 'No'],
                ['Recursos Humanos y planillas',      'Sí', 'No'],
                ['Gastos fijos',                      'Sí', 'No'],
                ['Resumen financiero',                'Sí', 'No'],
                ['Reportes',                          'Sí', 'No'],
            ],
            [4600, 2200, 2600]
        );

        $this->nota('Cómo se aplica esta separación',
            'El cajero ve el panel principal pero con la información acotada a sus propias ventas, ' .
            'y las secciones administrativas directamente no aparecen en su menú. Además, si ' .
            'intentara abrir una sección sin permiso, el sistema se lo impide.');

        $this->seccion->addTitle('3.2 Vista del administrador', 2);
        $this->captura('Panel principal completo, con las tarjetas de resumen y los paneles de operación diaria',
            'Aplicación Panadería Escritorio → Inicio (sesión de administrador)');

        $this->seccion->addTitle('3.3 Vista del cajero', 2);
        $this->captura('Panel principal del cajero, sin las secciones administrativas en el menú lateral',
            'Aplicación Panadería Escritorio → Inicio (sesión de cajero)');
    }

    // ─────────────────────────────────────────────────────────────
    //  6. MÓDULOS
    // ─────────────────────────────────────────────────────────────
    private function modulos(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('4. Módulos del sistema', 1);

        // ── Catálogo ──
        $this->seccion->addTitle('4.1 Catálogo de productos', 2);
        $this->p('Los productos son lo que la panadería vende. Cada uno pertenece a una categoría, ' .
            'tiene un precio de venta, una foto opcional y un stock en bodega con su nivel mínimo ' .
            'de alerta.');
        $this->p('El listado ofrece dos formas de verlo: una tabla clásica y una cuadrícula de ' .
            'tarjetas con las fotos. El sistema recuerda cuál elegiste. El buscador filtra ' .
            'mientras escribís, sin recargar la página.');
        $this->captura('Listado de productos en vista de tarjetas, con el buscador activo',
            'Menú lateral → Productos');

        // ── Insumos ──
        $this->seccion->addTitle('4.2 Insumos', 2);
        $this->p('Los insumos son la materia prima: harina, azúcar, levadura, manteca. Se registran ' .
            'con su unidad de medida y su costo unitario, y el sistema descuenta automáticamente ' .
            'las cantidades cuando se produce con una receta.');
        $this->vinieta('Compra: aumenta el stock y queda registrada como gasto del día.');
        $this->vinieta('Merma: descuenta stock por producto vencido, dañado o desperdiciado.');
        $this->captura('Listado de insumos mostrando stock actual y alertas de stock bajo',
            'Menú lateral → Insumos');

        // ── Recetas ──
        $this->seccion->addTitle('4.3 Recetas y producción', 2);
        $this->p('Una receta define qué insumos y en qué cantidad se necesitan para producir cierto ' .
            'número de unidades de un producto. Ese número es el rendimiento de la receta.');
        $this->p('Al ejecutar una producción se indica cuántas tandas se van a hacer. El sistema ' .
            'verifica que haya insumos suficientes, los descuenta y suma las unidades al stock ' .
            'del producto. Como el resultado real puede diferir del teórico, el campo de unidades ' .
            'producidas es editable: si salieron menos panes de los esperados, se corrige antes ' .
            'de confirmar.');
        $this->captura('Pantalla de producción mostrando los insumos que se consumirán y las unidades producidas',
            'Recetas → (elegir una) → Usar receta');

        // ── Almacenes ──
        $this->seccion->addTitle('4.4 Almacenes', 2);
        $this->p('El stock vive en dos niveles. Primero entra a la bodega central, que es el stock ' .
            'general del producto. Desde ahí se transfiere a los almacenes, que representan los ' .
            'puntos de venta. Cada cajero queda asignado a un almacén y vende contra el stock de ese lugar.');
        $this->captura('Detalle de un almacén con su stock asignado y los cajeros vinculados',
            'Menú lateral → Almacenes → (elegir uno)');

        // ── Ventas ──
        $this->seccion->addTitle('4.5 Ventas', 2);
        $this->p('La pantalla de venta permite armar el pedido agregando productos, aplicar un ' .
            'descuento si corresponde y cobrar en efectivo o por QR. Si el pago es en efectivo, ' .
            'el sistema calcula el cambio. Al confirmar, descuenta el stock del almacén.');
        $this->captura('Pantalla de registro de una nueva venta con productos agregados',
            'Menú lateral → Ventas → Nueva Venta');

        // ── Cortes ──
        $this->seccion->addTitle('4.6 Cortes de caja', 2);
        $this->p('Al comenzar el turno, el cajero abre la caja declarando el monto inicial. Al ' .
            'terminar, la cierra declarando el efectivo contado. El sistema compara ese monto con ' .
            'lo que debería haber según las ventas registradas y calcula la diferencia, que ' .
            'permite detectar faltantes o sobrantes.');
        $this->captura('Detalle de un corte de caja cerrado mostrando el arqueo y la diferencia',
            'Menú lateral → Cortes de Caja → (elegir uno)');

        // ── RRHH ──
        $this->seccion->addTitle('4.7 Recursos Humanos', 2);
        $this->p('Este módulo cubre el ciclo completo del personal y se compone de cuatro partes ' .
            'que trabajan encadenadas.');

        $this->seccion->addTitle('Cargos', 3);
        $this->p('Los puestos de trabajo de la panadería: panadero, ayudante, vendedor. Sirven ' .
            'para clasificar al personal.');

        $this->seccion->addTitle('Empleados', 3);
        $this->p('La ficha de cada trabajador incluye sus datos personales, el cargo, el salario ' .
            'base y la modalidad de pago (mensual o semanal). El sistema muestra automáticamente ' .
            'las equivalencias del sueldo: cuánto representa por día, por semana, por mes y por hora.');
        $this->captura('Listado de empleados con el resumen del personal y las equivalencias salariales',
            'Menú lateral → Empleados');

        $this->seccion->addTitle('Asistencias', 3);
        $this->p('Se registra día por día el estado de cada empleado, junto con la hora de entrada ' .
            'y de salida. Los estados posibles son: presente, ausente, tardanza, medio día, feriado ' .
            'y licencia. Si se abre una fecha ya registrada, el formulario aparece con los datos ' .
            'cargados para poder corregirlos.');
        $this->captura('Formulario de registro de asistencia diaria de todo el personal',
            'Menú lateral → Asistencias → Registrar Asistencia');

        $this->seccion->addTitle('Planillas', 3);
        $this->p('La planilla toma las asistencias del período y calcula lo que se le debe a cada ' .
            'empleado. Se generan por separado según la modalidad de pago: una planilla para el ' .
            'personal mensual y otra para el semanal, nunca mezclados.');
        $this->p('Una planilla pasa por tres estados. Nace como borrador, que permite revisarla. ' .
            'Luego se cierra, lo que congela los montos. Finalmente se marca como pagada cuando ' .
            'el dinero se entregó.');
        $this->captura('Detalle de una planilla mostrando el cálculo por empleado',
            'Menú lateral → Planillas → (elegir una)');

        $this->p('Desde el detalle se descarga un PDF en formato horizontal, pensado para ' .
            'imprimirlo y hacerlo firmar. Incluye una columna de firma junto al nombre y el ' .
            'carnet de cada trabajador.');
        $this->captura('PDF de la planilla listo para imprimir y firmar',
            'Planillas → (elegir una) → Descargar PDF');

        $this->seccion->addTitle('Adelantos', 3);
        $this->p('Desde la ficha del empleado se registran los adelantos de sueldo. El sistema ' .
            'controla que no se adelante más de lo que la persona gana en el ciclo, y muestra ' .
            'el máximo disponible antes de que se cargue el monto. Los adelantos se descuentan ' .
            'solos al generar la siguiente planilla.');

        // ── Gastos ──
        $this->seccion->addTitle('4.8 Gastos fijos', 2);
        $this->p('Los gastos fijos son los pagos recurrentes del negocio: alquiler, luz, agua, gas, ' .
            'internet, impuestos. El módulo funciona en dos niveles.');
        $this->p('Primero se dan de alta las plantillas, una sola vez por cada gasto, indicando ' .
            'su monto estimado, cada cuánto se paga y qué día del mes vence. Después, mes a mes, ' .
            'se generan los pagos con un clic: el sistema crea únicamente los que corresponden ' .
            'según la frecuencia de cada uno.');
        $this->captura('Listado de gastos fijos configurados',
            'Menú lateral → Gastos Fijos');
        $this->captura('Control de pagos del mes con el resumen de pendientes, vencidos y pagados',
            'Menú lateral → Control de Pagos');

        $this->p('Los gastos que superan su fecha de vencimiento sin pagarse pasan solos a estado ' .
            'vencido y se resaltan en rojo. Cuando llega la factura real y difiere del estimado ' .
            '—algo habitual con la luz y el agua— se puede ajustar el monto antes de pagarlo.');

        $this->seccion->addTitle('Resumen anual de gastos', 3);
        $this->p('Muestra los doce meses del año en una sola tabla, con cada gasto en una fila y ' .
            'su estado mes a mes. Arriba, un gráfico de líneas señala en qué meses hubo picos.');
        $this->captura('Resumen anual con la matriz de gastos por mes y el gráfico de tendencia',
            'Menú lateral → Resumen Anual');

        // ── Finanzas ──
        $this->seccion->addTitle('4.9 Resumen financiero', 2);
        $this->p('Es la pantalla que responde si el negocio gana o pierde. Consolida en un solo ' .
            'lugar la información que el resto de los módulos genera por separado: las ventas ' .
            'como ingreso, y las compras de insumos, las mermas, los sueldos y los gastos fijos ' .
            'como egresos.');
        $this->p('El resultado se presenta como un estado de resultados que va restando por etapas ' .
            'hasta llegar a la utilidad neta, con la variación de cada línea respecto del mes ' .
            'anterior y su peso porcentual sobre las ventas.');
        $this->captura('Estado de resultados del mes con las tarjetas de resumen',
            'Menú lateral → Resumen Financiero');
        $this->captura('Gráfico de ingresos contra egresos de los últimos doce meses',
            'Resumen Financiero (parte inferior de la pantalla)');
    }

    // ─────────────────────────────────────────────────────────────
    //  7. BASE DE DATOS
    // ─────────────────────────────────────────────────────────────
    private function baseDeDatos(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('5. Base de datos', 1);

        $this->p('La información se guarda localmente en «database.sqlite», dentro de la carpeta de ' .
            'datos de Panadería Escritorio del usuario de Windows. SQLite no necesita un servidor ' .
            'ni credenciales. Las tablas están agrupadas por módulo y se relacionan mediante claves ' .
            'foráneas, que garantizan que no queden registros huérfanos.');

        $this->seccion->addTitle('5.1 Mapa de relaciones', 2);
        $this->p('El siguiente esquema muestra cómo se conectan las tablas principales:');

        $this->codigo([
            'CATÁLOGO Y STOCK',
            '  categorias ──1:N── productos ──N:M── almacenes (mediante almacen_producto)',
            '  insumos ──N:M── recetas (mediante receta_insumo)',
            '  insumos ──1:N── compras_insumo     (entradas de materia prima)',
            '  insumos ──1:N── mermas_insumos     (pérdidas de materia prima)',
            '',
            'VENTAS',
            '  users ──1:N── ventas ──1:N── detalle_ventas ──N:1── productos',
            '  users ──1:N── cortes_caja',
            '  almacenes ──1:N── ventas',
            '',
            'RECURSOS HUMANOS',
            '  cargos ──1:N── empleados ──1:N── asistencias',
            '  empleados ──1:N── adelantos',
            '  planillas ──N:M── empleados (mediante planilla_empleado)',
            '',
            'GASTOS',
            '  gastos_fijos ──1:N── gastos_pagos',
            '',
            'SEGURIDAD',
            '  users ──N:M── roles ──N:M── permissions (mediante tablas de permisos)',
        ]);

        $this->nota('Cómo leer el esquema',
            'La marca «1:N» indica una relación de uno a muchos: una categoría tiene muchos ' .
            'productos. La marca «N:M» entre dos tablas indica una relación de muchos a muchos ' .
            'resuelta con una tabla intermedia: un producto puede estar en varios almacenes y un ' .
            'almacén tiene varios productos.');

        // ── Tablas por módulo ──
        $this->seccion->addTitle('5.2 Catálogo y stock', 2);

        $this->seccion->addTitle('categorias', 3);
        $this->tablaCampos([
            ['id',          'bigint',       'Identificador único'],
            ['nombre',      'varchar(100)', 'Nombre de la categoría'],
            ['descripcion', 'text',         'Detalle opcional'],
            ['activo',      'booleano',     'Si está disponible para usar'],
        ]);

        $this->seccion->addTitle('productos', 3);
        $this->tablaCampos([
            ['id',            'bigint',        'Identificador único'],
            ['categoria_id',  'bigint (FK)',   'Categoría a la que pertenece'],
            ['nombre',        'varchar(150)',  'Nombre del producto'],
            ['precio_venta',  'decimal(10,2)', 'Precio al público en Bs'],
            ['stock',         'entero',        'Unidades en bodega central'],
            ['stock_minimo',  'entero',        'Nivel que dispara la alerta'],
            ['imagen',        'varchar(255)',  'Ruta de la foto del producto'],
            ['activo',        'booleano',      'Si se puede vender'],
        ]);

        $this->seccion->addTitle('insumos', 3);
        $this->tablaCampos([
            ['id',             'bigint',        'Identificador único'],
            ['nombre',         'varchar(150)',  'Nombre del insumo'],
            ['unidad_medida',  'varchar(50)',   'Kilo, litro, unidad, etc.'],
            ['cantidad_stock', 'decimal(10,2)', 'Cantidad disponible'],
            ['stock_minimo',   'decimal(10,2)', 'Nivel que dispara la alerta'],
            ['costo_unitario', 'decimal(12,5)', 'Costo por unidad de medida'],
        ]);

        $this->seccion->addTitle('recetas y receta_insumo', 3);
        $this->tablaCampos([
            ['recetas.nombre',                   'varchar(150)',  'Nombre de la receta'],
            ['recetas.rendimiento',              'entero',        'Unidades que produce una tanda'],
            ['receta_insumo.receta_id',          'bigint (FK)',   'Receta a la que pertenece'],
            ['receta_insumo.insumo_id',          'bigint (FK)',   'Insumo requerido'],
            ['receta_insumo.cantidad_necesaria', 'decimal(10,2)', 'Cantidad por tanda'],
        ]);

        $this->seccion->addTitle('5.3 Ventas', 2);

        $this->seccion->addTitle('ventas', 3);
        $this->tablaCampos([
            ['id',             'bigint',           'Identificador único'],
            ['user_id',        'bigint (FK)',      'Usuario que registró la venta'],
            ['almacen_id',     'bigint (FK)',      'Punto de venta'],
            ['numero_venta',   'varchar(20)',      'Número correlativo único'],
            ['subtotal',       'decimal(10,2)',    'Suma antes del descuento'],
            ['descuento',      'decimal(10,2)',    'Descuento aplicado'],
            ['total',          'decimal(10,2)',    'Monto final cobrado'],
            ['tipo_pago',      'efectivo | qr',    'Forma de pago'],
            ['monto_recibido', 'decimal(10,2)',    'Con cuánto pagó el cliente'],
            ['cambio',         'decimal(10,2)',    'Vuelto entregado'],
            ['estado',         'completada | cancelada', 'Si la venta es válida'],
        ]);

        $this->seccion->addTitle('cortes_caja', 3);
        $this->tablaCampos([
            ['user_id',        'bigint (FK)',      'Cajero responsable del turno'],
            ['fecha_corte',    'date',             'Día del turno'],
            ['hora_apertura',  'time',             'Hora de apertura'],
            ['hora_cierre',    'time',             'Hora de cierre'],
            ['monto_inicial',  'decimal(10,2)',    'Efectivo al abrir la caja'],
            ['total_ventas',   'decimal(10,2)',    'Total vendido en el turno'],
            ['monto_final',    'decimal(10,2)',    'Efectivo contado al cerrar'],
            ['diferencia',     'decimal(10,2)',    'Sobrante o faltante detectado'],
            ['estado',         'abierto | cerrado','Situación del turno'],
        ]);

        $this->seccion->addTitle('5.4 Recursos Humanos', 2);

        $this->seccion->addTitle('empleados', 3);
        $this->tablaCampos([
            ['id',                'bigint',              'Identificador único'],
            ['nombre / apellido', 'varchar(100)',        'Nombre completo'],
            ['ci',                'varchar(20) único',   'Carnet de identidad'],
            ['telefono',          'varchar(20)',         'Contacto'],
            ['cargo_id',          'bigint (FK)',         'Puesto que ocupa'],
            ['salario_base',      'decimal(10,2)',       'Sueldo del ciclo en Bs'],
            ['tipo_pago',         'mensual | semanal',   'Modalidad de pago'],
            ['factor_hora_extra', 'decimal(4,2)',        'Multiplicador de hora extra (1.50)'],
            ['fecha_ingreso',     'date',                'Inicio de la relación laboral'],
            ['activo',            'booleano',            'Si sigue trabajando'],
        ]);

        $this->seccion->addTitle('asistencias', 3);
        $this->tablaCampos([
            ['empleado_id',      'bigint (FK)',   'Empleado registrado'],
            ['fecha',            'date',          'Día de la asistencia'],
            ['estado',           'enumerado',     'presente, ausente, tardanza, medio_dia, feriado, licencia'],
            ['hora_entrada',     'time',          'Marcaje de entrada'],
            ['hora_salida',      'time',          'Marcaje de salida'],
            ['minutos_tardanza', 'entero',        'Minutos de retraso'],
            ['horas_extra',      'decimal(5,2)',  'Horas trabajadas de más'],
        ]);
        $this->nota('Restricción importante',
            'La combinación de empleado y fecha es única: no pueden existir dos registros de ' .
            'asistencia para la misma persona el mismo día. Por eso, al volver a guardar una ' .
            'fecha ya cargada, el sistema actualiza el registro existente en lugar de duplicarlo.');

        $this->seccion->addTitle('planillas y planilla_empleado', 3);
        $this->tablaCampos([
            ['planillas.tipo',                        'mensual | semanal',           'Modalidad que agrupa'],
            ['planillas.periodo_inicio / fin',        'date',                        'Rango de días que cubre'],
            ['planillas.estado',                      'borrador | cerrada | pagada', 'Etapa del proceso'],
            ['planillas.total_general',               'decimal(12,2)',               'Suma a pagar'],
            ['planilla_empleado.dias_trabajados',     'entero',                      'Días presentes más tardanzas'],
            ['planilla_empleado.horas_extra',         'decimal(6,2)',                'Horas extra del período'],
            ['planilla_empleado.salario_bruto',       'decimal(10,2)',               'Ganado antes de descuentos'],
            ['planilla_empleado.descuento_tardanzas', 'decimal(10,2)',               'Descuento por llegadas tarde'],
            ['planilla_empleado.adelantos_descontados','decimal(10,2)',              'Adelantos aplicados'],
            ['planilla_empleado.total_neto',          'decimal(10,2)',               'Monto final a entregar'],
        ]);

        $this->seccion->addTitle('adelantos', 3);
        $this->tablaCampos([
            ['empleado_id', 'bigint (FK)',   'Quién recibió el adelanto'],
            ['monto',       'decimal(10,2)', 'Importe entregado'],
            ['fecha',       'date',          'Cuándo se entregó'],
            ['planilla_id', 'bigint (FK)',   'Planilla que lo descontó (vacío si sigue pendiente)'],
        ]);
        $this->nota('Cómo se sabe si un adelanto sigue pendiente',
            'Mientras el campo «planilla_id» esté vacío, el adelanto no fue descontado todavía. ' .
            'Cuando una planilla lo cubre, ese campo queda con el número de la planilla ' .
            'correspondiente y el adelanto deja de figurar como deuda.');

        $this->seccion->addTitle('5.5 Gastos', 2);

        $this->seccion->addTitle('gastos_fijos', 3);
        $this->tablaCampos([
            ['nombre',          'varchar(255)',  'Nombre del gasto'],
            ['categoria',       'enumerado',     'alquiler, servicios, mantenimiento, impuestos, otro'],
            ['monto_estimado',  'decimal(10,2)', 'Cuánto se espera pagar'],
            ['frecuencia',      'enumerado',     'mensual, bimestral, trimestral, semestral, anual'],
            ['dia_vencimiento', 'entero (1-28)', 'Día del mes en que vence'],
            ['mes_inicio',      'entero (1-12)', 'Mes de referencia para la frecuencia'],
            ['proveedor',       'varchar(255)',  'A quién se le paga'],
            ['activo',          'booleano',      'Si se incluye al generar meses'],
        ]);

        $this->seccion->addTitle('gastos_pagos', 3);
        $this->tablaCampos([
            ['gasto_fijo_id',     'bigint (FK)',   'Gasto del que proviene'],
            ['periodo',           'varchar(7)',    'Mes al que corresponde, formato AAAA-MM'],
            ['fecha_vencimiento', 'date',          'Cuándo hay que pagarlo'],
            ['monto_estimado',    'decimal(10,2)', 'Monto previsto'],
            ['monto_real',        'decimal(10,2)', 'Monto efectivamente pagado'],
            ['fecha_pago',        'date',          'Cuándo se pagó'],
            ['estado',            'enumerado',     'pendiente, pagado, vencido'],
            ['referencia',        'varchar(255)',  'Número de recibo o factura'],
        ]);
        $this->nota('La diferencia entre «periodo» y «fecha_pago»',
            'El campo «periodo» indica a qué mes pertenece el gasto y «fecha_pago» cuándo salió ' .
            'el dinero. Pueden no coincidir: la luz de enero puede pagarse en marzo. El resumen ' .
            'financiero usa «periodo», de modo que el costo pesa en el mes que corresponde y no ' .
            'distorsiona el resultado del mes en que se saldó.');

        $this->seccion->addTitle('5.6 Seguridad', 2);
        $this->p('Los permisos se manejan con el paquete Spatie Laravel Permission, que agrega ' .
            'cinco tablas. Los usuarios reciben roles, los roles agrupan permisos, y cada pantalla ' .
            'verifica el permiso que necesita antes de mostrarse.');
        $this->tablaCampos([
            ['users',                'tabla',  'Usuarios que inician sesión'],
            ['roles',                'tabla',  'Administrador y Cajero'],
            ['permissions',          'tabla',  'Acciones concretas del sistema'],
            ['model_has_roles',      'puente', 'Qué rol tiene cada usuario'],
            ['role_has_permissions', 'puente', 'Qué permisos incluye cada rol'],
        ]);
    }

    /** Tabla de campos con tres columnas de ancho fijo. */
    private function tablaCampos(array $filas): void
    {
        $this->tabla(['Campo', 'Tipo', 'Descripción'], $filas, [3000, 2400, 3600]);
    }

    // ─────────────────────────────────────────────────────────────
    //  8. REGLAS DE NEGOCIO
    // ─────────────────────────────────────────────────────────────
    private function reglasDeNegocio(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('6. Reglas de cálculo', 1);

        $this->p('Esta sección explica las fórmulas que el sistema aplica. Conocerlas permite ' .
            'verificar que los montos que muestra sean los esperados.');

        $this->seccion->addTitle('6.1 Base de cálculo de sueldos', 2);
        $this->p('La panadería trabaja de lunes a sábado. El domingo no se labora y por lo tanto ' .
            'no se contabiliza en ninguna planilla. De ahí salen las constantes que usa el sistema:');

        $this->tabla(
            ['Constante', 'Valor', 'Significado'],
            [
                ['Días por mes',    '26 días', 'Días laborables de un mes (lunes a sábado)'],
                ['Días por semana', '6 días',  'Días laborables de una semana'],
                ['Jornada',         '8 horas', 'Duración de un día de trabajo'],
            ],
            [2800, 2000, 5200]
        );

        $this->nota('Dónde se cambian estos valores',
            'Están centralizados en el archivo «config/nomina.php». Si en algún momento cambia ' .
            'la jornada o los días laborables, se modifica ahí una sola vez y todo el sistema ' .
            'queda actualizado: las planillas, las equivalencias salariales y el PDF.');

        $this->seccion->addTitle('6.2 Valor del día y de la hora', 2);
        $this->p('El divisor que convierte el sueldo en valor diario depende de la modalidad de ' .
            'pago del empleado, no de la duración del período que se esté liquidando:');

        $this->codigo([
            'Empleado mensual:   valor del día = salario base ÷ 26',
            'Empleado semanal:   valor del día = salario base ÷ 6',
            '',
            'En ambos casos:     valor de la hora = valor del día ÷ 8',
        ]);

        $this->p('Por ejemplo, un empleado semanal que gana Bs 300 tiene un valor de día de ' .
            'Bs 50 (300 ÷ 6) y un valor de hora de Bs 6,25 (50 ÷ 8).');

        $this->seccion->addTitle('6.3 Cómo se calcula una planilla', 2);
        $this->p('Para cada empleado, el sistema toma sus asistencias del período, descarta los ' .
            'domingos y aplica esta secuencia:');

        $this->codigo([
            '1. Días efectivos = presentes + tardanzas + (medios días × 0,5)',
            '',
            '2. Salario bruto       = valor del día × días efectivos',
            '3. Descuento tardanza  = (minutos tarde ÷ 60) × valor de la hora',
            '4. Pago de horas extra = horas extra × valor de la hora × 1,50',
            '',
            '5. Disponible = salario bruto + horas extra − descuento tardanza',
            '6. Total neto = disponible − adelantos que alcance a cubrir',
        ]);

        $this->nota('Por qué las tardanzas cuentan como día trabajado',
            'Quien llega tarde igual trabajó, así que su día se cuenta completo. Lo que se ' .
            'descuenta son los minutos concretos de retraso, valorizados a la tarifa horaria. ' .
            'El medio día, en cambio, cuenta como medio día efectivo.');

        $this->seccion->addTitle('6.4 Tratamiento de los adelantos', 2);
        $this->p('Los adelantos se descuentan del sueldo en la planilla siguiente. El sistema ' .
            'aplica dos controles para que la deuda nunca se pierda ni se descontrole.');

        $this->seccion->addTitle('Control al momento de entregarlo', 3);
        $this->p('No se puede adelantar más de lo que la persona gana en su ciclo. El límite ' .
            'disponible es el salario base menos lo que ya tiene adelantado sin descontar. La ' .
            'pantalla muestra ese máximo antes de cargar el monto.');

        $this->seccion->addTitle('Control al momento de liquidar', 3);
        $this->p('Si en el período el empleado trabajó poco y su sueldo no alcanza a cubrir todo ' .
            'lo que debe, el sistema descuenta solo hasta donde llega el dinero. Lo que queda ' .
            'sin cubrir permanece pendiente y se descuenta en la planilla siguiente.');

        $this->codigo([
            'Ejemplo:',
            '  El empleado trabajó 2 días y ganó           Bs 100',
            '  Tenía adelantos pendientes por              Bs 300',
            '',
            '  Se descuentan                               Bs 100  (lo que alcanza)',
            '  Queda pendiente para la próxima planilla    Bs 200',
            '  Recibe en mano                              Bs   0',
        ]);

        $this->nota('Por qué esto importa',
            'Sin este control, la deuda que excede el sueldo desaparecería del sistema y la ' .
            'panadería perdería ese dinero sin registro. En el detalle de la planilla, el ' .
            'empleado que arrastra saldo aparece con una etiqueta que indica cuánto sigue debiendo.',
            self::VERDE);

        $this->seccion->addTitle('6.5 Generación de gastos mensuales', 2);
        $this->p('Al generar un mes, el sistema recorre los gastos fijos activos y crea únicamente ' .
            'los que corresponden según su frecuencia. Un gasto trimestral que empieza en enero ' .
            'se genera en enero, abril, julio y octubre; en los demás meses no aparece.');
        $this->p('Si el día de vencimiento configurado no existe en ese mes, se usa el último día ' .
            'disponible. Por eso el sistema limita ese valor a 28, para evitar problemas en febrero.');

        $this->seccion->addTitle('6.6 Cálculo del resultado financiero', 2);
        $this->p('El resumen financiero arma el estado de resultados con esta estructura:');

        $this->codigo([
            '  Ventas del mes                     (ingresos)',
            '− Compras de insumos                 (costo directo)',
            '− Mermas valorizadas al costo        (costo directo)',
            '─────────────────────────────────────',
            '= UTILIDAD BRUTA',
            '',
            '− Sueldos según planillas            (gasto operativo)',
            '− Gastos fijos pagados               (gasto operativo)',
            '─────────────────────────────────────',
            '= UTILIDAD NETA',
        ]);

        $this->tabla(
            ['Concepto', 'De dónde sale', 'Criterio de asignación al mes'],
            [
                ['Ventas',        'Ventas completadas',      'Fecha de la venta'],
                ['Compras',       'Compras de insumos',      'Fecha de la compra'],
                ['Mermas',        'Mermas × costo unitario', 'Fecha de la merma'],
                ['Sueldos',       'Total de las planillas',  'Mes en que termina el período'],
                ['Gastos fijos',  'Pagos ya realizados',     'Mes al que corresponde el gasto'],
            ],
            [2200, 3400, 4400]
        );

        $this->nota('Gastos pendientes',
            'Los gastos que todavía no se pagaron no restan de la utilidad, porque el dinero no ' .
            'salió. Aparecen en un aviso al comienzo de la pantalla, con el monto y cuántos están ' .
            'vencidos, para que se los tenga presentes al leer el resultado.');
    }

    // ─────────────────────────────────────────────────────────────
    //  9. GUÍA DE USO
    // ─────────────────────────────────────────────────────────────
    private function guiaDeUso(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('7. Guía de uso paso a paso', 1);

        $this->p('Esta sección recorre las tareas más frecuentes en el orden en que se realizan ' .
            'durante la operación diaria y mensual del negocio.');

        // ── Diario ──
        $this->seccion->addTitle('7.1 Rutina diaria', 2);

        $this->seccion->addTitle('Al abrir la panadería', 3);
        $this->pasos([
            'Iniciar sesión con el usuario correspondiente.',
            'Revisar el panel principal: muestra si falta tomar asistencia y si hay gastos por vencer.',
            'Tomar la asistencia del personal desde «Asistencias → Registrar Asistencia».',
            'El cajero abre su caja declarando el monto inicial en efectivo.',
        ]);
        $this->captura('Panel principal mostrando el aviso de asistencia pendiente',
            'Aplicación Panadería Escritorio → Inicio');

        $this->seccion->addTitle('Durante el día', 3);
        $this->pasos([
            'Registrar cada venta desde «Ventas → Nueva Venta».',
            'Si se produce en el obrador, ejecutar la receta correspondiente para descontar los insumos.',
            'Transferir stock de bodega a los almacenes cuando haga falta reponer.',
        ]);

        $this->seccion->addTitle('Al cerrar', 3);
        $this->pasos([
            'Contar el efectivo de la caja.',
            'Cerrar el corte declarando el monto contado.',
            'Verificar que la diferencia sea cero o justificarla en las observaciones.',
        ]);
        $this->captura('Formulario de cierre de caja con el arqueo',
            'Cortes de Caja → Cerrar');

        // ── Semanal ──
        $this->seccion->addTitle('7.2 Rutina semanal', 2);
        $this->pasos([
            'Verificar que todas las asistencias de la semana estén cargadas.',
            'Generar la planilla del personal semanal desde «Planillas → Nueva Planilla», eligiendo el tipo «semanal» y el rango de lunes a sábado.',
            'Revisar el detalle: comprobar que los días trabajados y los montos sean correctos.',
            'Cerrar la planilla para congelar los montos.',
            'Descargar el PDF e imprimirlo para que el personal firme al recibir el pago.',
            'Marcar la planilla como pagada una vez entregado el dinero.',
        ]);
        $this->captura('Formulario de creación de una planilla nueva',
            'Planillas → Nueva Planilla');

        $this->nota('Antes de generar una planilla',
            'Cargá todas las asistencias del período primero. La planilla toma una foto de lo que ' .
            'hay registrado en ese momento; si después agregás asistencias faltantes, esa planilla ' .
            'no se actualiza sola.');

        // ── Mensual ──
        $this->seccion->addTitle('7.3 Rutina mensual', 2);

        $this->seccion->addTitle('Al comenzar el mes', 3);
        $this->pasos([
            'Entrar a «Control de Pagos» y elegir el mes.',
            'Pulsar «Generar mes» para crear los gastos que corresponden.',
            'Revisar la lista y ajustar los montos que ya se conozcan.',
        ]);

        $this->seccion->addTitle('A medida que llegan las facturas', 3);
        $this->pasos([
            'Si el monto real difiere del estimado, ajustarlo con el botón del lápiz.',
            'Al pagar, pulsar «Pagar», cargar el monto real, la fecha y el número de recibo.',
        ]);
        $this->captura('Ventana de registro de pago de un gasto',
            'Control de Pagos → Pagar');

        $this->seccion->addTitle('Al cerrar el mes', 3);
        $this->pasos([
            'Generar la planilla del personal mensual.',
            'Verificar que no queden gastos vencidos sin pagar.',
            'Entrar al «Resumen Financiero» y revisar el resultado del mes.',
            'Comparar contra el mes anterior usando las variaciones que muestra cada línea.',
        ]);
        $this->captura('Resumen financiero del mes cerrado',
            'Menú lateral → Resumen Financiero');

        // ── Tareas puntuales ──
        $this->seccion->addTitle('7.4 Tareas ocasionales', 2);

        $this->seccion->addTitle('Dar de alta un empleado', 3);
        $this->pasos([
            'Verificar que exista el cargo; si no, crearlo en «Cargos».',
            'Ir a «Empleados → Nuevo Empleado».',
            'Completar los datos personales y el carnet, que no puede repetirse.',
            'Elegir la modalidad de pago y cargar el salario base del ciclo.',
            'Confirmar las equivalencias que muestra la pantalla antes de guardar.',
        ]);
        $this->captura('Formulario de alta de empleado con las equivalencias salariales',
            'Empleados → Nuevo Empleado');

        $this->seccion->addTitle('Registrar un adelanto', 3);
        $this->pasos([
            'Entrar a la ficha del empleado desde el listado.',
            'Pulsar el botón de adelanto.',
            'Verificar el máximo disponible que indica la ventana.',
            'Cargar el monto, la fecha y el motivo.',
        ]);

        $this->seccion->addTitle('Dar de alta un gasto fijo', 3);
        $this->pasos([
            'Ir a «Gastos Fijos → Nuevo Gasto».',
            'Cargar el nombre, la categoría y el monto que se espera pagar.',
            'Elegir la frecuencia y el día del mes en que vence.',
            'Si la frecuencia no es mensual, indicar el mes de referencia.',
        ]);
        $this->captura('Formulario de alta de un gasto fijo',
            'Gastos Fijos → Nuevo Gasto');

        $this->seccion->addTitle('Corregir una asistencia ya cargada', 3);
        $this->pasos([
            'Ir a «Asistencias → Registrar Asistencia» y elegir la fecha.',
            'Los datos ya guardados aparecen cargados en el formulario.',
            'Modificar lo que corresponda y volver a guardar.',
        ]);
    }

    /** Lista numerada de pasos. */
    private function pasos(array $items): void
    {
        foreach ($items as $i => $texto) {
            $t = $this->seccion->addTable(['cellMargin' => 0, 'width' => 100 * 50,
                                           'unit' => TblWidth::PERCENT]);
            $t->addRow();
            $t->addCell(500, ['valign' => 'top'])
              ->addText((string) ($i + 1) . '.',
                  ['bold' => true, 'color' => self::AZUL2, 'size' => 11],
                  ['spaceAfter' => 60]);
            $t->addCell(9200, ['valign' => 'top'])
              ->addText($texto, ['size' => 11],
                  ['alignment' => Jc::BOTH, 'spaceAfter' => 60, 'lineHeight' => 1.1]);
        }
        $this->seccion->addTextBreak(1, ['size' => 8]);
    }

    // ─────────────────────────────────────────────────────────────
    //  10. MANTENIMIENTO
    // ─────────────────────────────────────────────────────────────
    private function mantenimiento(): void
    {
        $this->nuevaSeccion();
        $this->seccion->addTitle('8. Mantenimiento y solución de problemas', 1);

        $this->seccion->addTitle('8.1 Respaldo de la información', 2);
        $this->p('La base de datos contiene toda la operación del negocio. Conviene respaldarla ' .
            'con regularidad, sobre todo antes de actualizar el sistema.');
        $this->pasos([
            'Ingresá con un usuario administrador.',
            'Abrí «Sistema → Respaldos».',
            'Pulsá «Descargar respaldo ahora» y guardá el archivo ZIP en una memoria USB o en la nube.',
            'Para restaurar, seleccioná el ZIP en la misma pantalla, marcá la confirmación y pulsá «Restaurar respaldo».',
            'Después de restaurar, iniciá sesión nuevamente y comprobá los datos.',
        ]);
        $this->nota('Qué contiene el respaldo',
            'El ZIP incluye la base SQLite y las imágenes de productos. Guardá al menos una copia ' .
            'fuera de esta PC. Antes de restaurar, el sistema conserva una copia de seguridad de ' .
            'la base actual.');

        $this->seccion->addTitle('8.2 Actualizar el sistema', 2);
        $this->pasos([
            'Creá un respaldo desde «Sistema → Respaldos».',
            'Cerrá completamente Panadería Escritorio.',
            'Ejecutá el instalador de la versión nueva sobre la instalación existente.',
            'Abrí la aplicación; las actualizaciones de la base se aplican automáticamente.',
            'Verificá que tus usuarios, ventas, inventario e imágenes sigan disponibles.',
        ]);
        $this->nota('La carpeta AppData conserva la información',
            'No es necesario borrar AppData para actualizar. Eliminala únicamente cuando quieras ' .
            'reiniciar la aplicación desde cero y aceptes perder todos los datos locales.', self::ROJO);

        $this->seccion->addTitle('8.3 Problemas frecuentes', 2);

        $this->tabla(
            ['Síntoma', 'Causa probable', 'Solución'],
            [
                [
                    'Las fotos de los productos no se ven',
                    'La imagen fue movida, dañada o faltaba en un respaldo restaurado',
                    'Restaurar un respaldo completo que incluya las imágenes',
                ],
                [
                    'La aplicación no puede abrir la base de datos',
                    'El archivo SQLite está bloqueado, dañado o sin permisos',
                    'Cerrar y abrir la aplicación; si continúa, restaurar el último respaldo válido',
                ],
                [
                    'Una página queda cargando o la sesión vence',
                    'La sesión local se reinició o una operación tardó más de lo esperado',
                    'Esperar unos segundos; si continúa, cerrar y volver a abrir la aplicación',
                ],
                [
                    'Después de actualizar aparece la versión anterior',
                    'La aplicación estaba abierta durante la instalación',
                    'Cerrar completamente la aplicación y ejecutar otra vez el instalador nuevo',
                ],
                [
                    'Una planilla muestra montos incorrectos',
                    'Faltaban asistencias al generarla',
                    'Cargar las asistencias y generar la planilla de nuevo',
                ],
                [
                    'Un gasto figura vencido pero ya se pagó',
                    'El pago no se registró en el sistema',
                    'Ir a Control de Pagos y pulsar «Pagar»',
                ],
                [
                    'No se puede registrar un adelanto',
                    'Supera el sueldo del ciclo del empleado',
                    'Verificar el máximo disponible en la ventana',
                ],
            ],
            [2900, 3100, 4000]
        );

        $this->seccion->addTitle('8.4 Estructura de carpetas', 2);
        $this->p('Para quien necesite modificar el sistema, esta es la organización del código:');
        $this->codigo([
            'app/Http/Controllers/    Lógica de cada módulo',
            'app/Models/              Representación de las tablas',
            'app/Http/Middleware/     Control de acceso por rol',
            'config/nomina.php        Constantes de cálculo de sueldos',
            'database/migrations/     Definición de las tablas',
            'resources/views/         Pantallas del sistema',
            'resources/views/components/  Piezas reutilizables de interfaz',
            'routes/web.php           Direcciones del sistema',
            'storage/app/public/      Imágenes subidas',
        ]);

        $this->seccion->addTitle('8.5 Regenerar este manual', 2);
        $this->p('Este documento se genera automáticamente desde el propio sistema. Si algo cambia, ' .
            'se puede volver a generar con un comando:');
        $this->codigo(['php artisan docs:generar']);
        $this->p('El archivo queda en la carpeta «documentacion» del proyecto. Las capturas de ' .
            'pantalla que hayas insertado a mano se pierden al regenerarlo, así que conviene ' .
            'guardar una copia aparte de la versión final.');
    }
}

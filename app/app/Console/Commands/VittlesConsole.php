<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Vittles\CatalogService;
use App\Services\Vittles\ConfirmedOrderStore;
use App\Services\Vittles\OrderService;
use App\Services\Vittles\TraceStore;
use App\Services\Vittles\VittlesClient;
use App\Services\Vittles\VittlesException;
use App\Support\DiagnosticLog;
use Illuminate\Console\Command;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use InvalidArgumentException;

class VittlesConsole extends Command
{
    protected $signature = 'vittles:console';

    protected $description = 'Abre el menú interactivo de la demo Vittles POS';

    private ?User $user = null;

    private ?array $catalogCache = null;

    private bool $fullScreen = false;

    public function handle(
        CatalogService $catalog,
        OrderService $orders,
        ConfirmedOrderStore $history,
        VittlesClient $client,
        TraceStore $traces,
        DiagnosticLog $diagnostics,
    ): int {
        if (! $this->input->isInteractive()) {
            $this->error('Este menú necesita una terminal interactiva. Para el ejercicio usá php artisan vittles:order loc_1001 "Buffalo Wings (12)".');

            return self::FAILURE;
        }

        $this->fullScreen = defined('STDOUT') && function_exists('stream_isatty') && stream_isatty(STDOUT);
        if ($this->fullScreen) {
            fwrite(STDOUT, "\033[?1049h");
        }

        try {
            return $this->runMenu($catalog, $orders, $history, $client, $traces, $diagnostics);
        } finally {
            if ($this->fullScreen) {
                fwrite(STDOUT, "\033[?1049l");
            }
        }
    }

    private function runMenu(
        CatalogService $catalog,
        OrderService $orders,
        ConfirmedOrderStore $history,
        VittlesClient $client,
        TraceStore $traces,
        DiagnosticLog $diagnostics,
    ): int {
        while (true) {
            $this->screen('Inicio');
            if ($this->user === null) {
                $option = $this->choice('Acceso', ['Ingresar', 'Registrarse', 'README', 'Ver comando del ejercicio', 'Salir'], 0);
                if ($option === 'Salir') {
                    return self::SUCCESS;
                }
                $this->screen($option);
                try {
                    if ($option === 'Ingresar') {
                        $this->login($diagnostics);
                    } elseif ($option === 'Registrarse') {
                        $this->register($diagnostics);
                    } elseif ($option === 'README') {
                        $this->readme();
                    } else {
                        $this->commandPreview(false);
                    }
                } catch (QueryException) {
                    $this->error('No se pudo acceder a las cuentas locales. Ejecutá php artisan migrate.');
                }
                $this->pause();

                continue;
            }

            $options = ['Nueva orden', 'Sedes y menús', 'Pedidos confirmados', 'Ver pedido en Vittles', 'README', 'Ejecutar comando del ejercicio'];
            if ($this->adminAllowed()) {
                $options[] = 'ADMIN · Diagnóstico';
            }
            array_push($options, 'Cerrar sesión', 'Salir');
            $option = $this->choice('¿Qué querés hacer?', $options, 0);

            if ($option === 'Salir') {
                return self::SUCCESS;
            }
            if ($option === 'Cerrar sesión') {
                $diagnostics->event('info', 'console.logout', __METHOD__, ['user_id' => $this->user->id]);
                $this->user = null;
                $this->catalogCache = null;

                continue;
            }

            $this->screen($option);
            try {
                $diagnostics->event('info', 'console.action.started', __METHOD__, ['action' => $option, 'user_id' => $this->user->id]);
                match ($option) {
                    'Nueva orden' => $this->createOrder($catalog, $orders),
                    'Sedes y menús' => $this->catalog($catalog),
                    'Pedidos confirmados' => $this->history($history),
                    'Ver pedido en Vittles' => $this->showOrder(),
                    'README' => $this->readme(),
                    'Ejecutar comando del ejercicio' => $this->commandPreview(true),
                    'ADMIN · Diagnóstico' => $this->admin($catalog, $orders, $client, $traces),
                    default => null,
                };
                $diagnostics->event('info', 'console.action.finished', __METHOD__, ['action' => $option, 'user_id' => $this->user->id]);
            } catch (VittlesException|InvalidArgumentException|QueryException $e) {
                $this->error($e->getMessage());
                $diagnostics->event('warning', 'console.action.failed', __METHOD__, ['error_type' => $e::class]);
            }
            if ($option !== 'ADMIN · Diagnóstico') {
                $this->pause();
            }
        }
    }

    private function screen(string $title): void
    {
        if ($this->fullScreen) {
            fwrite(STDOUT, "\033[2J\033[H");
        }
        $this->banner();
        if ($this->user === null) {
            $this->line('<fg=yellow>ACCESO</>  Ingresar  ·  Registrarse  ·  README  ·  Comando  ·  Salir');
        } else {
            $this->line('<fg=yellow>MENÚ</>  Nueva orden  ·  Sedes y menús  ·  Pedidos  ·  Ver pedido');
            $this->line('      README  ·  Comando'.($this->adminAllowed() ? '  ·  ADMIN' : '').'  ·  Cerrar sesión  ·  Salir');
        }
        $this->line('<fg=gray>'.str_repeat('─', 49).'</>');
        $this->line('<fg=gray>Inicio  /  '.e($title).'</>');
        $this->newLine();
    }

    private function pause(): void
    {
        if ($this->fullScreen) {
            $this->ask('Presioná Enter para volver al menú', '');
        }
    }

    private function banner(): void
    {
        $this->line('<fg=yellow>╔══════════════════════════════════════════════╗</>');
        $this->line('<fg=yellow>║</>   <fg=white;options=bold>heytruffle*</>  <fg=yellow>VITTLES POS · CONSOLA</>       <fg=yellow>║</>');
        $this->line('<fg=yellow>╚══════════════════════════════════════════════╝</>');
        $this->line($this->user ? 'Sesión: '.$this->user->name.($this->user->is_admin ? ' · ADMIN' : '') : 'Acceso local · elegí una opción');
        $this->newLine();
    }

    private function login(DiagnosticLog $diagnostics): void
    {
        $email = mb_strtolower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Contraseña');
        $key = 'vittles-console-login:'.hash('sha256', $email);
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->error('Demasiados intentos. Esperá un minuto para volver a ingresar.');

            return;
        }
        $user = User::query()->where('email', $email)->first();
        if ($user === null || ! Hash::check($password, $user->password)) {
            RateLimiter::hit($key, 60);
            $this->error('Las credenciales no coinciden.');
            $diagnostics->event('warning', 'console.login.failed', __METHOD__, ['reason_code' => 'invalid_credentials']);

            return;
        }

        RateLimiter::clear($key);
        $this->user = $user;
        $this->info('Ingresaste como '.$user->name.'.');
        $diagnostics->event('info', 'console.login.succeeded', __METHOD__, ['user_id' => $user->id]);
    }

    private function register(DiagnosticLog $diagnostics): void
    {
        if (! app()->environment('local')) {
            $this->error('El registro solo está disponible en la demo local.');

            return;
        }

        $name = trim((string) $this->ask('Nombre'));
        $email = mb_strtolower(trim((string) $this->ask('Email')));
        $password = (string) $this->secret('Contraseña');
        $confirmation = (string) $this->secret('Repetí la contraseña');
        $validator = Validator::make([
            'name' => $name, 'email' => $email,
            'password' => $password, 'password_confirmation' => $confirmation,
        ], [
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
        ]);
        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return;
        }

        $user = DB::transaction(fn (): User => User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => ! User::query()->exists(),
        ]));
        $this->user = $user;
        $this->info('Cuenta creada. Ingresaste como '.$user->name.'.');
        $diagnostics->event('info', 'console.register.succeeded', __METHOD__, ['user_id' => $user->id]);
    }

    private function loadCatalog(CatalogService $catalog, bool $refresh = false): array
    {
        if ($refresh || $this->catalogCache === null) {
            $this->catalogCache = $catalog->load();
        }

        return $this->catalogCache;
    }

    private function catalog(CatalogService $catalog): void
    {
        $data = $this->loadCatalog($catalog);
        $this->section('Sedes y menús');
        $this->table(['ID', 'Location', 'Activa', 'Menú'], array_map(fn (array $location) => [
            $location['id'], $location['name'], ($location['active'] ?? false) ? 'Sí' : 'No',
            $data['menus'][$location['id']]['status'] ?? 'sin lectura',
        ], $data['locations']));
        $options = array_map(fn (array $location) => $location['id'].' · '.$location['name'], $data['locations']);
        array_push($options, 'Actualizar desde Vittles', 'Volver');
        $selected = $this->choice('Ver menú', $options, count($options) - 1);
        if ($selected === 'Actualizar desde Vittles') {
            $this->loadCatalog($catalog, true);
            $this->info('Catálogo actualizado.');

            return;
        }
        if ($selected === 'Volver') {
            return;
        }
        $id = strtok($selected, ' ');
        $menu = $data['menus'][$id] ?? null;
        $this->screen('Sedes y menús / '.$id);
        if (($menu['status'] ?? null) !== 'loaded') {
            $this->warn($menu['message'] ?? 'Este menú no está disponible.');

            return;
        }
        $this->table(['Producto', 'ID', 'Precio', 'Disponible'], array_map(fn (array $item) => [
            $item['name'], $item['id'], '$'.$item['price'], $item['available'] ? 'Sí' : 'No',
        ], $menu['items']));
    }

    private function createOrder(CatalogService $catalog, OrderService $orders): void
    {
        $data = $this->loadCatalog($catalog);
        $locations = array_values(array_filter($data['locations'], fn (array $location) => ($data['menus'][$location['id']]['status'] ?? null) === 'loaded'));
        if ($locations === []) {
            $this->warn('No hay sedes con menú legible.');

            return;
        }
        $labels = array_map(fn (array $location) => $location['id'].' · '.$location['name'], $locations);
        $labels[] = 'Cancelar';
        $selected = $this->choice('Location de destino', $labels, count($labels) - 1);
        if ($selected === 'Cancelar') {
            return;
        }
        $location = $locations[array_search($selected, $labels, true)];
        $items = array_values(array_filter($data['menus'][$location['id']]['items'], fn (array $item) => $item['available']));
        $lines = [];
        while (true) {
            $this->screen('Nueva orden / '.$location['name']);
            if ($lines !== []) {
                $this->line('Seleccionados: '.implode(', ', array_map(fn (array $line) => $line['name'].' × '.$line['quantity'], $lines)));
            }
            $this->section('Productos · '.$location['name']);
            $this->table(['ID', 'Producto', 'Precio'], array_map(fn (array $item) => [$item['id'], $item['name'], '$'.$item['price']], $items));
            $options = array_map(fn (array $item) => $item['id'].' · '.$item['name'], $items);
            array_push($options, 'Finalizar selección', 'Cancelar');
            $selection = $this->choice('Agregar o cambiar cantidad', $options, count($options) - 2);
            if ($selection === 'Cancelar') {
                return;
            }
            if ($selection === 'Finalizar selección') {
                break;
            }
            $item = $items[array_search($selection, $options, true)];
            $entered = trim((string) $this->ask('Cantidad de '.$item['name'].' (1–20; 0 para quitar)', '2'));
            if (! ctype_digit($entered)) {
                $this->warn('Escribí un número entero.');

                continue;
            }
            $quantity = (int) $entered;
            if ($quantity < 0 || $quantity > 20) {
                $this->warn('Usá un número entre 0 y 20.');

                continue;
            }
            if ($quantity === 0) {
                unset($lines[$item['id']]);
            } else {
                $lines[$item['id']] = ['item_id' => $item['id'], 'quantity' => $quantity, 'name' => $item['name'], 'price' => $item['price']];
            }
            $this->line(count($lines).' productos seleccionados.');
        }
        if ($lines === []) {
            $this->warn('No se seleccionó ningún producto.');

            return;
        }
        $this->screen('Nueva orden / Confirmación');
        $this->table(['Producto', 'Cantidad', 'Subtotal estimado'], array_map(fn (array $line) => [
            $line['name'], $line['quantity'], '$'.number_format((float) $line['price'] * $line['quantity'], 2, '.', ''),
        ], array_values($lines)));
        $key = trim((string) $this->ask('Identificador de compra', 'demo'));
        if ($key === '') {
            $this->warn('El identificador no puede estar vacío.');

            return;
        }
        $this->line('El total definitivo lo devuelve Vittles. Repetir esta combinación recupera la misma orden.');
        if (! $this->confirm('¿Crear o recuperar esta orden?', false)) {
            return;
        }

        $requestKey = 'web-user-'.$this->user->id.'-'.$key;
        $result = $orders->placeMany($location['id'], array_map(fn (array $line) => [
            'item_id' => $line['item_id'], 'quantity' => $line['quantity'],
        ], array_values($lines)), $requestKey);
        $this->screen('Nueva orden / Resultado');
        $this->receipt($result);
    }

    private function receipt(array $result): void
    {
        $this->section('Comprobante · '.$result['status']);
        $this->line('Location: '.$result['location']['name'].' ('.$result['location']['id'].')');
        foreach ($result['items'] as $line) {
            $this->line('  '.$line['item']['name'].' × '.$line['quantity']);
        }
        if (isset($result['order'])) {
            $this->info('ID: '.$result['order']['id'].' · Total Vittles: $'.$result['order']['total']);
        } else {
            $this->warn($result['message'] ?? 'Vittles no confirmó esta orden.');
        }
        $this->line('Referencia: '.$result['client_ref']);
    }

    private function history(ConfirmedOrderStore $history): void
    {
        $locations = $history->locations();
        $options = ['Todas las sedes'];
        foreach ($locations as $location) {
            $options[] = $location->location_id.' · '.$location->location_name;
        }
        $options[] = 'Volver';
        $selection = $this->choice('Filtrar pedidos', $options, 0);
        if ($selection === 'Volver') {
            return;
        }
        $locationId = $selection === 'Todas las sedes' ? null : strtok($selection, ' ');
        $orders = $history->recent($locationId);
        if ($orders === []) {
            $this->warn('No hay pedidos para este filtro.');

            return;
        }
        $page = 0;
        $pageSize = 6;
        do {
            $pageCount = (int) ceil(count($orders) / $pageSize);
            $visible = array_slice($orders, $page * $pageSize, $pageSize);
            $this->screen('Pedidos confirmados / Página '.($page + 1).' de '.$pageCount);
            $this->line('Historial local; hasta 100 confirmaciones de esta integración.');
            $this->table(['#', 'ID', 'Location', 'Productos', 'Unidades', 'Total'], array_map(fn (int $index, object $order) => [
                $index + 1, $order->order_id, $order->location_id,
                count(json_decode($order->items_json ?? '', true) ?: []) ?: 1,
                $order->quantity, '$'.number_format((float) $order->total, 2, '.', ''),
            ], array_keys($visible), $visible));
            $this->line('Filas 1–'.count($visible).' · S siguiente · A anterior · Enter volver');
            $selection = mb_strtolower(trim((string) $this->ask('Ver detalle local', '')));
            if ($selection === 'a' && $page > 0) {
                $page--;
            } elseif ($selection === 's' && $page < $pageCount - 1) {
                $page++;
            } elseif ($selection === '') {
                return;
            } elseif (ctype_digit($selection) && (int) $selection >= 1 && (int) $selection <= count($visible)) {
                $order = $visible[(int) $selection - 1];
                break;
            } else {
                $this->warn('Elegí una fila o una página disponible.');
                $this->pause();
            }
        } while (true);
        $this->screen('Pedidos confirmados / '.$order->order_id);
        $this->section('Pedido '.$order->order_id);
        $this->line('Location: '.$order->location_name.' · '.$order->location_id);
        foreach (json_decode($order->items_json ?? '', true) ?: [['name' => $order->item_name, 'quantity' => $order->quantity]] as $line) {
            $this->line('  '.$line['name'].' × '.$line['quantity']);
        }
        $this->line('Total confirmado: $'.number_format((float) $order->total, 2, '.', ''));
        $this->line('Referencia: '.$order->client_ref);
    }

    private function showOrder(): void
    {
        $id = trim((string) $this->ask('ID de la orden en Vittles'));
        if ($id !== '') {
            $this->call('vittles:show', ['order_id' => $id]);
        }
    }

    private function commandPreview(bool $canRun): void
    {
        $this->section('Comando exacto del ejercicio');
        $this->line('<fg=yellow>php artisan vittles:order loc_1001 "Buffalo Wings (12)"</>');
        $this->line('Consulta todas las sedes y menús; crea o recupera una orden de 2 unidades en loc_1001.');
        if ($canRun && $this->confirm('¿Ejecutarlo desde este menú?', false)) {
            $this->call('vittles:order', ['location' => 'loc_1001', 'item' => 'Buffalo Wings (12)']);
        }
    }

    private function readme(): void
    {
        $this->section('README · alcance y decisiones');
        $this->line('Obligatorio: autenticación POS, todas las locations y menús, una orden de 2 unidades y resumen idempotente.');
        $this->line('Web y consola interactiva: demo adicional con varias líneas, cuentas locales e historial.');
        $this->line('Comando: php artisan vittles:order loc_1001 "Buffalo Wings (12)"');
        $this->line('Consultas: vittles:show <id> lee Vittles; vittles:orders [location] lee el historial local.');
        $this->line('Fuera a propósito: pagos, stock propio, despliegue y garantía exactly once distribuida.');
        $this->line('IA: se usó Codex para analizar, diseñar, implementar y revisar.');
        $this->line('Documentación completa: README.md y docs/COMMANDS.md en el repositorio.');
    }

    private function adminAllowed(): bool
    {
        $host = parse_url((string) config('vittles.base_url'), PHP_URL_HOST);

        return $this->user?->is_admin === true && app()->environment('local') && in_array($host, ['127.0.0.1', 'localhost'], true);
    }

    private function admin(CatalogService $catalog, OrderService $orders, VittlesClient $client, TraceStore $traces): void
    {
        if (! $this->adminAllowed()) {
            $this->error('ADMIN solo está disponible para la primera cuenta local y el mock local.');

            return;
        }
        while (true) {
            $this->screen('ADMIN / Diagnóstico');
            $this->section('ADMIN · diagnóstico del mock local');
            $option = $this->choice('Laboratorio de API', [
                'Ver trazas', 'Probar autenticación', 'Consultar página de locations', 'Recorrer catálogo completo',
                'Consultar menú', 'Buscar por client_ref', 'Leer orden por ID', 'Probar rechazo',
                'Crear o recuperar orden de prueba', 'Volver',
            ], 0);
            if ($option === 'Volver') {
                return;
            }
            $this->screen('ADMIN / '.$option);
            try {
                $result = match ($option) {
                    'Ver trazas' => $this->traces($traces),
                    'Probar autenticación' => $client->inspectAuthentication(),
                    'Consultar página de locations' => $client->get('/v1/locations', $this->optionalCursor()),
                    'Recorrer catálogo completo' => $this->loadCatalog($catalog, true),
                    'Consultar menú' => $client->get('/v1/locations/'.rawurlencode(trim((string) $this->ask('Location ID', 'loc_1004'))).'/menu'),
                    'Buscar por client_ref' => $client->get('/v1/orders', ['client_ref' => trim((string) $this->ask('Referencia'))]),
                    'Leer orden por ID' => $client->get('/v1/orders/'.rawurlencode(trim((string) $this->ask('Order ID')))),
                    'Probar rechazo' => $client->probeRejectedOrder(),
                    'Crear o recuperar orden de prueba' => $orders->place(
                        trim((string) $this->ask('Location ID', 'loc_1001')),
                        trim((string) $this->ask('Producto exacto', 'Buffalo Wings (12)')),
                        trim((string) $this->ask('Clave de solicitud', 'admin-demo')),
                    ),
                    default => null,
                };
                if ($result !== null) {
                    $this->screen('ADMIN / Resultado · '.$option);
                    $this->line(json_encode($this->safeProbeResult($result), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE));
                }
            } catch (VittlesException|InvalidArgumentException $e) {
                $this->error($e->getMessage());
            }
            $this->pause();
        }
    }

    private function optionalCursor(): array
    {
        $cursor = trim((string) $this->ask('Cursor (vacío = primera página)', ''));

        return $cursor === '' ? [] : ['cursor' => $cursor];
    }

    private function traces(TraceStore $traces): ?array
    {
        $entries = $traces->recent();
        if ($entries === []) {
            $this->warn('Todavía no hay trazas.');

            return null;
        }
        $filter = $this->choice('Filtrar trazas', ['Todas', 'Errores', 'POST'], 0);
        $id = trim((string) $this->ask('ID de diagnóstico (vacío = todos)', ''));
        $entries = array_values(array_filter($entries, fn (array $entry) => ($id === '' || ($entry['correlation_id'] ?? null) === $id)
            && ($filter !== 'POST' || $entry['method'] === 'POST')
            && ($filter !== 'Errores' || ($entry['transport_error'] ?? null) !== null || ($entry['status'] ?? 0) >= 400 || ($entry['response_body']['status'] ?? null) === 'REJECTED')
        ));
        if ($entries === []) {
            $this->warn('No hay trazas para ese filtro.');

            return null;
        }
        $page = 0;
        $pageSize = 6;
        while (true) {
            $pageCount = (int) ceil(count($entries) / $pageSize);
            $visible = array_slice($entries, $page * $pageSize, $pageSize);
            $this->screen('ADMIN / Trazas · página '.($page + 1).' de '.$pageCount);
            $this->table(['#', 'HTTP', 'Ruta', 'Estado', 'ms'], array_map(fn (int $n, array $entry) => [
                $n + 1, $entry['method'], $entry['path'], $entry['status'] ?? 'sin respuesta', $entry['duration_ms'],
            ], array_keys($visible), $visible));
            $this->line('Filas 1–'.count($visible).' · S siguiente · A anterior · Enter volver');
            $selected = mb_strtolower(trim((string) $this->ask('Ver request/response', '')));
            if ($selected === 'a' && $page > 0) {
                $page--;
            } elseif ($selected === 's' && $page < $pageCount - 1) {
                $page++;
            } elseif ($selected === '') {
                return null;
            } elseif (ctype_digit($selected) && (int) $selected >= 1 && (int) $selected <= count($visible)) {
                return $visible[(int) $selected - 1];
            } else {
                $this->warn('Elegí una fila o una página disponible.');
                $this->pause();
            }
        }
    }

    private function safeProbeResult(array $result): array
    {
        if (isset($result['catalog'], $result['items'])) {
            return [
                'status' => $result['status'], 'client_ref' => $result['client_ref'],
                'order' => $result['order'] ?? null, 'message' => $result['message'] ?? null,
                'locations_read' => count($result['catalog']['locations']),
                'menus_read' => count($result['catalog']['menus']),
            ];
        }

        return $result;
    }

    private function section(string $title): void
    {
        $this->newLine();
        $this->line('<fg=yellow>── '.$title.' ──</>');
    }
}

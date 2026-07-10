<?php
/**
 * Shared API helpers: JSON I/O, validation, pagination.
 */

function json_out(mixed $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function json_error(string $message, int $code = 400, array $extra = []): never
{
    json_out(array_merge(['error' => $message], $extra), $code);
}

/** Parse JSON request body. Returns [] on empty body. */
function read_json(): array
{
    $raw = file_get_contents('php://input');
    if ($raw === '' || $raw === false) {
        return [];
    }
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        json_error('Invalid JSON body.', 400);
    }
    return $data;
}

/** Ensure required keys exist and are non-empty strings/numbers. */
function require_fields(array $data, array $fields): void
{
    $missing = [];
    foreach ($fields as $f) {
        if (!isset($data[$f]) || (is_string($data[$f]) && trim($data[$f]) === '')) {
            $missing[] = $f;
        }
    }
    if ($missing) {
        json_error('Missing required fields: ' . implode(', ', $missing), 422);
    }
}

/** Standard list-endpoint params: ?page, ?per_page, ?q */
function list_params(int $defaultPerPage = 10, int $maxPerPage = 100): array
{
    $page    = max(1, (int)($_GET['page'] ?? 1));
    $perPage = min($maxPerPage, max(1, (int)($_GET['per_page'] ?? $defaultPerPage)));
    $q       = trim((string)($_GET['q'] ?? ''));
    return [$page, $perPage, $q, ($page - 1) * $perPage];
}

function paginated(array $rows, int $total, int $page, int $perPage): array
{
    return [
        'data' => $rows,
        'meta' => [
            'page'       => $page,
            'per_page'   => $perPage,
            'total'      => $total,
            'total_pages'=> (int)ceil($total / $perPage),
        ],
    ];
}

/** Fetch a row by id or 404. */
function find_or_404(string $table, int $id, string $select = '*'): array
{
    $stmt = db()->prepare("SELECT {$select} FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) {
        json_error('Not found.', 404);
    }
    return $row;
}

/**
 * Generic CRUD for simple resources (categories, suppliers, customers).
 * $config: table, fields (writable), required, search (columns for ?q).
 */
function simple_crud(array $config, string $method, ?int $id): void
{
    $table = $config['table'];

    switch ($method) {
        case 'GET':
            if ($id !== null) {
                json_out(find_or_404($table, $id));
            }
            [$page, $perPage, $q, $offset] = list_params();
            $where  = '';
            $params = [];
            if ($q !== '' && !empty($config['search'])) {
                $like  = array_map(fn($c) => "{$c} LIKE ?", $config['search']);
                $where = 'WHERE ' . implode(' OR ', $like);
                $params = array_fill(0, count($like), "%{$q}%");
            }
            $total = (int)db()->prepare("SELECT COUNT(*) FROM {$table} {$where}")
                ->execute($params) ?: 0;
            $stmt = db()->prepare("SELECT COUNT(*) c FROM {$table} {$where}");
            $stmt->execute($params);
            $total = (int)$stmt->fetch()['c'];

            $stmt = db()->prepare(
                "SELECT * FROM {$table} {$where} ORDER BY id DESC LIMIT {$perPage} OFFSET {$offset}"
            );
            $stmt->execute($params);
            json_out(paginated($stmt->fetchAll(), $total, $page, $perPage));

        case 'POST':
            $data = read_json();
            require_fields($data, $config['required']);
            $cols = [];
            $vals = [];
            foreach ($config['fields'] as $f) {
                if (array_key_exists($f, $data)) {
                    $cols[] = $f;
                    $vals[] = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
                }
            }
            $ph = implode(',', array_fill(0, count($cols), '?'));
            $stmt = db()->prepare(
                "INSERT INTO {$table} (" . implode(',', $cols) . ") VALUES ({$ph})"
            );
            $stmt->execute($vals);
            json_out(find_or_404($table, (int)db()->lastInsertId()), 201);

        case 'PUT':
            if ($id === null) json_error('ID required.', 400);
            find_or_404($table, $id);
            $data = read_json();
            require_fields($data, $config['required']);
            $sets = [];
            $vals = [];
            foreach ($config['fields'] as $f) {
                if (array_key_exists($f, $data)) {
                    $sets[] = "{$f} = ?";
                    $vals[] = is_string($data[$f]) ? trim($data[$f]) : $data[$f];
                }
            }
            if (!$sets) json_error('Nothing to update.', 422);
            $vals[] = $id;
            db()->prepare("UPDATE {$table} SET " . implode(',', $sets) . " WHERE id = ?")
                ->execute($vals);
            json_out(find_or_404($table, $id));

        case 'DELETE':
            if ($id === null) json_error('ID required.', 400);
            find_or_404($table, $id);
            try {
                db()->prepare("DELETE FROM {$table} WHERE id = ?")->execute([$id]);
            } catch (PDOException $e) {
                if ($e->getCode() === '23000') {
                    json_error('Cannot delete: record is referenced by other data.', 409);
                }
                throw $e;
            }
            json_out(['deleted' => true]);

        default:
            json_error('Method not allowed.', 405);
    }
}

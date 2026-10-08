<?php
declare(strict_types=1);

function e(?string $value): string { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    if ($path === '') return BASE_URL . '/';
    // Existing project routes use forms such as "search&type=anime".
    // Apache serves index.php, so turn the first & into the query separator.
    if (strpos($path, '?') === false && strpos($path, '&') !== false) {
        [$route, $query] = explode('&', $path, 2);
        return BASE_URL . '/index.php?page=' . rawurlencode($route) . '&' . $query;
    }
    if (strpos($path, '?') !== false) {
        [$route, $query] = explode('?', $path, 2);
        if ($route === 'index.php') return BASE_URL . '/index.php?' . $query;
        return BASE_URL . '/index.php?page=' . rawurlencode($route) . '&' . $query;
    }
    return BASE_URL . '/index.php?page=' . rawurlencode($path);
}
function asset_url(string $path): string {
    return rtrim(BASE_URL, '/') . '/' . ltrim($path, '/');
}
function redirect(string $path): never { header('Location: ' . url($path)); exit; }
function flash(string $key, ?string $message = null): ?string { if ($message !== null) { $_SESSION['_flash'][$key] = $message; return null; } $v = $_SESSION['_flash'][$key] ?? null; unset($_SESSION['_flash'][$key]); return $v; }
function csrf_token(): string { if (empty($_SESSION['_csrf'])) $_SESSION['_csrf']=bin2hex(random_bytes(32)); return $_SESSION['_csrf']; }
function verify_csrf(): void { if (!hash_equals($_SESSION['_csrf'] ?? '', (string)($_POST['_csrf'] ?? ''))) { http_response_code(419); exit('CSRF token tidak valid.'); } }
function current_user(): ?array { static $u=false; if ($u!==false) return $u; if (empty($_SESSION['user_id'])) return $u=null; $s=db()->prepare('SELECT id,username,email,role,avatar_url,bio,created_at FROM users WHERE id=?'); $s->execute([(int)$_SESSION['user_id']]); return $u=$s->fetch()?:null; }
function require_login(): void { if (!current_user()) { flash('error','Silakan login terlebih dahulu.'); redirect('login'); } }
function require_admin(): void { require_login(); if ((current_user()['role']??'')!=='admin') { http_response_code(403); exit('403 — Admin only.'); } }
function get(string $key,mixed $default=null): mixed { return $_GET[$key]??$default; }
function post(string $key,mixed $default=null): mixed { return $_POST[$key]??$default; }
function image_url(?string $url): string { return $url ?: 'https://placehold.co/300x430/111827/ffffff?text=No+Cover'; }
function format_status(?string $status): string { return ucwords(str_replace('_',' ',$status??'')); }

function media_detail_url(string $type, int $malId = 0, int $aniId = 0): string {
    $type = $type === 'manga' ? 'manga' : 'anime';
    $params = ['type' => $type];
    if ($malId > 0) $params['mal_id'] = $malId;
    if ($aniId > 0) $params['anilist_id'] = $aniId;
    if (count($params) === 1) return url('search&type=' . $type);
    return asset_url('detail.php') . '?' . http_build_query($params);
}

function media_link(string $type, array $item): string {
    $type = $type === 'manga' ? 'manga' : 'anime';
    // IMPORTANT: never overload one `id` with two namespaces.
    // Keep both identifiers when available and let detail.php resolve them in order.
    $malId = (int)($item['mal_id'] ?? $item['idMal'] ?? 0);
    $aniId = (int)($item['anilist_id'] ?? $item['id'] ?? 0);
    return media_detail_url($type, $malId, $aniId);
}
function safe_description(?string $description): string {
    if (!$description) return 'Belum ada synopsis yang tersedia.';
    return trim(strip_tags(str_replace(["<br>","<br/>","<br />"], "\n", $description)));
}


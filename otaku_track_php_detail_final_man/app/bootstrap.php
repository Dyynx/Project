<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/services/AniListService.php';
require_once __DIR__ . '/services/JikanService.php';

$api = new JikanService();

function render(string $view, array $data = []): void
{
    extract($data);
    require __DIR__ . '/../views/partials/header.php';
    require __DIR__ . '/../views/' . $view . '.php';
    require __DIR__ . '/../views/partials/footer.php';
}

function find_media_list(int $userId, string $type, int $externalId): ?array
{
    $stmt = db()->prepare('SELECT * FROM media_lists WHERE user_id=? AND media_type=? AND external_id=?');
    $stmt->execute([$userId, $type, $externalId]);
    return $stmt->fetch() ?: null;
}

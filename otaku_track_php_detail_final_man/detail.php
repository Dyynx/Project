<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

$type = get('type', 'anime') === 'manga' ? 'manga' : 'anime';
$legacyId = (int)get('id', 0);
$malId = (int)get('mal_id', 0);
$aniId = (int)get('anilist_id', 0);
$source = (string)get('source', '');

if ($malId < 1 && $aniId < 1 && $legacyId > 0) {
    if ($source === 'anilist') $aniId = $legacyId;
    else $malId = $legacyId;
}

$media = null;
$resolvedBy = null;

if ($malId > 0) {
    $media = $type === 'anime' ? $api->anime($malId) : $api->manga($malId);
    if ($media) $resolvedBy = 'mal';
}

if (!$media && $malId > 0) {
    $media = $api->aniListService()->detailByMalId(strtoupper($type), $malId);
    if ($media) $resolvedBy = 'anilist_mal';
}

if (!$media && $aniId > 0) {
    $media = $api->detailByAniListId($type, $aniId);
    if ($media) $resolvedBy = 'anilist_id';
}

if (!$media) {
    render('anime/detail_fallback', [
        'type' => $type,
        'malId' => $malId,
        'aniId' => $aniId,
        'jikanError' => $api->lastError(),
        'aniListError' => $api->aniListService()->lastError(),
    ]);
    exit;
}

// Keep the identifier namespaces explicit. Jikan's `id` is a MAL ID,
// while AniList's `id` is a different namespace.
$resolvedMalId = (int)($media['mal_id'] ?? $media['idMal'] ?? $malId);
$resolvedAniId = (int)($media['anilist_id'] ?? 0);
if ($resolvedAniId < 1 && isset($media['idMal']) && isset($media['siteUrl'])) {
    $resolvedAniId = (int)($media['id'] ?? $aniId);
}
$linkId = $resolvedMalId ?: $resolvedAniId;

/**
 * Normalize all detail sections into one predictable shape.
 * The view never needs to know whether the data came from Jikan or AniList.
 */
function detail_title(array $item): string
{
    $title = $item['title'] ?? '';
    if (is_array($title)) {
        return (string)($title['english'] ?? $title['romaji'] ?? $title['native'] ?? $title['userPreferred'] ?? 'Untitled');
    }
    return (string)($title ?: $item['name'] ?? 'Untitled');
}

function detail_image(array $item): ?string
{
    return $item['coverImage']['extraLarge']
        ?? $item['coverImage']['large']
        ?? $item['coverImage']['medium']
        ?? $item['images']['jpg']['large_image_url']
        ?? $item['images']['jpg']['image_url']
        ?? null;
}

function person_name(mixed $value): string
{
    if (is_string($value)) return trim($value);
    if (!is_array($value)) return '';
    return (string)($value['full'] ?? $value['name'] ?? $value['romaji'] ?? $value['english'] ?? $value['native'] ?? '');
}

function normalize_characters(array $media): array
{
    $rows = $media['characters']['edges'] ?? $media['characters'] ?? [];
    if (!is_array($rows)) return [];
    $out = [];
    foreach ($rows as $row) {
        $character = $row['node'] ?? $row['character'] ?? null;
        if (!is_array($character)) continue;
        $voices = $row['voiceActors'] ?? $row['voice_actors'] ?? [];
        if (!is_array($voices)) $voices = [];
        $voice = $voices[0] ?? null;
        if (is_array($voice) && isset($voice['person']) && is_array($voice['person'])) $voice = $voice['person'];
        $out[] = [
            'name' => person_name($character['name'] ?? $character['name'] ?? 'Character'),
            'role' => (string)($row['role'] ?? 'Character'),
            'image' => $character['image']['large'] ?? $character['image']['medium'] ?? $character['images']['jpg']['image_url'] ?? null,
            'voice' => is_array($voice) ? person_name($voice['name'] ?? $voice['person']['name'] ?? '') : '',
            'voice_image' => is_array($voice) ? ($voice['image']['large'] ?? $voice['image']['medium'] ?? $voice['images']['jpg']['image_url'] ?? null) : null,
            'voice_language' => is_array($voice) ? (string)($voice['language'] ?? '') : '',
        ];
    }
    return $out;
}

function normalize_staff(array $media): array
{
    $rows = $media['staff']['edges'] ?? $media['staff'] ?? [];
    if (!is_array($rows)) return [];
    $out = [];
    foreach ($rows as $row) {
        $person = $row['node'] ?? $row['staff'] ?? $row['person'] ?? null;
        if (!$person && isset($row['person'])) $person = $row['person'];
        if (!is_array($person)) continue;
        $positions = $row['positions'] ?? $row['position'] ?? [];
        if (!is_array($positions)) $positions = [$positions];
        $role = (string)($row['role'] ?? (implode(', ', array_filter(array_map('strval', $positions))) ?: 'Staff'));
        $out[] = [
            'name' => person_name($person['name'] ?? $person),
            'role' => $role,
            'image' => $person['image']['large'] ?? $person['image']['medium'] ?? $person['images']['jpg']['image_url'] ?? null,
        ];
    }
    return $out;
}

function normalize_relations(array $media, string $type): array
{
    $rows = $media['relations']['edges'] ?? $media['relations'] ?? [];
    $out = [];
    if (!is_array($rows)) return [];
    foreach ($rows as $row) {
        if (!is_array($row)) continue;
        $node = $row['node'] ?? $row['entry'] ?? null;
        $relation = (string)($row['relationType'] ?? $row['relation'] ?? 'Related');
        if ($node && is_array($node)) {
            $out[] = ['relation'=>$relation,'type'=>strtoupper((string)($node['type'] ?? $type)),'mal_id'=>(int)($node['idMal'] ?? $node['mal_id'] ?? 0),'anilist_id'=>(int)($node['id'] ?? 0),'title'=>detail_title($node),'image'=>detail_image($node)];
            continue;
        }
        $entries = $row['entry'] ?? $row['entries'] ?? [];
        if (is_array($entries)) foreach ($entries as $entry) {
            if (!is_array($entry)) continue;
            $out[] = ['relation'=>$relation,'type'=>strtoupper((string)($entry['type'] ?? $type)),'mal_id'=>(int)($entry['mal_id'] ?? $entry['id'] ?? 0),'anilist_id'=>(int)($entry['anilist_id'] ?? 0),'title'=>detail_title($entry),'image'=>detail_image($entry)];
        }
    }
    return $out;
}

function normalize_recommendations(array $media, string $type): array
{
    $rows = $media['recommendations']['nodes'] ?? $media['recommendations'] ?? [];
    if (!is_array($rows)) return [];
    $out=[];
    foreach ($rows as $row) {
        $entry = $row['mediaRecommendation'] ?? $row['entry'] ?? $row;
        if (!is_array($entry)) continue;
        $score = $entry['averageScore'] ?? $entry['score'] ?? null;
        $out[] = ['mal_id'=>(int)($entry['idMal'] ?? $entry['mal_id'] ?? 0),'anilist_id'=>(int)($entry['id'] ?? $entry['anilist_id'] ?? 0),'type'=>strtoupper((string)($entry['type'] ?? $type)),'title'=>detail_title($entry),'image'=>detail_image($entry),'score'=>$score !== null ? (float)$score/10 : null];
    }
    return $out;
}

$recommendations = normalize_recommendations($media, $type);
if (!$recommendations && $resolvedMalId > 0) {
    // Anime has a dedicated Jikan recommendation endpoint. Manga normally
    // arrives through AniList's embedded recommendation connection.
    $recommendations = normalize_recommendations(
        ['recommendations' => array_map(
            static fn($item) => ['entry' => $item],
            $api->recommendations($resolvedMalId, $type)
        )],
        $type
    );
}

$characters = normalize_characters($media);
$staff = normalize_staff($media);
$relations = normalize_relations($media, $type);

$links = db()->prepare('SELECT * FROM watch_links WHERE media_type=? AND external_id=? ORDER BY provider');
$links->execute([$type, $linkId]);
$watchLinks = $links->fetchAll();

$myList = current_user() ? find_media_list((int)current_user()['id'], $type, $linkId) : null;

if (defined('DEBUG_MODE') && DEBUG_MODE) {
    $trace = [
        'type' => $type,
        'requested_mal_id' => $malId,
        'requested_anilist_id' => $aniId,
        'resolved_mal_id' => $resolvedMalId,
        'resolved_anilist_id' => $resolvedAniId,
        'resolved_by' => $resolvedBy,
        'section_counts' => [
            'characters' => count($characters),
            'staff' => count($staff),
            'relations' => count($relations),
            'recommendations' => count($recommendations),
            'external_links' => count($media['externalLinks'] ?? []),
            'streaming' => count($media['streamingEpisodes'] ?? ($media['streaming'] ?? [])),
            'tags' => count($media['tags'] ?? []),
        ],
    ];
    @file_put_contents(CACHE_DIR . '/last_detail_trace.json', json_encode($trace, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
}

render('anime/detail', compact(
    'media', 'type', 'recommendations', 'watchLinks', 'myList',
    'characters', 'staff', 'relations', 'resolvedMalId', 'resolvedAniId', 'linkId'
));

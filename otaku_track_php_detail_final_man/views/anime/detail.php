<?php
$rawTitle = $media['title'] ?? 'Detail';
$title = is_array($rawTitle)
    ? (string)($rawTitle['english'] ?? $rawTitle['romaji'] ?? $rawTitle['native'] ?? $rawTitle['userPreferred'] ?? 'Detail')
    : (string)$rawTitle;

$img = $media['coverImage']['extraLarge']
    ?? $media['coverImage']['large']
    ?? $media['images']['jpg']['large_image_url']
    ?? $media['images']['jpg']['image_url']
    ?? null;
$banner = $media['bannerImage'] ?? null;
$description = safe_description($media['description'] ?? $media['synopsis'] ?? null);

$genreValues = [];
foreach (($media['genres'] ?? []) as $genre) {
    $name = is_array($genre) ? ($genre['name'] ?? '') : (string)$genre;
    if ($name !== '') $genreValues[] = $name;
}

$tagValues = [];
foreach (($media['tags'] ?? []) as $tag) {
    $name = is_array($tag) ? ($tag['name'] ?? '') : (string)$tag;
    if ($name !== '') $tagValues[] = $name;
}
$themeValues = [];
foreach (($media['themes'] ?? []) as $theme) {
    $name = is_array($theme) ? ($theme['name'] ?? '') : (string)$theme;
    if ($name !== '') $themeValues[] = $name;
}
foreach (($media['demographics'] ?? []) as $demo) {
    $name = is_array($demo) ? ($demo['name'] ?? '') : (string)$demo;
    if ($name !== '') $themeValues[] = $name;
}
$themeTags = array_values(array_unique(array_merge($genreValues, $themeValues, $tagValues)));

$studios = [];
$studioRows = $media['studios']['nodes'] ?? $media['studios'] ?? [];
if (is_array($studioRows)) {
    foreach ($studioRows as $studio) {
        $name = is_array($studio) ? ($studio['name'] ?? '') : (string)$studio;
        if ($name !== '') $studios[] = $name;
    }
}

$externalLinks = is_array($media['externalLinks'] ?? null) ? $media['externalLinks'] : [];
$streaming = $media['streamingEpisodes'] ?? $media['streaming'] ?? [];
if (!is_array($streaming)) $streaming = [];

$score = null;
if (isset($media['averageScore']) && $media['averageScore'] !== null) {
    $score = (float)$media['averageScore'] / 10;
} elseif (isset($media['score']) && $media['score'] !== null && $media['score'] !== '') {
    $score = (float)$media['score'];
}

$format = (string)($media['format'] ?? $media['type'] ?? 'Unknown');
$status = (string)($media['status'] ?? 'Unknown');
$year = $media['seasonYear'] ?? ($media['aired']['prop']['from']['year'] ?? null);
$source = (string)($media['source'] ?? 'N/A');
$origin = (string)($media['countryOfOrigin'] ?? 'N/A');
$season = $media['season'] ?? null;
$startDate = $media['startDate'] ?? ($media['aired']['from'] ?? null);
$endDate = $media['endDate'] ?? ($media['aired']['to'] ?? null);

function detail_date_label(mixed $date): string {
    if (is_array($date)) {
        $y = $date['year'] ?? null;
        $m = $date['month'] ?? null;
        $d = $date['day'] ?? null;
        if ($y && $m && $d) return sprintf('%04d-%02d-%02d', $y, $m, $d);
        if ($y && $m) return sprintf('%04d-%02d', $y, $m);
        if ($y) return (string)$y;
        $from = $date['from'] ?? null;
        if (is_string($from)) return substr($from, 0, 10);
    }
    if (is_string($date) && $date !== '') return substr($date, 0, 10);
    return 'N/A';
}

function detail_type_link(array $item, string $fallbackType): string {
    $itemType = strtoupper((string)($item['type'] ?? $fallbackType));
    return $itemType === 'MANGA' ? 'manga' : 'anime';
}

$myListStatus = $myList['status'] ?? ($type === 'manga' ? 'plan_to_read' : 'plan_to_watch');
$malId = (int)($resolvedMalId ?? $media['mal_id'] ?? $media['idMal'] ?? 0);
$anilistId = (int)($resolvedAniId ?? $media['anilist_id'] ?? 0);
$linkId = (int)($linkId ?? ($malId ?: $anilistId));
?>

<div class="detail-page">
  <section class="detail-hero" style="--cover:url('<?=e((string)($banner ?: $img ?: ''))?>')">
    <div class="container py-5">
      <div class="row g-4 align-items-end">
        <div class="col-12 col-md-3 col-lg-2">
          <img class="detail-cover" src="<?=e(image_url($img))?>" alt="<?=e($title)?>">
        </div>
        <div class="col-12 col-md-9 col-lg-10">
          <div class="eyebrow">MEDIA DETAIL</div>
          <h1 class="display-5 fw-bold mb-3"><?=e($title)?></h1>
          <div class="d-flex flex-wrap gap-2 mb-3">
            <span class="badge text-bg-warning">★ <?=e($score !== null ? number_format($score, 1) : 'N/A')?></span>
            <span class="badge badge-soft"><?=e(format_status($status))?></span>
            <span class="badge badge-soft"><?=e($format)?></span>
            <?php if ($year): ?><span class="badge badge-soft"><?=e((string)$year)?></span><?php endif; ?>
          </div>
          <div class="d-flex flex-wrap gap-2">
            <?php foreach (array_slice($genreValues, 0, 8) as $genre): ?>
              <span class="chip"><?=e($genre)?></span>
            <?php endforeach; ?>
          </div>
          <div class="detail-actions mt-4">
            <?php if (current_user()): ?>
              <button class="btn btn-primary" type="button" onclick="document.getElementById('my-list-card').scrollIntoView({behavior:'smooth'})"><i class="fa-solid fa-plus"></i> My List</button>
            <?php else: ?>
              <a class="btn btn-primary" href="<?=e(url('login'))?>"><i class="fa-solid fa-right-to-bracket"></i> Login untuk My List</a>
            <?php endif; ?>
            <button class="btn btn-outline-light" type="button" data-bookmark data-id="<?=e((string)($malId ?: $anilistId))?>"><i class="fa-regular fa-bookmark"></i> <span>Bookmark</span></button>
            <?php if (!empty($media['siteUrl'])): ?>
              <a class="btn btn-outline-light" href="<?=e((string)$media['siteUrl'])?>" target="_blank" rel="noopener">AniList <i class="fa-solid fa-arrow-up-right-from-square"></i></a>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>
  </section>

  <div class="container py-4">
    <div class="row g-4">
      <div class="col-lg-8">

        <!-- 1. SYNOPSIS -->
        <section class="content-card mb-4" id="synopsis">
          <div class="card-eyebrow">STORY</div>
          <h3>Synopsis</h3>
          <p class="detail-synopsis mb-0"><?=nl2br(e($description))?></p>
        </section>

        <!-- 2. INFORMATION -->
        <section class="content-card mb-4" id="information">
          <div class="card-eyebrow">INFORMATION</div>
          <h3>Information</h3>
          <div class="info-grid">
            <div><span>Score</span><strong><?=e($score !== null ? number_format($score, 1) . ' / 10' : 'N/A')?></strong></div>
            <div><span>Popularity</span><strong><?=e(isset($media['popularity']) ? (string)$media['popularity'] : 'N/A')?></strong></div>
            <div><span>Rank</span><strong><?=e(isset($media['rank']) ? '#' . (string)$media['rank'] : 'N/A')?></strong></div>
            <div><span>Members / Favorites</span><strong><?=e(isset($media['members']) ? (string)$media['members'] : (isset($media['favourites']) ? (string)$media['favourites'] : 'N/A'))?></strong></div>
            <div><span>Rating</span><strong><?=e((string)($media['rating'] ?? $media['rating']['string'] ?? 'N/A'))?></strong></div>
            <div><span><?= $type === 'manga' ? 'Chapters' : 'Episodes' ?></span><strong><?=e((string)($type === 'manga' ? ($media['chapters'] ?? 'N/A') : ($media['episodes'] ?? 'N/A')))?></strong></div>
            <div><span><?= $type === 'manga' ? 'Volumes' : 'Duration' ?></span><strong><?=e((string)($type === 'manga' ? ($media['volumes'] ?? 'N/A') : (($media['duration'] ?? 'N/A') !== 'N/A' ? $media['duration'] . ' min' : 'N/A')))?></strong></div>
            <div><span>Format</span><strong><?=e($format)?></strong></div>
            <div><span>Year</span><strong><?=e($year ? (string)$year : 'N/A')?></strong></div>
            <div><span>Start</span><strong><?=e(detail_date_label($startDate))?></strong></div>
            <div><span>End</span><strong><?=e(detail_date_label($endDate))?></strong></div>
            <div><span>Season</span><strong><?=e($season ? ucfirst(strtolower((string)$season)) : 'N/A')?></strong></div>
            <div><span>Studio / Publisher</span><strong><?=e($studios ? implode(', ', $studios) : 'N/A')?></strong></div>
            <div><span>Source</span><strong><?=e($source)?></strong></div>
            <div><span>Origin</span><strong><?=e($origin)?></strong></div>
          </div>
        </section>

        <!-- 3. THEMES & TAGS -->
        <section class="content-card mb-4" id="themes-tags">
          <div class="card-eyebrow">CLASSIFICATION</div>
          <h3>Themes &amp; Tags</h3>
          <?php if ($genreValues): ?>
            <div class="detail-subtitle">Genres</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($genreValues as $genre): ?><span class="chip"><?=e($genre)?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($themeValues): ?>
            <div class="detail-subtitle">Themes / Demographics</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach (array_slice($themeValues, 0, 30) as $tag): ?><span class="chip"><?=e($tag)?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($tagValues): ?>
            <div class="detail-subtitle">AniList Tags</div>
            <div class="d-flex flex-wrap gap-2">
              <?php foreach (array_slice($tagValues, 0, 30) as $tag): ?><span class="chip"><?=e($tag)?></span><?php endforeach; ?>
            </div>
          <?php elseif (!$themeValues && !$genreValues): ?>
            <div class="detail-empty"><i class="fa-solid fa-tags"></i><span>Belum ada data klasifikasi tambahan untuk judul ini.</span></div>
          <?php endif; ?>
        </section>

        <!-- 4. WHERE TO WATCH / READ -->
        <section class="content-card mb-4" id="where-to-watch">
          <div class="card-eyebrow"><?= $type === 'manga' ? 'READ' : 'WATCH' ?></div>
          <h3><?= $type === 'manga' ? 'Where to Read / Official Links' : 'Where to Watch' ?></h3>
          <?php if ($watchLinks || $externalLinks || $streaming): ?>
            <div class="platform-grid">
              <?php foreach ($watchLinks as $l): ?>
                <a class="platform-card" href="<?=e((string)$l['url'])?>" target="_blank" rel="noopener">
                  <i class="fa-solid fa-circle-play"></i>
                  <div><strong><?=e((string)$l['provider'])?></strong><span>Official link from site admin</span></div>
                </a>
              <?php endforeach; ?>
              <?php foreach (array_slice($externalLinks, 0, 10) as $l): if (empty($l['url'])) continue; ?>
                <a class="platform-card" href="<?=e((string)$l['url'])?>" target="_blank" rel="noopener">
                  <i class="fa-solid fa-link"></i>
                  <div><strong><?=e((string)($l['site'] ?? 'Official Site'))?></strong><span><?=e((string)($l['type'] ?? 'External link'))?></span></div>
                </a>
              <?php endforeach; ?>
              <?php foreach (array_slice($streaming, 0, 10) as $s): if (empty($s['url'])) continue; ?>
                <a class="platform-card" href="<?=e((string)$s['url'])?>" target="_blank" rel="noopener">
                  <i class="fa-solid fa-tv"></i>
                  <div><strong><?=e((string)($s['site'] ?? 'Streaming'))?></strong><span><?=e((string)($s['title'] ?? 'Watch'))?></span></div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="detail-empty"><i class="fa-solid fa-tv"></i><span>Belum ada platform/link resmi yang dikembalikan API atau database admin.</span></div>
          <?php endif; ?>
        </section>

        <!-- 5. CHARACTERS & VOICE ACTORS -->
        <section class="content-card mb-4" id="characters">
          <div class="card-eyebrow">CAST</div>
          <h3>Characters &amp; Voice Actors</h3>
          <?php if ($characters): ?>
            <div class="people-grid">
              <?php foreach (array_slice($characters, 0, 18) as $character): ?>
                <div class="person-card">
                  <img src="<?=e(image_url($character['image'] ?? null))?>" alt="<?=e((string)$character['name'])?>" loading="lazy">
                  <div>
                    <strong><?=e((string)$character['name'])?></strong>
                    <span><?=e((string)$character['role'])?></span>
                    <?php if (!empty($character['voice'])): ?><small><i class="fa-solid fa-microphone"></i> <?=e((string)$character['voice'])?></small><?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="detail-empty"><i class="fa-solid fa-users"></i><span>Data characters dan voice actors belum tersedia dari API untuk judul ini.</span></div>
          <?php endif; ?>
        </section>

        <!-- 6. STAFF & PRODUCTION -->
        <section class="content-card mb-4" id="staff">
          <div class="card-eyebrow">PRODUCTION</div>
          <h3>Staff &amp; Production</h3>
          <?php if ($studios): ?>
            <div class="detail-subtitle">Studios / Production</div>
            <div class="d-flex flex-wrap gap-2 mb-3">
              <?php foreach ($studios as $studio): ?><span class="chip"><i class="fa-solid fa-building"></i> <?=e($studio)?></span><?php endforeach; ?>
            </div>
          <?php endif; ?>
          <?php if ($staff): ?>
            <div class="people-grid">
              <?php foreach (array_slice($staff, 0, 18) as $person): ?>
                <div class="person-card">
                  <img src="<?=e(image_url($person['image'] ?? null))?>" alt="<?=e((string)$person['name'])?>" loading="lazy">
                  <div>
                    <strong><?=e((string)$person['name'])?></strong>
                    <span><?=e((string)$person['role'])?></span>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php elseif (!$studios): ?>
            <div class="detail-empty"><i class="fa-solid fa-user-gear"></i><span>Data staff dan production belum tersedia dari API untuk judul ini.</span></div>
          <?php else: ?>
            <div class="detail-empty"><i class="fa-solid fa-user-gear"></i><span>Studio tersedia, tetapi daftar staff belum tersedia dari API.</span></div>
          <?php endif; ?>
        </section>

        <!-- 7. RELATIONS -->
        <section class="content-card mb-4" id="relations">
          <div class="card-eyebrow">FRANCHISE</div>
          <h3>Relations</h3>
          <?php if ($relations): ?>
            <div class="relation-list">
              <?php foreach (array_slice($relations, 0, 18) as $relation): ?>
                <?php
                  $relationType = detail_type_link(['type' => $relation['type'] ?? $type], $type);
                  $relationItem = [
                      'mal_id' => (int)($relation['mal_id'] ?? 0),
                      'anilist_id' => (int)($relation['anilist_id'] ?? 0),
                  ];
                ?>
                <a class="relation-card" href="<?=e(media_link($relationType, $relationItem))?>">
                  <span><?=e(str_replace('_', ' ', (string)$relation['relation']))?></span>
                  <strong><?=e((string)$relation['title'])?></strong>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="detail-empty"><i class="fa-solid fa-diagram-project"></i><span>Belum ada relation (prequel, sequel, side story, alternative, dll.) dari API.</span></div>
          <?php endif; ?>
        </section>

        <!-- 8. RECOMMENDATIONS -->
        <section class="content-card mb-4" id="recommendations">
          <div class="card-eyebrow">DISCOVER</div>
          <h3>Recommendations</h3>
          <?php if ($recommendations): ?>
            <div class="media-grid mini-grid">
              <?php foreach (array_slice($recommendations, 0, 8) as $recommendation): ?>
                <?php
                  $recommendationType = detail_type_link($recommendation, $type);
                  $recommendationItem = [
                      'mal_id' => (int)($recommendation['mal_id'] ?? 0),
                      'anilist_id' => (int)($recommendation['anilist_id'] ?? 0),
                  ];
                  $recScore = $recommendation['score'] ?? null;
                ?>
                <a class="media-card" href="<?=e(media_link($recommendationType, $recommendationItem))?>">
                  <div class="poster">
                    <img src="<?=e(image_url($recommendation['image'] ?? null))?>" alt="<?=e((string)$recommendation['title'])?>" loading="lazy">
                    <?php if ($recScore !== null): ?><span class="score"><i class="fa-solid fa-star"></i> <?=e(number_format((float)$recScore, 1))?></span><?php endif; ?>
                  </div>
                  <div class="media-title"><?=e((string)$recommendation['title'])?></div>
                </a>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="detail-empty"><i class="fa-solid fa-wand-magic-sparkles"></i><span>Belum ada recommendations yang dikembalikan API untuk judul ini.</span></div>
          <?php endif; ?>
        </section>

      </div>

      <aside class="col-lg-4">
        <?php if (current_user()): ?>
          <div class="content-card sticky-lg-top" id="my-list-card" style="top:90px">
            <div class="card-eyebrow">PERSONAL TRACKER</div>
            <h3>My List</h3>
            <p class="text-secondary small">Simpan status, skor pribadi, dan progress seperti tracker ala AniList.</p>
            <form method="post">
              <input type="hidden" name="_csrf" value="<?=e(csrf_token())?>">
              <input type="hidden" name="action" value="save_list">
              <input type="hidden" name="media_type" value="<?=e($type)?>">
              <input type="hidden" name="external_id" value="<?=e((string)$linkId)?>">
              <input type="hidden" name="mal_id" value="<?=e((string)$malId)?>">
              <input type="hidden" name="anilist_id" value="<?=e((string)$anilistId)?>">
              <input type="hidden" name="title" value="<?=e($title)?>">
              <input type="hidden" name="image_url" value="<?=e((string)$img)?>">
              <label class="form-label">Status</label>
              <select name="status" class="form-select mb-3">
                <?php $statuses = $type === 'manga' ? ['reading','completed','on_hold','dropped','plan_to_read'] : ['watching','completed','on_hold','dropped','plan_to_watch']; ?>
                <?php foreach ($statuses as $itemStatus): ?>
                  <option value="<?=e($itemStatus)?>" <?=$myListStatus === $itemStatus ? 'selected' : ''?>><?=e(ucwords(str_replace('_', ' ', $itemStatus)))?></option>
                <?php endforeach; ?>
              </select>
              <label class="form-label">My Score</label>
              <input name="score" type="number" min="0" max="10" step=".1" class="form-control mb-3" value="<?=e((string)($myList['score'] ?? ''))?>">
              <label class="form-label">Progress</label>
              <input name="progress" type="number" min="0" class="form-control mb-3" value="<?=e((string)($myList['progress'] ?? 0))?>">
              <button class="btn btn-primary w-100"><i class="fa-solid fa-bookmark"></i> Simpan ke My List</button>
            </form>
          </div>
        <?php else: ?>
          <div class="content-card" id="my-list-card">
            <div class="card-eyebrow">PERSONAL TRACKER</div>
            <h3>My List</h3>
            <p class="text-secondary">Login untuk menyimpan judul ini ke watchlist/reading list dan memberi score sendiri.</p>
            <a class="btn btn-primary w-100" href="<?=e(url('login'))?>">Login</a>
          </div>
        <?php endif; ?>

        <div class="content-card mt-3">
          <div class="card-eyebrow">SOURCE</div>
          <h3>Data</h3>
          <div class="source-list">
            <div><span>MAL ID</span><strong><?=e($malId ? (string)$malId : 'N/A')?></strong></div>
            <div><span>AniList ID</span><strong><?=e($anilistId ? (string)$anilistId : 'N/A')?></strong></div>
            <div><span>Resolver</span><strong><?=e((string)($media['_source'] ?? 'API'))?></strong></div>
            <div><span>Site</span><strong><?=e(!empty($media['siteUrl']) ? 'AniList' : 'Jikan / MAL')?></strong></div>
          </div>
        </div>
      </aside>
    </div>
  </div>
</div>

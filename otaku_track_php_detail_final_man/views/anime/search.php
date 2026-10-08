<?php
$typeName = $type === 'manga' ? 'Manga' : 'Anime';
$poster = fn($a) => image_url($a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? null);
$titleOf = fn($a) => $a['title'] ?? $a['title_english'] ?? 'Untitled';
$genres = ['Action','Adventure','Comedy','Drama','Fantasy','Horror','Mystery','Romance','Sci-Fi','Sports','Supernatural','Thriller'];
$formats = $type === 'manga' ? ['MANGA','NOVEL','ONE_SHOT','MANHWA','MANHUA'] : ['TV','MOVIE','ONA','OVA','SPECIAL','MUSIC'];
?>
<div class="container py-4" data-search-page data-q="<?=e($q)?>" data-type="<?=e($type)?>">
  <div class="section-head mb-4"><div><div class="eyebrow">EXPLORE</div><h1><?=e($typeName)?></h1><p class="text-secondary mb-0"><?= $q !== '' ? 'Hasil pencarian untuk “'.e($q).'”.' : 'Browse katalog tanpa harus mengetik pencarian.' ?></p></div><span class="catalog-count"><strong id="search-result-count"><?=count($results ?? [])?></strong> judul</span></div>
  <div class="explore-layout">
    <aside class="filter-sidebar">
      <form method="get" action="<?=e(asset_url('index.php'))?>">
        <input type="hidden" name="page" value="search"><input type="hidden" name="type" value="<?=e($type)?>">
        <div class="filter-title"><span>Filter</span><a href="<?=e(url('search&type='.$type))?>">Reset</a></div>
        <label>Search</label><input class="form-control" name="q" value="<?=e($q)?>" placeholder="Cari judul..."><br>
        <label>Format</label><select class="form-select" name="format"><option value="">Semua format</option><?php foreach($formats as $f): ?><option value="<?=e($f)?>" <?=($filters['type']??'')===$f?'selected':''?>><?=e($f)?></option><?php endforeach; ?></select><br>
        <label>Genre</label><select class="form-select" name="genre"><option value="">Semua genre</option><?php foreach($genres as $g): ?><option value="<?=e($g)?>" <?=get('genre')===$g?'selected':''?>><?=e($g)?></option><?php endforeach; ?></select><br>
        <label>Season</label><select class="form-select" name="season"><option value="">Semua season</option><?php foreach(['WINTER','SPRING','SUMMER','FALL'] as $s): ?><option value="<?=e($s)?>" <?=get('season')===$s?'selected':''?>><?=e($s)?></option><?php endforeach; ?></select><br>
        <label>Year</label><input class="form-control" name="year" type="number" min="1960" max="2100" value="<?=e(get('year',''))?>" placeholder="2026"><br>
        <label>Min. Score</label><input class="form-control" name="min_score" type="number" min="0" max="10" step="0.1" value="<?=e(get('min_score',''))?>" placeholder="7.0"><br>
        <label>Sort</label><select class="form-select" name="sort"><option value="desc" <?=get('sort','desc')==='desc'?'selected':''?>>Terbaru / tinggi</option><option value="asc" <?=get('sort')==='asc'?'selected':''?>>Terendah</option></select>
        <button class="filter-btn" type="submit">Terapkan filter</button>
      </form>
    </aside>
    <section>
      <div class="results-bar"><span><strong id="catalog-label"><?=count($results ?? [])?></strong> judul ditampilkan</span><span class="muted">AniList + Jikan</span></div>
      <div class="media-grid search-results-grid" id="search-results-grid">
      <?php foreach (($results ?? []) as $a): ?><a class="media-card" href="<?=e(media_link($type,$a))?>"><div class="poster"><img src="<?=e($poster($a))?>" alt="<?=e($titleOf($a))?>" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> <?=e((string)($a['score']??'N/A'))?></span></div><div class="media-title"><?=e($titleOf($a))?></div><div class="media-meta"><?=e($a['type']??$typeName)?> · <?=e((string)($a['year']??''))?></div></a><?php endforeach; ?>
      </div>
      <?php if(empty($results)): ?><div class="empty-grid-message" id="search-loading">Memuat katalog...</div><?php endif; ?>
    </section>
  </div>
</div>

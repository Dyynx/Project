<?php
$poster = fn($a) => image_url($a['images']['jpg']['large_image_url'] ?? $a['images']['jpg']['image_url'] ?? null);
$titleOf = fn($a) => $a['title'] ?? $a['title_english'] ?? 'Untitled';
?>
<section class="home-hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <div class="eyebrow">PELACAK ANIME & MANGA</div>
      <h1>Temukan, simpan, dan <span>diskusikan</span> cerita berikutnya.</h1>
      <p>Jelajahi anime dan manga, cari judul, simpan ke daftar pribadi, dan buka detail lengkap seperti synopsis, karakter, cast, studio, platform, serta rekomendasi.</p>
      <form class="hero-search" method="get" action="<?=e(asset_url('index.php'))?>">
        <input type="hidden" name="page" value="search"><input type="hidden" name="type" value="anime">
        <input name="q" placeholder="Cari judul anime, misal: One Piece" value="<?=e((string)get('q',''))?>">
        <button type="submit"><i class="fa-solid fa-magnifying-glass"></i> Cari</button>
      </form>
      <div class="quick-links"><a href="<?=e(url('search&type=anime'))?>">Explore Anime</a><a href="<?=e(url('search&type=anime&order_by=score'))?>">Skor tertinggi</a><a href="<?=e(url('search&type=manga'))?>">Explore Manga</a></div>
    </div>
    <div class="hero-art">
      <div class="art-glow"></div>
      <?php $art = array_slice($popular ?? [], 0, 4); if ($art): foreach ($art as $i=>$a): ?>
        <a href="<?=e(media_link('anime',$a))?>"><img class="float-cover c<?=$i+1?>" src="<?=e($poster($a))?>" alt="<?=e($titleOf($a))?>"></a>
      <?php endforeach; else: ?><div class="api-placeholder"><div><i class="fa-solid fa-cloud-arrow-down"></i><br>Memuat poster...</div></div><?php endif; ?>
    </div>
  </div>
</section>

<div class="container" id="home-data">
  <section class="section">
    <div class="section-head"><div><div class="eyebrow">TRENDING SEKARANG</div><h2>Popular Anime</h2></div><a href="<?=e(url('search&type=anime'))?>">Lihat semua <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="media-grid home-grid"><?php foreach (($popular ?? []) as $a): ?><a class="media-card" href="<?=e(media_link('anime',$a))?>"><div class="poster"><img src="<?=e($poster($a))?>" alt="<?=e($titleOf($a))?>" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> <?=e((string)($a['score'] ?? 'N/A'))?></span></div><div class="media-title"><?=e($titleOf($a))?></div><div class="media-meta"><?=e($a['type'] ?? 'Anime')?> · <?=e((string)($a['year'] ?? ''))?></div></a><?php endforeach; ?></div>
  </section>
  <section class="section pt-3">
    <div class="section-head"><div><div class="eyebrow">TOP PICKS</div><h2>Skor Tertinggi</h2></div><a href="<?=e(url('search&type=anime&order_by=score'))?>">Lihat semua <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="media-grid home-grid"><?php foreach (($top ?? []) as $a): ?><a class="media-card" href="<?=e(media_link('anime',$a))?>"><div class="poster"><img src="<?=e($poster($a))?>" alt="<?=e($titleOf($a))?>" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> <?=e((string)($a['score'] ?? 'N/A'))?></span></div><div class="media-title"><?=e($titleOf($a))?></div><div class="media-meta"><?=e($a['type'] ?? 'Anime')?> · <?=e((string)($a['year'] ?? ''))?></div></a><?php endforeach; ?></div>
  </section>
  <section class="section pt-3 pb-5">
    <div class="section-head"><div><div class="eyebrow">MANGA</div><h2>Popular Manga</h2></div><a href="<?=e(url('search&type=manga'))?>">Lihat semua <i class="fa-solid fa-arrow-right"></i></a></div>
    <div class="media-grid home-grid"><?php foreach (($manga ?? []) as $a): ?><a class="media-card" href="<?=e(media_link('manga',$a))?>"><div class="poster"><img src="<?=e($poster($a))?>" alt="<?=e($titleOf($a))?>" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> <?=e((string)($a['score'] ?? 'N/A'))?></span></div><div class="media-title"><?=e($titleOf($a))?></div><div class="media-meta"><?=e($a['type'] ?? 'Manga')?> · <?=e((string)($a['year'] ?? ''))?></div></a><?php endforeach; ?></div>
  </section>
</div>

<?php
$typeLabel = $type === 'manga' ? 'Manga' : 'Anime';
$malIdJs = (int)$malId;
$aniIdJs = (int)$aniId;
?>
<main class="detail-page container py-4" id="detail-fallback" data-type="<?=e($type)?>" data-mal-id="<?=$malIdJs?>" data-ani-id="<?=$aniIdJs?>">
  <div class="content-card mb-4">
    <div class="card-eyebrow">DETAIL <?=e(strtoupper($typeLabel))?></div>
    <div id="fallback-status" class="py-4 text-center">
      <div class="spinner-border text-primary mb-3" role="status"></div>
      <h3 class="mb-2">Memuat detail...</h3>
      <p class="text-secondary mb-0">Resolver server sedang gagal mengambil data. Mencoba resolver AniList langsung.</p>
    </div>
    <div id="fallback-error" class="alert alert-danger d-none"></div>
  </div>
  <div id="fallback-content" class="d-none">
    <section class="detail-hero mb-4" id="fb-hero"><div class="container py-5"><div class="row align-items-end g-4">
      <div class="col-auto"><img id="fb-cover" class="detail-cover" alt="Cover"></div>
      <div class="col"><div class="card-eyebrow">ANILIST FALLBACK</div><h1 id="fb-title" class="display-6 fw-bold mb-2"></h1><div id="fb-meta" class="text-secondary mb-3"></div><div id="fb-genres" class="d-flex flex-wrap gap-2"></div></div>
    </div></div></section>
    <div class="row g-4">
      <div class="col-lg-8"><div class="content-card"><div class="card-eyebrow">SYNOPSIS</div><p id="fb-description" class="mb-0" style="white-space:pre-line"></p></div></div>
      <div class="col-lg-4"><div class="content-card"><div class="card-eyebrow">INFORMATION</div><div id="fb-info"></div></div></div>
    </div>
    <div class="content-card mt-4"><div class="card-eyebrow">DATA SOURCE</div><p class="mb-0">Detail berhasil ditemukan menggunakan ID media yang sama. Coba buka ulang setelah API server normal agar bagian Characters, Staff, Relations, dan Recommendations dari server ikut dimuat.</p></div>
  </div>
</main>
<script>
(async function(){
  const root=document.getElementById('detail-fallback');
  const status=document.getElementById('fallback-status');
  const error=document.getElementById('fallback-error');
  const content=document.getElementById('fallback-content');
  const type=(root.dataset.type||'anime').toUpperCase();
  const malId=Number(root.dataset.malId||0);
  const aniId=Number(root.dataset.aniId||0);
  const query=`query($type:MediaType,$id:Int,$idMal:Int){Media(type:$type,id:$id,idMal:$idMal){id idMal type format status title{romaji english native userPreferred} description(asHtml:false) startDate{year month day} season seasonYear episodes chapters volumes averageScore genres coverImage{large extraLarge} bannerImage studios(isMain:true){nodes{name}}}}`;
  const variables={type,id:aniId>0?aniId:null,idMal:malId>0?malId:null};
  try{
    const res=await fetch('https://graphql.anilist.co',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({query,variables})});
    const json=await res.json();
    const m=json?.data?.Media;
    if(!m) throw new Error(json?.errors?.[0]?.message||'AniList tidak mengembalikan media untuk ID tersebut.');
    const title=m.title?.english||m.title?.romaji||m.title?.native||'Untitled';
    document.getElementById('fb-title').textContent=title;
    document.getElementById('fb-cover').src=m.coverImage?.extraLarge||m.coverImage?.large||'';
    document.getElementById('fb-description').textContent=m.description||'Belum ada synopsis.';
    document.getElementById('fb-meta').textContent=[m.format,m.status,m.seasonYear,m.averageScore?('Score '+(m.averageScore/10).toFixed(1)):''].filter(Boolean).join(' · ');
    document.getElementById('fb-genres').innerHTML=(m.genres||[]).map(g=>`<span class="chip">${String(g).replace(/[&<>]/g,x=>({'&':'&amp;','<':'&lt;','>':'&gt;'}[x]))}</span>`).join('');
    const info=[['MAL ID',m.idMal||malId],['AniList ID',m.id||aniId],['Episodes',m.episodes||'-'],['Chapters',m.chapters||'-'],['Studio',(m.studios?.nodes||[]).map(x=>x.name).join(', ')||'-']];
    document.getElementById('fb-info').innerHTML=info.map(x=>`<div class="d-flex justify-content-between border-bottom py-2"><span class="text-secondary">${x[0]}</span><strong>${x[1]}</strong></div>`).join('');
    if(m.bannerImage) document.getElementById('fb-hero').style.setProperty('--cover',`url("${m.bannerImage.replace(/"/g,'')}" )`);
    status.classList.add('d-none'); content.classList.remove('d-none');
  }catch(e){
    status.classList.add('d-none'); error.classList.remove('d-none');
    error.innerHTML='<strong>Detail belum bisa diambil.</strong><br>'+String(e.message||e)+'<br><small>MAL ID: '+malId+' · AniList ID: '+aniId+'</small>';
  }
})();
</script>

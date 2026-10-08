document.addEventListener('DOMContentLoaded', () => {
  const body = document.body;
  const themeBtn = document.getElementById('theme-toggle');
  const savedTheme = localStorage.getItem('otaku-theme') || 'dark';
  if (savedTheme === 'light') body.classList.add('theme-light');
  function updateThemeIcon(){ if(!themeBtn)return; themeBtn.innerHTML = body.classList.contains('theme-light') ? '<i class="fa-solid fa-sun"></i>' : '<i class="fa-regular fa-moon"></i>'; }
  updateThemeIcon();
  themeBtn?.addEventListener('click', () => { body.classList.toggle('theme-light'); localStorage.setItem('otaku-theme', body.classList.contains('theme-light')?'light':'dark'); updateThemeIcon(); });

  document.querySelectorAll('[data-bookmark]').forEach(btn => {
    const key = 'otaku-bookmarks';
    const id = btn.dataset.id || '';
    let saved = [];
    try { saved = JSON.parse(localStorage.getItem(key) || '[]'); } catch(e){}
    const refresh = () => { const active=saved.includes(id); btn.classList.toggle('active',active); btn.innerHTML = active ? '<i class="fa-solid fa-bookmark"></i> <span>Bookmarked</span>' : '<i class="fa-regular fa-bookmark"></i> <span>Bookmark</span>'; };
    refresh();
    btn.addEventListener('click',()=>{ saved=saved.includes(id)?saved.filter(x=>x!==id):[...saved,id]; localStorage.setItem(key,JSON.stringify(saved)); refresh(); });
  });

  // Home: if PHP rendered empty media because the server request failed, browser tries AniList then Jikan.
  const api='https://api.jikan.moe/v4';
  const ani='https://graphql.anilist.co';
  const esc=(x)=>String(x??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));
  const titleOf=(a)=>a?.title?.english||a?.title?.romaji||a?.title?.native||a?.title||'Untitled';
  const imgOf=(a)=>a?.coverImage?.extraLarge||a?.coverImage?.large||a?.images?.jpg?.large_image_url||a?.images?.jpg?.image_url||'';
  const idOf=(a)=>a?.idMal||a?.mal_id||a?.anilist_id||a?.id||0;
  const detailHref=(a,type='anime')=>{const params=new URLSearchParams({type}); if(a?.idMal||a?.mal_id) params.set('mal_id',String(a.idMal||a.mal_id)); if(a?.anilist_id||a?.id) params.set('anilist_id',String(a.anilist_id||a.id)); return `detail.php?${params.toString()}`;};
  const yearOf=(a)=>a?.seasonYear||a?.year||'';
  const card=(a,type='anime')=>`<a class="media-card" href="${detailHref(a,type)}"><div class="poster"><img src="${esc(imgOf(a))}" alt="${esc(titleOf(a))}" loading="lazy"><span class="score"><i class="fa-solid fa-star"></i> ${esc(a?.score ?? (a?.averageScore ? (a.averageScore/10).toFixed(1) : 'N/A'))}</span></div><div class="media-title">${esc(titleOf(a))}</div><div class="media-meta">${esc(a?.type||a?.format||type)} · ${esc(yearOf(a))}</div></a>`;
  async function json(url, options={}){ const ctrl=new AbortController(); const t=setTimeout(()=>ctrl.abort(),10000); try{const r=await fetch(url,{...options,signal:ctrl.signal}); if(!r.ok)throw new Error(`HTTP ${r.status}`); return await r.json();} finally{clearTimeout(t);} }
  async function aniReq(query,variables){const r=await json(ani,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({query,variables})});if(r.errors?.length)throw new Error(r.errors[0].message);return r.data;}
  const mediaQuery=`query($type:MediaType,$sort:[MediaSort],$perPage:Int){Page(perPage:$perPage){media(type:$type,isAdult:false,sort:$sort){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}}`;
  const browseQuery=`query($type:MediaType,$sort:[MediaSort],$perPage:Int){p1:Page(page:1,perPage:$perPage){media(type:$type,isAdult:false,sort:$sort){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}p2:Page(page:2,perPage:$perPage){media(type:$type,isAdult:false,sort:$sort){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}p3:Page(page:3,perPage:$perPage){media(type:$type,isAdult:false,sort:$sort){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}}`;
  async function getBrowse(type,sort='POPULARITY_DESC'){const d=await aniReq(browseQuery,{type,sort:[sort],perPage:25});return [...(d.p1?.media||[]),...(d.p2?.media||[]),...(d.p3?.media||[])].slice(0,70);}

  const search=document.querySelector('[data-search-page]');
  if(search){
    const q=search.dataset.q||'', type=search.dataset.type==='manga'?'manga':'anime', grid=document.getElementById('search-results-grid'), loading=document.getElementById('search-loading'), count=document.getElementById('search-result-count'), label=document.getElementById('catalog-label');
    const already=grid?.querySelectorAll('.media-card').length||0;
    if(grid && already===0){
      (async()=>{
        try{
          let data=[];
          if(q){
            const query=`query($type:MediaType,$search:String,$perPage:Int){Page(perPage:$perPage){media(type:$type,search:$search,isAdult:false,sort:SEARCH_MATCH){idMal id format title{romaji english native} coverImage{large extraLarge} averageScore seasonYear}}}`;
            const d=await aniReq(query,{type:type.toUpperCase(),search:q,perPage:25}); data=d.Page.media||[];
          } else {
            data=await getBrowse(type, new URLSearchParams(location.search).get('order_by')==='score'?'SCORE_DESC':'POPULARITY_DESC');
          }
          if(data.length){grid.innerHTML=data.map(a=>card(a,type)).join(''); count&&(count.textContent=data.length); label&&(label.textContent=data.length); loading?.remove(); return;}
        }catch(e){}
        try{
          const ep=type==='manga'?'manga':'anime'; const u=q?`${api}/${ep}?q=${encodeURIComponent(q)}&limit=25&sfw=true`:`${api}/top/${ep}?filter=bypopularity&limit=25&sfw=true`; const d=await json(u); if(d.data?.length){grid.innerHTML=d.data.map(a=>card(a,type)).join(''); count&&(count.textContent=d.data.length); label&&(label.textContent=d.data.length); loading?.remove();}
        }catch(e){ if(loading) loading.textContent='API sedang tidak tersedia. Coba refresh beberapa detik lagi.'; }
      })();
    }
  }
});

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
      <div class="col"><div class="card-eyebrow">DETAIL <?=e(strtoupper($typeLabel))?></div><h1 id="fb-title" class="display-6 fw-bold mb-2"></h1><div id="fb-meta" class="text-secondary mb-3"></div><div id="fb-genres" class="d-flex flex-wrap gap-2"></div></div>
    </div></div></section>
    <div class="row g-4 mb-4">
      <div class="col-lg-8"><div class="content-card h-100"><div class="card-eyebrow">SYNOPSIS</div><p id="fb-description" class="mb-0" style="white-space:pre-line"></p></div></div>
      <div class="col-lg-4"><div class="content-card h-100"><div class="card-eyebrow">INFORMATION</div><div id="fb-info"></div></div></div>
    </div>

    <!-- TRAILER -->
    <section class="content-card mb-4 d-none" id="fb-trailer-sec">
      <div class="card-eyebrow">PREVIEW</div>
      <h3><i class="fa-brands fa-youtube text-danger me-2"></i> Official Trailer</h3>
      <div class="trailer-wrapper"><iframe id="fb-trailer-frame" allowfullscreen loading="lazy"></iframe></div>
    </section>

    <!-- CHARACTERS & VOICE ACTORS -->
    <section class="content-card mb-4 d-none" id="fb-char-sec">
      <div class="card-eyebrow">CAST</div>
      <h3>Characters &amp; Voice Actors</h3>
      <div class="char-va-grid" id="fb-characters"></div>
    </section>

    <!-- STAFF & PRODUCTION -->
    <section class="content-card mb-4 d-none" id="fb-staff-sec">
      <div class="card-eyebrow">PRODUCTION</div>
      <h3>Staff &amp; Production</h3>
      <div class="detail-subtitle mb-2" id="fb-studios-title">Studios</div>
      <div class="d-flex flex-wrap gap-2 mb-3" id="fb-studios"></div>
      <div class="staff-grid" id="fb-staff"></div>
    </section>

    <!-- RECOMMENDATIONS -->
    <section class="content-card mb-4 d-none" id="fb-rec-sec">
      <div class="card-eyebrow">DISCOVER</div>
      <h3>Recommendations</h3>
      <div class="media-grid mini-grid" id="fb-recommendations"></div>
    </section>
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
  const esc=(x)=>String(x??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#39;','"':'&quot;'}[c]));

  let argDef='$type: MediaType';
  let mediaArg='type: $type';
  const variables={type};
  if(aniId>0){ argDef+=', $id: Int'; mediaArg+=', id: $id'; variables.id=aniId; }
  else if(malId>0){ argDef+=', $idMal: Int'; mediaArg+=', idMal: $idMal'; variables.idMal=malId; }

  const query=`query(${argDef}){Media(${mediaArg}){id idMal type format status title{romaji english native userPreferred} description(asHtml:false) season seasonYear episodes chapters volumes averageScore genres coverImage{large extraLarge} bannerImage trailer{id site} studios(isMain:true){nodes{name}} characters(sort:[ROLE,FAVOURITES_DESC],page:1,perPage:24){edges{role node{name{full} image{large}} voiceActors{name{full} image{large}}}} staff(sort:[RELEVANCE,FAVOURITES_DESC],page:1,perPage:16){edges{role node{name{full} image{large}}}} recommendations(sort:RATING_DESC,page:1,perPage:8){nodes{mediaRecommendation{id idMal type title{romaji english} coverImage{large} averageScore}}}}}`;

  try{
    const res=await fetch('https://graphql.anilist.co',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json'},body:JSON.stringify({query,variables})});
    const json=await res.json();
    const m=json?.data?.Media;
    if(!m) throw new Error(json?.errors?.[0]?.message||'AniList tidak mengembalikan media untuk ID tersebut.');

    const title=m.title?.english||m.title?.romaji||m.title?.native||'Untitled';
    document.getElementById('fb-title').textContent=title;
    document.getElementById('fb-cover').src=m.coverImage?.extraLarge||m.coverImage?.large||'';
    const cleanDesc=(m.description||'Belum ada synopsis.').replace(/<br\s*\/?>/gi,'\n');
    document.getElementById('fb-description').textContent=cleanDesc;
    document.getElementById('fb-meta').textContent=[m.format,m.status,m.seasonYear,m.averageScore?('Score '+(m.averageScore/10).toFixed(1)):''].filter(Boolean).join(' · ');
    document.getElementById('fb-genres').innerHTML=(m.genres||[]).map(g=>`<span class="chip">${esc(g)}</span>`).join('');

    const studios=(m.studios?.nodes||[]).map(x=>x.name).join(', ')||'-';
    const info=[['MAL ID',m.idMal||malId||'-'],['AniList ID',m.id||aniId||'-'],['Format',m.format||'-'],['Status',m.status||'-'],['Episodes',m.episodes||'-'],['Chapters',m.chapters||'-'],['Studio',studios]];
    document.getElementById('fb-info').innerHTML=info.map(x=>`<div class="d-flex justify-content-between border-bottom py-2"><span class="text-secondary">${x[0]}</span><strong>${x[1]}</strong></div>`).join('');
    if(m.bannerImage) document.getElementById('fb-hero').style.setProperty('--cover',`url("${m.bannerImage.replace(/"/g,'')}")`);

    // Trailer
    if(m.trailer?.id && (m.trailer.site||'').toLowerCase()==='youtube'){
      const tSec=document.getElementById('fb-trailer-sec');
      const tFrame=document.getElementById('fb-trailer-frame');
      tFrame.src=`https://www.youtube-nocookie.com/embed/${encodeURIComponent(m.trailer.id)}`;
      tSec.classList.remove('d-none');
    }

    // Characters & VAs
    const chars=m.characters?.edges||[];
    if(chars.length){
      const cSec=document.getElementById('fb-char-sec');
      const cGrid=document.getElementById('fb-characters');
      cGrid.innerHTML=chars.map(edge=>{
        const ch=edge.node||{};
        const va=(edge.voiceActors||[])[0];
        const vaHtml=va ? `<div class="va-side"><div class="va-info"><div class="va-name" title="${esc(va.name?.full)}">${esc(va.name?.full)}</div><div class="va-lang">Japanese</div></div><img class="va-avatar" src="${esc(va.image?.large||'')}" alt="${esc(va.name?.full)}" loading="lazy"></div>` : '';
        return `<div class="char-va-card"><div class="char-side"><img class="char-avatar" src="${esc(ch.image?.large||'')}" alt="${esc(ch.name?.full)}" loading="lazy"><div class="char-info"><div class="char-name" title="${esc(ch.name?.full)}">${esc(ch.name?.full)}</div><div class="char-role">${esc(edge.role||'Character')}</div></div></div>${vaHtml}</div>`;
      }).join('');
      cSec.classList.remove('d-none');
    }

    // Staff & Studios
    const staff=m.staff?.edges||[];
    const studioNodes=m.studios?.nodes||[];
    if(studioNodes.length || staff.length){
      const sSec=document.getElementById('fb-staff-sec');
      if(studioNodes.length){
        document.getElementById('fb-studios').innerHTML=studioNodes.map(s=>`<span class="chip"><i class="fa-solid fa-building me-1"></i> ${esc(s.name)}</span>`).join('');
      } else {
        document.getElementById('fb-studios-title').classList.add('d-none');
      }
      if(staff.length){
        document.getElementById('fb-staff').innerHTML=staff.map(edge=>{
          const p=edge.node||{};
          return `<div class="staff-card"><img class="staff-avatar" src="${esc(p.image?.large||'')}" alt="${esc(p.name?.full)}" loading="lazy"><div class="staff-info"><strong class="staff-name">${esc(p.name?.full)}</strong><span class="staff-role">${esc(edge.role||'Staff')}</span></div></div>`;
        }).join('');
      }
      sSec.classList.remove('d-none');
    }

    // Recommendations
    const recs=(m.recommendations?.nodes||[]).map(x=>x.mediaRecommendation).filter(Boolean);
    if(recs.length){
      const rSec=document.getElementById('fb-rec-sec');
      const rGrid=document.getElementById('fb-recommendations');
      const curType=(m.type||type).toLowerCase();
      rGrid.innerHTML=recs.map(r=>{
        const rTitle=r.title?.english||r.title?.romaji||'Untitled';
        const rImg=r.coverImage?.large||'';
        const rScore=r.averageScore ? (r.averageScore/10).toFixed(1) : null;
        const rScoreHtml=rScore ? `<span class="score"><i class="fa-solid fa-star"></i> ${rScore}</span>` : '';
        const rParams=new URLSearchParams({type:curType});
        if(r.idMal) rParams.set('mal_id', r.idMal);
        if(r.id) rParams.set('anilist_id', r.id);
        return `<a class="media-card" href="detail.php?${rParams.toString()}"><div class="poster"><img src="${esc(rImg)}" alt="${esc(rTitle)}" loading="lazy">${rScoreHtml}</div><div class="media-title">${esc(rTitle)}</div></a>`;
      }).join('');
      rSec.classList.remove('d-none');
    }

    status.classList.add('d-none'); content.classList.remove('d-none');
  }catch(e){
    status.classList.add('d-none'); error.classList.remove('d-none');
    error.innerHTML='<strong>Detail belum bisa diambil.</strong><br>'+esc(e.message||e)+'<br><small>MAL ID: '+malId+' · AniList ID: '+aniId+'</small>';
  }
})();
</script>

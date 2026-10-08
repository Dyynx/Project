<?php
declare(strict_types=1);
require_once __DIR__ . '/AniListService.php';

final class JikanService
{
    private AniListService $aniList;
    private string $baseUrl;
    private ?string $lastError = null;

    public function __construct(string $baseUrl = JIKAN_BASE_URL)
    {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->aniList = new AniListService();
    }

    public function lastError(): ?string { return $this->lastError; }
    public function aniListService(): AniListService { return $this->aniList; }

    public function popularAnime(int $limit = 12): array
    {
        return $this->cached('home_popular_' . min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?' . http_build_query(['limit'=>min(12,$limit),'sfw'=>'true']));
            $data = $j['data'] ?? [];
            if ($data) return $data;
            return $this->aniList->popularAnime($limit);
        });
    }

    public function topAnime(int $limit = 6): array
    {
        return $this->cached('v8_home_top_score_' . min(12, $limit), function () use ($limit) {
            $j = $this->request('/top/anime?' . http_build_query(['filter'=>'bypopularity','limit'=>min(12,$limit),'sfw'=>'true']));
            $data = $j['data'] ?? [];
            if ($data) {
                usort($data, fn($a,$b)=>(float)($b['score']??0) <=> (float)($a['score']??0));
                return array_slice($data,0,min(12,$limit));
            }
            return $this->aniList->topAnime($limit);
        });
    }

    public function popularManga(int $limit = 12): array
    {
        $j = $this->request('/top/manga?' . http_build_query(['filter'=>'bypopularity','limit'=>min(12,$limit)]));
        $data = $j['data'] ?? [];
        return $data ?: $this->aniList->popularManga($limit);
    }

    public function browse(string $type, array $filters = [], int $limit = 70): array
    {
        // For the empty-query Explore page, AniList's browse query is the reliable source and supports multi-filter browsing.
        $ani = $this->aniList->browse($type, $filters, $limit);
        if ($ani) return $ani;

        $endpoint = $type === 'manga' ? '/manga' : '/anime';
        $params = ['limit'=>25,'sfw'=>'true'];
        foreach (['type','status','rating','min_score','order_by','sort','genres','start_date','end_date'] as $key) {
            if (($filters[$key] ?? '') !== '' && $filters[$key] !== null) $params[$key] = $filters[$key];
        }
        $out=[];
        for($page=1;$page<=3 && count($out)<$limit;$page++){
            $params['page']=$page;
            $j=$this->request($endpoint.'?'.http_build_query($params));
            $chunk=$j['data']??[];
            if(!$chunk) break;
            $out=array_merge($out,$chunk);
        }
        return array_slice($out,0,$limit);
    }

    public function searchAnime(string $query, array $filters = []): array { return $this->search('/anime',$query,$filters,'ANIME'); }
    public function searchManga(string $query, array $filters = []): array { return $this->search('/manga',$query,$filters,'MANGA'); }

    public function anime(int $id): ?array
    {
        return $this->detailMerged('ANIME', $id);
    }

    public function manga(int $id): ?array
    {
        return $this->detailMerged('MANGA', $id);
    }

    /**
     * One resolver for every poster. Jikan/MAL supplies the broad MAL detail,
     * while AniList enriches the same title with tags, characters + VAs,
     * staff, studios, relations, external links, streaming and recommendations.
     * If either service is temporarily unavailable, the other remains usable.
     */
    private function detailMerged(string $type, int $malId): ?array
    {
        if ($malId < 1) return null;

        $kind = $type === 'MANGA' ? 'manga' : 'anime';
        $base = $this->request('/' . $kind . '/' . $malId . '/full')['data'] ?? null;
        if (!$base) {
            $base = $this->request('/' . $kind . '/' . $malId)['data'] ?? null;
        }
        if (!$base) return null;

        $base['mal_id'] = (int)($base['mal_id'] ?? $malId);
        $base['_source'] = 'Jikan / MyAnimeList';

        // Keep every detail connection independent. A timeout on one endpoint
        // must never erase the main media detail or the other sections.
        $castPayload = $this->request('/' . $kind . '/' . $malId . '/characters')['data'] ?? [];
        if (is_array($castPayload) && array_key_exists('characters', $castPayload)) {
            $base['characters'] = $castPayload['characters'] ?? [];
            $base['staff'] = $castPayload['staff'] ?? [];
        } elseif ($castPayload) {
            $base['characters'] = $castPayload;
        }

        $staffResponse = $this->request('/' . $kind . '/' . $malId . '/staff')['data'] ?? [];
        if ($staffResponse) $base['staff'] = $staffResponse;

        $recommendations = $this->request('/' . $kind . '/' . $malId . '/recommendations')['data'] ?? [];
        if ($recommendations) $base['recommendations'] = $recommendations;

        return $base;
    }

    public function detailByAniListId(string $type,int $id): ?array
    {
        return $this->aniList->detailByAniListId($type,$id);
    }

    public function recommendations(int $id, string $type = 'anime'): array
    {
        if ($id < 1) return [];
        $type = $type === 'manga' ? 'manga' : 'anime';
        $aniType = strtoupper($type);
        $detail = $this->aniList->detailByMalId($aniType, $id);
        $ani = [];
        foreach (($detail['recommendations']['nodes'] ?? []) as $node) {
            $rec = $node['mediaRecommendation'] ?? null;
            if ($rec) $ani[] = $rec;
        }
        if ($ani) return $ani;

        $j = $this->request('/' . $type . '/' . $id . '/recommendations');
        return array_values(array_filter(array_map(function($item) {
            return $item['entry'] ?? $item;
        }, $j['data'] ?? [])));
    }

    private function search(string $endpoint,string $query,array $filters,string $aniType): array
    {
        $query = trim($query);
        if ($query === '') return [];
        $params=['q'=>$query,'limit'=>25,'sfw'=>'true'];
        foreach(['type','status','rating','min_score','order_by','sort','genres','start_date','end_date'] as $key){
            if(($filters[$key]??'')!=='' && ($filters[$key]??null)!==null) $params[$key]=$filters[$key];
        }
        $json=$this->request($endpoint.'?'.http_build_query($params));
        $data=$json['data']??[];
        if($data) return $this->rank($data,$query);
        $fallback=$this->aniList->search($aniType,$query,25);
        return $this->rank($fallback,$query);
    }

    private function rank(array $items,string $query): array
    {
        $q=$this->normalize($query); $tokens=array_values(array_filter(explode(' ',$q)));
        foreach($items as &$item){
            $titles=[];
            foreach($item['titles']??[] as $t) if(!empty($t['title'])) $titles[]=$t['title'];
            foreach(['title','title_english','title_japanese'] as $key) if(!empty($item[$key])) $titles[]=$item[$key];
            $best=0;
            foreach(array_unique(array_map(fn($v)=>$this->normalize((string)$v),$titles)) as $title){
                if($title==='') continue;
                if($title===$q)$score=1000;
                elseif(str_starts_with($title,$q))$score=850;
                elseif(str_contains($title,$q))$score=700;
                else{ $matched=0; foreach($tokens as $token) if(str_contains($title,$token))$matched++; $score=$tokens?($matched/count($tokens))*500:0; }
                $best=max($best,$score);
            }
            $item['_relevance']=$best;
        }
        unset($item);
        usort($items,fn($a,$b)=>($b['_relevance']??0)<=>($a['_relevance']??0));
        return array_values(array_filter(array_slice($items,0,25),fn($a)=>(($a['_relevance']??0)>=200)));
    }

    private function normalize(string $s): string { return trim(preg_replace('/\s+/',' ',mb_strtolower($s))); }

    private function request(string $path): array
    {
        $this->lastError = null;
        $cacheKey = sha1($path);
        $file = CACHE_DIR . '/' . $cacheKey . '.json';
        if (is_file($file) && (time() - filemtime($file)) < 300) {
            $data = json_decode((string)file_get_contents($file), true);
            return is_array($data) ? $data : [];
        }

        $url = $this->baseUrl . $path;
        $attempts = [];
        if (defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE)) {
            $attempts[] = ['verify'=>true, 'cainfo'=>API_CA_BUNDLE];
        }
        // Let PHP/cURL use its configured trust store as a second attempt.
        $attempts[] = ['verify'=>true, 'cainfo'=>null];

        foreach ($attempts as $attempt) {
            $ch = curl_init($url);
            $opts = [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_TIMEOUT => 12,
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'User-Agent: OtakuTrack/4.0'],
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            ];
            if ($attempt['cainfo']) $opts[CURLOPT_CAINFO] = $attempt['cainfo'];
            curl_setopt_array($ch, $opts);
            $body = curl_exec($ch);
            $error = curl_error($ch);
            $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($body !== false && $status >= 200 && $status < 300) {
                $data = json_decode($body, true);
                if (is_array($data)) {
                    @file_put_contents($file, $body);
                    return $data;
                }
                $this->lastError = 'Jikan response bukan JSON valid.';
            } else {
                $attempts[] = ['error'=>$error, 'status'=>$status];
                $this->lastError = 'Jikan HTTP ' . $status . ($error ? ' — ' . $error : '');
            }
        }

        return [];
    }

    private function cached(string $key, callable $callback): array
    {
        $file=CACHE_DIR.'/'.$key.'.json';
        if(is_file($file) && (time()-filemtime($file))<300){ $data=json_decode((string)file_get_contents($file),true); return is_array($data)?$data:[]; }
        $data=$callback(); if($data) @file_put_contents($file,json_encode($data)); return $data;
    }
}

<?php
declare(strict_types=1);

final class AniListService
{
    private string $url = 'https://graphql.anilist.co';
    private ?string $lastError = null;

    public function lastError(): ?string { return $this->lastError; }

    public function popularAnime(int $limit = 12): array { return $this->list('ANIME', 'POPULARITY_DESC', $limit); }
    public function topAnime(int $limit = 6): array { return $this->list('ANIME', 'SCORE_DESC', $limit); }
    public function popularManga(int $limit = 12): array { return $this->list('MANGA', 'POPULARITY_DESC', $limit); }

    public function browse(string $type, array $filters = [], int $limit = 70): array
    {
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $limit = max(1, min(70, $limit));
        $perPage = 25;
        $pages = (int)ceil($limit / $perPage);

        $aliases = [];
        $variables = [
            'type' => $type,
            'perPage' => $perPage,
            'isAdult' => false,
        ];
        $fieldArgs = '$type: MediaType, $perPage: Int, $isAdult: Boolean, $format: [MediaFormat], $status: MediaStatus, $season: MediaSeason, $seasonYear: Int, $genre: [String], $sort: [MediaSort]';
        $vars = [
            'format' => 'format', 'status' => 'status', 'season' => 'season', 'seasonYear' => 'seasonYear',
            'genre' => 'genre', 'sort' => 'sort'
        ];
        $variables['sort'] = [$this->mapSort((string)($filters['sort'] ?? 'POPULARITY_DESC'))];
        if (($filters['format'] ?? '') !== '') $variables['format'] = [strtoupper((string)$filters['format'])];
        if (($filters['status'] ?? '') !== '') $variables['status'] = strtoupper((string)$filters['status']);
        if (($filters['season'] ?? '') !== '') $variables['season'] = strtoupper((string)$filters['season']);
        if (($filters['year'] ?? '') !== '') $variables['seasonYear'] = (int)$filters['year'];
        if (($filters['genre'] ?? '') !== '') $variables['genre'] = [(string)$filters['genre']];

        for ($page = 1; $page <= $pages; $page++) {
            $alias = 'p' . $page;
            $aliases[] = $alias . ': Page(page: ' . $page . ', perPage: $perPage) {
                pageInfo { total currentPage lastPage hasNextPage }
                media(type: $type, isAdult: $isAdult, format_in: $format, status: $status, season: $season, seasonYear: $seasonYear, genre_in: $genre, sort: $sort) {
                    id idMal type format status title { romaji english native }
                    coverImage { large extraLarge color }
                    bannerImage description(asHtml:false)
                    averageScore popularity favourites seasonYear episodes chapters volumes genres
                }
            }';
        }

        $query = 'query(' . $fieldArgs . ') { ' . implode(' ', $aliases) . ' }';
        $json = $this->request($query, $variables);
        if (!$json) return [];

        $items = [];
        for ($page = 1; $page <= $pages; $page++) {
            foreach (($json['data']['p' . $page]['media'] ?? []) as $m) $items[] = $this->normalizeMedia($m, $type);
        }
        return array_slice($items, 0, $limit);
    }

    public function search(string $type, string $query, int $limit = 25): array
    {
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $query = trim($query);
        if ($query === '') return [];
        $q = <<<'GQL'
query ($type: MediaType, $search: String, $perPage: Int) {
  Page(perPage: $perPage) {
    media(type: $type, search: $search, isAdult: false, sort: SEARCH_MATCH) {
      id idMal type format status title { romaji english native }
      coverImage { large extraLarge color }
      bannerImage description(asHtml:false)
      averageScore popularity seasonYear episodes chapters volumes genres
    }
  }
}
GQL;
        $json = $this->request($q, ['type'=>$type,'search'=>$query,'perPage'=>min(25,$limit)]);
        return $this->normalizeList($json['data']['Page']['media'] ?? [], $type);
    }

    public function detailByMalId(string $type, int $malId): ?array
    {
        if ($malId < 1) return null;
        return $this->detail($type, ['idMal' => $malId]);
    }

    public function detailByAniListId(string $type, int $id): ?array
    {
        if ($id < 1) return null;
        return $this->detail($type, ['id' => $id]);
    }

    private function detail(string $type, array $where): ?array
    {
        $type = strtoupper($type) === 'MANGA' ? 'MANGA' : 'ANIME';
        $q = <<<'GQL'
query ($type: MediaType, $id: Int, $idMal: Int) {
  Media(type: $type, id: $id, idMal: $idMal) {
    id idMal type format status
    title { romaji english native userPreferred }
    synonyms
    description(asHtml:false)
    startDate { year month day }
    endDate { year month day }
    season seasonYear
    episodes duration chapters volumes
    countryOfOrigin source
    averageScore meanScore popularity trending favourites
    genres tags { id name rank }
    coverImage { large extraLarge color }
    bannerImage
    siteUrl
    studios(isMain:true) { nodes { id name isAnimationStudio siteUrl } }
    externalLinks { id url site siteId type language icon }
    streamingEpisodes { title thumbnail url site }
    characters(sort:[ROLE, FAVOURITES_DESC], page:1, perPage:12) {
      edges {
        role
        voiceActors { id name { full native } image { large medium } siteUrl }
        node { id name { full native } image { large medium } siteUrl }
      }
    }
    staff(sort:[RELEVANCE, FAVOURITES_DESC], page:1, perPage:12) {
      edges { role node { id name { full native } image { large } siteUrl } }
    }
    relations {
      edges { relationType node { id idMal type format title { romaji english native } coverImage { medium large } averageScore } }
    }
    recommendations(sort:RATING_DESC, page:1, perPage:8) {
      nodes { id rating mediaRecommendation { id idMal type title { romaji english native } coverImage { medium large } averageScore } }
    }
  }
}
GQL;
        $vars = ['type'=>$type, 'id'=>null, 'idMal'=>null];
        if (isset($where['id'])) $vars['id'] = (int)$where['id'];
        if (isset($where['idMal'])) $vars['idMal'] = (int)$where['idMal'];
        $json = $this->request($q, $vars);
        $m = $json['data']['Media'] ?? null;
        return $m ?: null;
    }

    private function list(string $type, string $sort, int $limit): array
    {
        return $this->browse($type, ['sort'=>$sort], $limit);
    }

    private function normalizeList(array $items, string $type): array
    {
        $out = [];
        foreach ($items as $m) $out[] = $this->normalizeMedia($m, $type);
        return $out;
    }

    private function normalizeMedia(array $m, string $type): array
    {
        $title = $m['title']['english'] ?: ($m['title']['romaji'] ?: ($m['title']['native'] ?? 'Untitled'));
        return [
            'mal_id' => (int)($m['idMal'] ?? 0),
            'anilist_id' => (int)($m['id'] ?? 0),
            'title' => $title,
            'title_english' => $m['title']['english'] ?? null,
            'title_japanese' => $m['title']['native'] ?? null,
            'type' => $m['format'] ?? $type,
            'format' => $m['format'] ?? null,
            'score' => isset($m['averageScore']) ? ((float)$m['averageScore'] / 10) : null,
            'averageScore' => $m['averageScore'] ?? null,
            'popularity' => $m['popularity'] ?? null,
            'year' => $m['seasonYear'] ?? null,
            'images' => ['jpg' => [
                'image_url' => $m['coverImage']['large'] ?? null,
                'large_image_url' => $m['coverImage']['extraLarge'] ?? ($m['coverImage']['large'] ?? null),
            ]],
            'coverImage' => $m['coverImage'] ?? [],
            'bannerImage' => $m['bannerImage'] ?? null,
            'description' => $m['description'] ?? null,
            'status' => $m['status'] ?? null,
            'genres' => array_map(fn($g)=>['name'=>$g], $m['genres'] ?? []),
            'episodes' => $m['episodes'] ?? null,
            'chapters' => $m['chapters'] ?? null,
            'volumes' => $m['volumes'] ?? null,
            'siteUrl' => $m['siteUrl'] ?? null,
            '_source' => 'AniList',
        ];
    }

    private function mapSort(string $sort): string
    {
        $allowed = ['POPULARITY_DESC','SCORE_DESC','TRENDING_DESC','FAVOURITES_DESC','START_DATE_DESC','UPDATED_AT_DESC'];
        return in_array($sort, $allowed, true) ? $sort : 'POPULARITY_DESC';
    }

    private function request(string $query, array $variables): array
    {
        $this->lastError = null;
        $cacheFile = CACHE_DIR . '/anilist_' . sha1($query . '|' . json_encode($variables)) . '.json';
        if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < 300) {
            $cached = json_decode((string)file_get_contents($cacheFile), true);
            if (is_array($cached)) return $cached;
        }
        $ch = curl_init($this->url);
        $opts = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode(['query'=>$query,'variables'=>$variables]),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json','Accept: application/json','User-Agent: OtakuTrack/3.0'],
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];
        if (defined('API_CA_BUNDLE') && is_file(API_CA_BUNDLE)) $opts[CURLOPT_CAINFO] = API_CA_BUNDLE;
        curl_setopt_array($ch, $opts);
        $body = curl_exec($ch);
        $error = curl_error($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false) { $this->lastError = 'AniList cURL: '.($error ?: 'request gagal'); return []; }
        if ($status < 200 || $status >= 300) { $this->lastError = 'AniList HTTP '.$status; return []; }
        $data = json_decode($body, true);
        if (!is_array($data)) { $this->lastError = 'Respons AniList bukan JSON valid.'; return []; }
        if (!empty($data['errors'])) { $this->lastError = 'AniList GraphQL: '.($data['errors'][0]['message'] ?? 'error'); return []; }
        @file_put_contents($cacheFile, json_encode($data));
        return $data;
    }
}

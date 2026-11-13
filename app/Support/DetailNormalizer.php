<?php

namespace App\Support;

/**
 * Menyamakan seluruh section detail anime/manga dari Jikan (MAL) maupun AniList
 * menjadi satu bentuk yang stabil, sehingga view tidak perlu tahu datanya
 * berasal dari API mana. Dibawa verbatim dari detail.php repo asli.
 */
final class DetailNormalizer
{
    public static function detailTitle(array $item): string
    {
        $title = $item['title'] ?? '';
        if (is_array($title)) {
            return (string) ($title['english'] ?? $title['romaji'] ?? $title['native'] ?? $title['userPreferred'] ?? 'Untitled');
        }

        return (string) ($title ?: $item['name'] ?? 'Untitled');
    }

    public static function detailImage(array $item): ?string
    {
        return $item['coverImage']['extraLarge']
            ?? $item['coverImage']['large']
            ?? $item['coverImage']['medium']
            ?? $item['images']['jpg']['large_image_url']
            ?? $item['images']['jpg']['image_url']
            ?? null;
    }

    /**
     * Nama orang/karakter bisa berupa string (Jikan) atau objek nama (AniList).
     */
    public static function personName(mixed $name): string
    {
        if (is_array($name)) {
            return (string) ($name['full'] ?? $name['userPreferred'] ?? $name['native'] ?? $name['romaji'] ?? $name['english'] ?? '');
        }

        return trim((string) $name);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function characters(array $media): array
    {
        $rows = $media['characters']['edges'] ?? $media['characters'] ?? [];
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $character = $row['node'] ?? $row['character'] ?? null;
            if (! is_array($character)) {
                continue;
            }
            $voices = $row['voiceActors'] ?? $row['voice_actors'] ?? [];
            if (! is_array($voices)) {
                $voices = [];
            }
            $voice = $voices[0] ?? null;
            if (is_array($voice) && isset($voice['person']) && is_array($voice['person'])) {
                $voice = $voice['person'];
            }
            $out[] = [
                'name' => self::personName($character['name'] ?? 'Character'),
                'role' => (string) ($row['role'] ?? 'Character'),
                'image' => $character['image']['large'] ?? $character['image']['medium'] ?? $character['images']['jpg']['image_url'] ?? null,
                'voice' => is_array($voice) ? self::personName($voice['name'] ?? $voice['person']['name'] ?? '') : '',
                'voice_image' => is_array($voice) ? ($voice['image']['large'] ?? $voice['image']['medium'] ?? $voice['images']['jpg']['image_url'] ?? null) : null,
                'voice_language' => is_array($voice) ? (string) ($voice['language'] ?? '') : '',
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function staff(array $media): array
    {
        $rows = $media['staff']['edges'] ?? $media['staff'] ?? [];
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $person = $row['node'] ?? $row['staff'] ?? $row['person'] ?? null;
            if (! $person && isset($row['person'])) {
                $person = $row['person'];
            }
            if (! is_array($person)) {
                continue;
            }
            $positions = $row['positions'] ?? $row['position'] ?? [];
            if (! is_array($positions)) {
                $positions = [$positions];
            }
            $role = (string) ($row['role'] ?? (implode(', ', array_filter(array_map('strval', $positions))) ?: 'Staff'));
            $out[] = [
                'name' => self::personName($person['name'] ?? $person),
                'role' => $role,
                'image' => $person['image']['large'] ?? $person['image']['medium'] ?? $person['images']['jpg']['image_url'] ?? null,
            ];
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function relations(array $media, string $type): array
    {
        $rows = $media['relations']['edges'] ?? $media['relations'] ?? [];
        $out = [];
        if (! is_array($rows)) {
            return [];
        }
        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $node = $row['node'] ?? $row['entry'] ?? null;
            $relation = (string) ($row['relationType'] ?? $row['relation'] ?? 'Related');
            if ($node && is_array($node)) {
                $out[] = [
                    'relation' => $relation,
                    'type' => strtoupper((string) ($node['type'] ?? $type)),
                    'mal_id' => (int) ($node['idMal'] ?? $node['mal_id'] ?? 0),
                    'anilist_id' => (int) ($node['id'] ?? 0),
                    'title' => self::detailTitle($node),
                    'image' => self::detailImage($node),
                ];

                continue;
            }
            $entries = $row['entry'] ?? $row['entries'] ?? [];
            if (is_array($entries)) {
                foreach ($entries as $entry) {
                    if (! is_array($entry)) {
                        continue;
                    }
                    $out[] = [
                        'relation' => $relation,
                        'type' => strtoupper((string) ($entry['type'] ?? $type)),
                        'mal_id' => (int) ($entry['mal_id'] ?? $entry['id'] ?? 0),
                        'anilist_id' => (int) ($entry['anilist_id'] ?? 0),
                        'title' => self::detailTitle($entry),
                        'image' => self::detailImage($entry),
                    ];
                }
            }
        }

        return $out;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public static function recommendations(array $media, string $type): array
    {
        $rows = $media['recommendations']['nodes'] ?? $media['recommendations'] ?? [];
        if (! is_array($rows)) {
            return [];
        }
        $out = [];
        foreach ($rows as $row) {
            $entry = $row['mediaRecommendation'] ?? $row['entry'] ?? $row;
            if (! is_array($entry)) {
                continue;
            }
            $score = $entry['averageScore'] ?? $entry['score'] ?? null;
            $out[] = [
                'mal_id' => (int) ($entry['idMal'] ?? $entry['mal_id'] ?? 0),
                'anilist_id' => (int) ($entry['id'] ?? $entry['anilist_id'] ?? 0),
                'type' => strtoupper((string) ($entry['type'] ?? $type)),
                'title' => self::detailTitle($entry),
                'image' => self::detailImage($entry),
                'score' => $score !== null ? (float) $score / 10 : null,
            ];
        }

        return $out;
    }

    public static function trailer(array $media): ?array
    {
        $t = $media['trailer'] ?? null;
        if (! is_array($t)) {
            return null;
        }

        $id = (string) ($t['id'] ?? $t['youtube_id'] ?? '');
        $site = strtolower((string) ($t['site'] ?? 'youtube'));
        $embedUrl = (string) ($t['embed_url'] ?? '');

        if ($id !== '' && ($site === 'youtube' || $embedUrl === '')) {
            $embedUrl = 'https://www.youtube-nocookie.com/embed/'.rawurlencode($id);
        } elseif ($embedUrl === '' && ! empty($t['url'])) {
            if (preg_match('/(?:v=|youtu\.be\/|embed\/)([A-Za-z0-9_-]{11})/', (string) $t['url'], $m)) {
                $embedUrl = 'https://www.youtube-nocookie.com/embed/'.$m[1];
            } else {
                $embedUrl = (string) $t['url'];
            }
        }

        if ($embedUrl === '') {
            return null;
        }

        return [
            'id' => $id,
            'site' => $site,
            'embed_url' => $embedUrl,
            'thumbnail' => $t['thumbnail'] ?? ($t['images']['maximum_image_url'] ?? ($t['images']['large_image_url'] ?? null)),
        ];
    }
}

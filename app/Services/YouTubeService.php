<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Exception;

class YouTubeService
{
    private string $apiKey;
    private string $baseUrl = 'https://www.googleapis.com/youtube/v3';

    public function __construct()
    {
        $this->apiKey = config('services.youtube.api_key');
    }

    public function searchPlaylists(string $query): array
    {
        $items = $this->search($query);

        if (empty($items)) return [];

        $details = $this->getPlaylistsDetails($items);

        return array_map(function ($item) use ($details) {
            $pid = $item['id']['playlistId'];
            return [
                'playlist_id'  => $pid,
                'title'        => $item['snippet']['title'],
                'description'  => $item['snippet']['description'],
                'thumbnail'    => $item['snippet']['thumbnails']['medium']['url'] ?? null,
                'channel_name' => $item['snippet']['channelTitle'],
                'video_count'  => $details[$pid] ?? 0,
            ];
        }, $items);
    }

    private function search(string $query): array
    {
        $response = Http::get("{$this->baseUrl}/search", [
            'part'       => 'snippet',
            'q'          => $query,
            'type'       => 'playlist',
            'maxResults' => 2,
            'key'        => $this->apiKey,
        ]);

        if ($response->status() === 403) {
            throw new Exception('YouTube API key غير صالح أو تجاوزت الحصة اليومية.');
        }

        if ($response->status() === 400) {
            throw new Exception('طلب YouTube API غير صحيح، تحقق من الإعدادات.');
        }

        if ($response->failed()) {
            throw new Exception('فشل الاتصال بـ YouTube API، حاول مرة أخرى.');
        }

        return $response->json('items', []);
    }

    private function getPlaylistsDetails(array $items): array
    {
        $playlistIds = array_column(
            array_map(fn($i) => ['id' => $i['id']['playlistId']], $items),
            'id'
        );

        $response = Http::get("{$this->baseUrl}/playlists", [
            'part' => 'contentDetails,snippet',
            'id'   => implode(',', $playlistIds),
            'key'  => $this->apiKey,
        ]);

        if ($response->failed()) {
            throw new Exception('فشل جلب تفاصيل الـ Playlists من YouTube.');
        }

        $details = [];
        foreach ($response->json('items', []) as $item) {
            $details[$item['id']] = $item['contentDetails']['itemCount'] ?? 0;
        }

        return $details;
    }
}

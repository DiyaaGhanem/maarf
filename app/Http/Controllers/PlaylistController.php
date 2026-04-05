<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCategoriesRequest;
use App\Models\Playlist;
use App\Services\GeminiService;
use App\Services\YouTubeService;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{

    // protected $gemini;
    // protected $youtube;

    // public function __construct(GeminiService $gemini, YouTubeService $youtube)
    // {
    //     $this->gemini = $gemini;
    //     $this->youtube = $youtube;
    // }

    public function __construct(private GeminiService $gemini, private YouTubeService $youtube) {}


    public function index(Request $request)
    {
        $categories = Playlist::selectRaw('category, count(*) as count')->groupBy('category')->get();

        $totalCount = Playlist::count();
        $activeCategory = $request->query('category');

        $query = Playlist::query();

        if ($activeCategory) {
            $query->where('category', $activeCategory);
        }

        $playlists = $query->latest()->paginate(8);

        return view('playlists.index', compact('playlists', 'categories', 'activeCategory', 'totalCount'));
    }

    public function fetch(StoreCategoriesRequest $request)
    {
        $categories = array_filter(
            array_map('trim', explode("\n", $request->categories))
        );

        // $allTitles = [];

        try {
            foreach ($categories as $category) {
                $titles = $this->gemini->generateCourseTitles($category);
                // $allTitles[$category] = $titles;

                foreach ($titles as $title) {
                    $playlists = $this->youtube->searchPlaylists($title);

                    foreach ($playlists as $playlist) {
                        Playlist::updateOrCreate(
                            ['playlist_id' => $playlist['playlist_id']],
                            [
                                'title'        => $playlist['title'],
                                'description'  => $playlist['description'],
                                'thumbnail'    => $playlist['thumbnail'],
                                'channel_name' => $playlist['channel_name'],
                                'category'     => $category,
                                'video_count'  => $playlist['video_count'],
                            ]
                        );
                    }
                    sleep(1);
                }
                sleep(3);
            }
        } catch (\Exception $e) {
            session()->flash('message', $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }

        // dd($allTitles);

        session()->flash('message', "تم جمع الكورسات بنجاح!");
        return response()->json([
            'success' => true,
            'message' => 'تم جمع الكورسات بنجاح!',
        ]);
    }
}

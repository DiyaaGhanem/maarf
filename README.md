# 🎬 YouTube Course Scraper

A Laravel-based web application that discovers educational YouTube playlists (courses) using AI-generated search queries and displays them in a clean, responsive Arabic UI.

---

## 📌 Project Overview

This application allows users to input content categories, then automatically:

1. Uses **Google Gemini 2.5 Flash AI** to generate relevant YouTube search queries per category
2. Searches **YouTube Data API v3** for educational playlists (2 per query)
3. Stores results in a MySQL database with full deduplication
4. Displays results in a filterable, paginated Arabic RTL UI

---

## 🛠 Tech Stack

| Layer      | Technology                        |
|------------|-----------------------------------|
| Backend    | Laravel 11, PHP 8.2               |
| Frontend   | Bootstrap 5 RTL, Custom CSS, jQuery AJAX |
| AI         | Google Gemini 2.5 Flash API       |
| Data       | YouTube Data API v3               |
| Database   | MySQL                             |

---

## 📁 Project Structure & What Each File Does

```
app/
├── Http/
│   ├── Controllers/
│   │   └── PlaylistController.php        # Main controller — handles listing & fetching playlists
│   └── Requests/
│       └── StoreCategoriesRequest.php    # Form Request — validates category textarea input with Arabic messages
├── Models/
│   └── Playlist.php                      # Eloquent model with fillable fields
└── Services/
    ├── GeminiService.php                 # Calls Gemini API to generate 5 search queries per category
    └── YouTubeService.php                # Searches YouTube & fetches playlist details (2 separate API calls)

database/
└── migrations/
    └── xxxx_create_playlists_table.php   # Single migration — one table is all this task needs

resources/
└── views/
    └── playlists/
        └── index.blade.php               # Main Blade view — hero section, input form, results grid, pagination

public/
└── css/
    └── app.css                           # Custom RTL styles — navbar, hero, cards, tabs, pagination

routes/
└── web.php                               # Two routes: GET / (index) and POST /fetch (trigger scraping)

config/
└── services.php                          # Holds YouTube API key config pulled from .env
```

---

## 🗄 Database Design

> The task scope is simple — only **one table** is needed. No relational complexity required.

### `playlists` table

| Column       | Type            | Notes                                              |
|--------------|-----------------|----------------------------------------------------|
| id           | bigint (PK)     | Auto-increment primary key                         |
| playlist_id  | string (unique) | YouTube playlist ID — **used for deduplication**   |
| title        | string          | Playlist title                                     |
| description  | text (nullable) | Playlist description                               |
| thumbnail    | string (nullable)| Thumbnail image URL                               |
| channel_name | string (nullable)| YouTube channel name                              |
| category     | string          | The user-input category this playlist belongs to  |
| video_count  | integer         | Number of videos in the playlist (default: 0)     |
| created_at   | timestamp       | —                                                  |
| updated_at   | timestamp       | —                                                  |

**Deduplication** is handled via `Playlist::updateOrCreate()` keyed on `playlist_id`.  
If the same playlist appears in multiple search results, it will be updated — never duplicated.

---

## ⚙️ Setup Instructions

### 1. Clone the repository

```bash
git clone https://github.com/DiyaaGhanem/maarf.git
cd maarf
```

### 2. Install PHP dependencies

```bash
composer install
```

### 3. Environment setup

```bash
cp .env.example .env
php artisan key:generate
```

Open `.env` and update the following:

```env
APP_NAME="YouTube Course Scraper"
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=maarf          # change to your database name
DB_USERNAME=root
DB_PASSWORD=               # change if needed

GEMINI_API_KEY=            # change if needed
YOUTUBE_API_KEY=           # change if needed
```

### 4. Run database migrations

```bash
php artisan migrate
```

### 5. Run the project

```bash
php artisan serve
```

Visit: [http://localhost:8000](http://localhost:8000)

---

## 🔑 API Keys Configuration

### Google Gemini API (used for AI-generated search queries)

1. Go to [aistudio.google.com/apikey](https://aistudio.google.com/apikey)
2. Click **Create API Key**
3. Copy the key and add it to `.env`:

```env
GEMINI_API_KEY=AIza...
```

> ⚠️ The free tier quota is **per Google Cloud project**, not per API key.  
> If you hit rate limits, create a **new Google Cloud project** and generate a fresh key from there.

---

### YouTube Data API v3

1. Go to [console.cloud.google.com](https://console.cloud.google.com)
2. Create a new project
3. Go to **APIs & Services → Library**
4. Search for **YouTube Data API v3** and click **Enable**
5. Go to **APIs & Services → Credentials → Create Credentials → API Key**
6. Copy the key and add it to `.env`:

```env
YOUTUBE_API_KEY=AIza...
```

Then add this to `config/services.php`:

```php
'youtube' => [
    'api_key' => env('YOUTUBE_API_KEY'),
],
```

---

## 🚀 How It Works — Full Flow

```
User inputs categories
(e.g. "Marketing", "Programming", "Graphic Design")
        │
        ▼
GeminiService::generateCourseTitles($category)
→ Calls Gemini 2.5 Flash API
→ Returns 5 specific YouTube search queries per category
        │
        ▼
YouTubeService::searchPlaylists($query)
  ├── search()               → GET /search   — finds 2 playlists per query
  └── getPlaylistsDetails()  → GET /playlists — fetches video counts for those playlists
        │
        ▼
Playlist::updateOrCreate(['playlist_id' => ...], [...])
→ Saves to DB, skips duplicates automatically
        │
        ▼
Results displayed with:
  - Category filter tabs (with count per category)
  - Paginated card grid (8 per page)
  - Video count badge per card
  - Direct link to YouTube playlist
```

---

## ✅ Key Technical Decisions

| Decision | Reason |
|----------|--------|
| **One table only** | The task is self-contained — no relational data needed |
| **Gemini instead of OpenAI** | Free tier available, no billing required for assessment |
| **Service layer** | `GeminiService` and `YouTubeService` keep the controller clean (Single Responsibility) |
| **Form Request** | `StoreCategoriesRequest` handles validation with Arabic error messages |
| **AJAX submission** | jQuery AJAX for better UX — no full page reload, inline error display |
| **`updateOrCreate`** | Clean deduplication at DB level using YouTube's unique `playlist_id` |
| **Two YouTube API calls** | `/search` doesn't return video count — a second call to `/playlists` is needed |
| **Exception-based errors** | Services throw exceptions with clear Arabic messages instead of returning empty arrays silently |

---

## ⏱ Time Breakdown

| Task | Time |
|------|------|
| Reading & understanding task requirements | 20 min |
| Laravel project setup & package installation | 15 min |
| API keys creation & configuration (Gemini + YouTube) | 15 min |
| Database migration design | 10 min |
| Building `GeminiService` — Gemini API integration | 20 min |
| Building `YouTubeService` — search + details (2 API calls) | 25 min |
| Building `PlaylistController` & `StoreCategoriesRequest` | 20 min |
| Debugging API integrations (quota issues, model names, token limits) | 25 min |
| Building UI — Blade + Bootstrap 5 RTL + Custom CSS | 40 min |
| AJAX form submission + jQuery error handling | 15 min |
| Final polish, cleanup & README | 15 min |
| **Total** | **~3.5 hours** |

---

## 📦 Dependencies

```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^11.0",
        "guzzlehttp/guzzle": "^7.0"
    }
}
```

Frontend CDNs (no npm required):
- Bootstrap 5.3 RTL — `cdn.jsdelivr.net`
- jQuery 3.7.1 — `code.jquery.com`
- Cairo Font — `fonts.googleapis.com`

---

## 🔗 Routes

| Method | URI      | Controller Method | Description              |
|--------|----------|-------------------|--------------------------|
| GET    | `/`      | `index`           | Display playlists + form |
| POST   | `/fetch` | `fetch`           | Trigger scraping process |
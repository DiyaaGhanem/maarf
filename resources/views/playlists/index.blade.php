<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YouTube Course Scraper</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

    {{-- Navbar --}}
    <nav class="app-navbar">
        <div class="navbar-brand-area">
            <span class="yt-icon">▶</span>
            <span class="brand-name">YouTube Course Scraper</span>
            <span class="navbar-divider">|</span>
            <span class="navbar-subtitle">أداة جمع الدورات التعليمية</span>
        </div>
    </nav>

    {{-- Hero --}}
    <div class="hero">
        <div class="container">
            <div class="hero-title-area">
                <h1>جمع الدورات التعليمية من يوتيوب</h1>
                <p>أدخل التصنيفات واضغط ابدأ – النظام سيجمع الدورات تلقائياً باستخدام الذكاء الاصطناعي</p>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container main-content pb-5">

        <div class="input-card mb-4">
            @if(session('message'))
            <div class="alert-success">✅ {{ session('message') }}</div>
            @endif
            <span class="input-card-label">أدخل التصنيفات (كل تصنيف في سطر جديد)</span>
            <form id="fetchForm">
                @csrf
                <div class="input-row">
                    <div class="input-col">
                        <textarea name="categories" id="categoriesInput" placeholder="التسويق&#10;البرمجة&#10;الجرافيكس&#10;الهندسة&#10;ادارة الاعمال">{{ old('categories') }}</textarea>
                        <div class="input-error" id="categoriesError" style="display:none;"></div>
                    </div>
                    <div class="btn-col">
                        <button type="submit" class="btn-fetch" id="fetchBtn">▶ ابدأ الجمع</button>
                        <button type="button" class="btn-stop" id="stopBtn">☐ إيقاف</button>
                    </div>
                </div>
            </form>
        </div>

        @if($playlists->total() > 0)

        <div class="section-header">
            <span class="section-title">الدورات المكتشفة</span>
            <span class="results-count">
                @if($activeCategory)
                تم العثور على {{ $playlists->total() }} دورة في تصنيف "{{ $activeCategory }}"
                @else
                تم العثور على {{ $totalCount }} دورة في {{ $categories->count() }} تصنيفات
                @endif
            </span>
        </div>

        {{-- Category Tabs --}}
        <ul class="category-tabs">
            <li>
                <a href="{{ route('playlists.index') }}" class="{{ !$activeCategory ? 'active' : '' }}">
                    الكل ({{ $totalCount }})
                </a>
            </li>
            @foreach($categories as $cat)
            <li>
                <a href="{{ route('playlists.index', ['category' => $cat->category]) }}" class="{{ $activeCategory === $cat->category ? 'active' : '' }}">
                    {{ $cat->category }} ({{ $cat->count }})
                </a>
            </li>
            @endforeach
        </ul>

        {{-- Cards --}}
        <div class="row g-3">
            @foreach($playlists as $playlist)
            <div class="col-6 col-md-4 col-lg-3">
                <a href="https://www.youtube.com/playlist?list={{ $playlist->playlist_id }}" target="_blank" class="text-decoration-none">
                    <div class="playlist-card">
                        <img src="{{ $playlist->thumbnail ?? 'https://placehold.co/320x180?text=No+Image' }}" alt="{{ $playlist->title }}" loading="lazy">
                        <div class="card-body">
                            <div class="card-title">{{ $playlist->title }}</div>
                            <div class="card-channel">🎓 {{ $playlist->channel_name }}</div>
                            <div class="card-footer-info">
                                <span class="badge-cat">{{ $playlist->category }}</span>
                                @if($playlist->video_count > 0)
                                <span class="video-count">▶ {{ $playlist->video_count }} درس</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
            </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($playlists->hasPages())
        <div class="pagination-wrapper">
            <span class="pagination-info">
                عرض {{ $playlists->firstItem() }} إلى {{ $playlists->lastItem() }} من {{ $playlists->total() }} نتيجة
            </span>
            {{ $playlists->appends(request()->query())->links('vendor.pagination.bootstrap-5') }}
        </div>
        @endif

        @else
        <div class="empty-state">
            <div class="icon">🎬</div>
            <h4>لا توجد دورات بعد</h4>
            <p>أدخل التصنيفات واضغط "ابدأ الجمع" لبدء جمع الكورسات</p>
        </div>
        @endif

    </div>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script>
        $('#fetchBtn').closest('form').on('submit', function(e) {
            e.preventDefault();

            $('#categoriesError').hide().text('');
            $('#categoriesInput').removeClass('is-invalid');

            let submitBtn = $('#fetchBtn');
            submitBtn.prop('disabled', true).text('⏳ جاري الجمع...');

            $.ajax({
                type: 'POST'
                , url: '{{ route("playlists.fetch") }}'
                , data: {
                    _token: '{{ csrf_token() }}'
                    , categories: $('#categoriesInput').val()
                , }
                , success: function(data) {
                    if (data.success) {
                        window.location.reload();
                    }
                }
                , error: function(reject) {
                    var response = $.parseJSON(reject.responseText);
                    if (response.errors && response.errors.categories) {
                        $('#categoriesError').text('⚠ ' + response.errors.categories[0]).show();
                        $('#categoriesInput').addClass('is-invalid');
                    } else {
                        $('#categoriesError')
                            .text('⚠ حدث خطأ غير متوقع، حاول مرة أخرى.')
                            .show();
                    }
                }
                , complete: function() {
                    submitBtn.prop('disabled', false).text('▶ ابدأ الجمع');
                    $('#stopBtn').removeClass('visible');
                }
            });
        });

        $('#stopBtn').on('click', function() {
            location.reload();
        });

    </script>
</body>
</html>

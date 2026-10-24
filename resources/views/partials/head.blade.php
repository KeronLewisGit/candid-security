<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light dark">
<meta name="theme-color" content="#171b1e">
<meta name="csrf-token" content="{{ csrf_token() }}">
<link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">
<script>
    // Apply the saved theme before first paint so there is no flash.
    document.documentElement.classList.add('js');
    try { var t = localStorage.getItem('theme'); if (t === 'dark' || t === 'light') document.documentElement.dataset.theme = t; } catch (e) {}
</script>
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])

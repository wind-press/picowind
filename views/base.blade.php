<!doctype html>
<html {!! $site->language_attributes ?? '' !!} data-theme="light">
<head>
    <meta charset="{{ $site->charset ?? 'UTF-8' }}" />
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="pingback" href="{{ $site->pingback_url ?? '' }}" />
    <style>
        [x-cloak] { display: none; }
    </style>
    @php wp_head(); @endphp
</head>

<body class="{{ body_class() }}" data-template="base.blade.php">
    @twig('header.twig')

    @yield('content', 'Sorry, no content')

    @section('footer')
        @twig('footer.twig')
        @php wp_footer(); @endphp
    @show
</body>
</html>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <title>Thuraiyur Cricket Council</title>
  <link rel="icon" type="image/x-icon" href="favicon.ico">

  <!-- Android Meta -->
  <meta name="theme-color" content="#0f172a">
  <meta name="mobile-web-app-capable" content="yes">

  <!-- For iOS -->
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black">
  <meta name="apple-mobile-web-app-title" content="Thuraiyur">
  <meta name="apple-mobile-web-app-description" content="TCC Cricket Community">
  <link rel="apple-touch-icon" href="Icon-180.png" sizes="180x180">
  <link rel="apple-touch-icon" href="Icon-192.png" sizes="192x192">
  <link rel="apple-touch-icon" href="Icon-512.png" sizes="512x512">
  <link rel="apple-touch-startup-image" href="Icon-180.png" media="(device-width: 320px) and (device-height: 568px">


  <!-- SEO -->
  <meta name="author" content="TCC">
  <meta name="keywords" content="TCC, TCC Website, TCC Gaming, TCC Gaming Community, TCC Gaming Website, TCC Gaming Community Website">
  <meta name="robots" content="index, follow">
  <meta name="revisit-after" content="7 days">

  <!-- Dynamic Title -->
  <title>
    <?php echo $_settings->info('title') ? $_settings->info('title') . ' | ' : '' ?>
    <?php echo $_settings->info('name') ?>
  </title>

  <!-- Styles -->
  <link rel="stylesheet" href="./styles.css">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/daisyui@5" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Belanosima:wght@400;600;700&family=Lobster+Two:ital@0;1&family=Meera+Inimai&family=Secular+One&display=swap" rel="stylesheet">

  <!-- Scripts -->
  <script src="./assets/jquery3.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/js/all.min.js" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="//unpkg.com/alpinejs" defer></script>
  <script src="./script.js"></script>

  <!-- Hide Scrollbars -->
  <style>
    html, body {
      touch-action: pan-y;
      overflow-x: hidden;
      width: 100%;
      height: 100%;
      -ms-overflow-style: none;
      scrollbar-width: none;
    }

    html::-webkit-scrollbar,
    body::-webkit-scrollbar {
      display: none;
    }
  </style>

  <!-- Random Profile Border -->
  <script>
    const daisyColors = [
      'border-primary',
      'border-secondary',
      'border-accent',
      'border-neutral',
      'border-success',
      'border-warning',
      'border-error',
      'border-info',
    ];

    function applyRandomProfileBorder(selector) {
      const elements = document.querySelectorAll(selector);
      const randomColor = daisyColors[Math.floor(Math.random() * daisyColors.length)];
      elements.forEach(element => {
        element.classList.remove(...daisyColors);
        element.classList.add('border-2', randomColor);
      });
    }

    window.onload = () => applyRandomProfileBorder('.profile-border');
  </script>
</head>

<?php require_once('config.php'); ?>
<!DOCTYPE html>
<html lang="en">
<?php require_once('includes/header.php'); ?>

<head>
  <link rel="manifest" href="./manifest.json">
  <link rel="icon" type="image/x-icon" href="favicon.ico">
  <link rel="stylesheet" href="./styles.css">
</head>

<body class="bg-base-300">
  <script>
    const currentPage = "<?php echo $_GET['p'] ?? 'home'; ?>";
    if (currentPage !== 'onboarding' && !sessionStorage.getItem('onboarding_done')) {
      sessionStorage.setItem('onboarding_done', 'true');
      window.location.href = "?p=onboarding";
    }
  </script>

  <?php
  include_once('includes/bottomnavbar.php');

  $page = $_GET['p'] ?? 'home';
  echo "<script>start_loader();</script>";

  $page_path = is_dir($page) ? "$page/index.php" : "$page.php";
  if (file_exists($page_path)) {
    include $page_path;
  } else {
    include '404.php';
  }
  echo '<div class="mb-14"></div>';

  echo "<script>end_loader();</script>";
  ?>
</body>
<!-- Request Notification Permission on Page Load -->
<script>
  document.addEventListener('DOMContentLoaded', () => {
    if (!('Notification' in window)) {
      console.warn('This browser does not support notifications.');
      return;
    }

    if (Notification.permission === 'default') {
      Notification.requestPermission().then(permission => {
        if (permission === 'granted') {
          new Notification('Notification Enabled', {
            body: 'You will now receive updates from TCC!',
            icon: './Icon-180.png'
          });
        }
      }).catch(error => {
        console.error('Notification permission request failed:', error);
      });
    }
  });
</script>

<script>
  const checkNotifications = () => {
    if (!('Notification' in window) || Notification.permission !== 'granted') return;

    fetch('./check_notifications.php')
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        if (data.error) {
          console.error('Error from server:', data.error);
          return;
        }

        if (Array.isArray(data) && data.length > 0) {
          data.forEach(notification => {
            const notif = new Notification(notification.title || 'New Notification', {
              body: notification.body || '',
              icon: notification.icon || './Icon-180.png',
              badge: notification.badge || '',
              data: {
                url: notification.url || '#'
              }
            });

            notif.onclick = (event) => {
              event.preventDefault();
              window.location.href = notification.url || '#';
            };
          });
        } else {
          console.log('No new notifications.');
        }
      })
      .catch(error => console.error('Error fetching notifications:', error));
  };

  setInterval(checkNotifications, 1000);
</script>


<!-- Modal -->
<div id="unimodal" class="modal modal-bottom z-[9999] backdrop-blur-xs">
  <div class="modal-box max-w-sm md:max-w-md bg-base-300 rounded-t-3xl shadow-2xl p-0 pb-6 overflow-hidden border border-white/20 transition-all duration-300 ease-in-out">

    <!-- Modal Header -->
    <div class="sticky top-0 z-10 p-4 bg-base-300 border-b border-gray-200 flex items-center justify-between">
      <h2 id="modal-title" class="text-lg font-bold text-gray-800 text-center w-full uppercase tracking-wide">Title</h2>
      <button onclick="closemodal()" class="absolute right-4 text-gray-400 hover:text-gray-700 transition duration-200">
        <i class="fas fa-xmark text-xl text-error"></i>
      </button>
    </div>

    <!-- Modal Body -->
    <div id="modal-body" class="bg-base-300 max-h-[80vh] overflow-y-auto custom-scroll">
      <!-- Dynamic Content -->
    </div>

  </div>
</div>
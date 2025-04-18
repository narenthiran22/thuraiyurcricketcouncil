// Register Service Worker
if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('service-worker.js')
    .then(reg => console.log('Service Worker registered', reg))
    .catch(err => console.error('Service Worker registration failed', err));
}

// Handle Install Prompt
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault();
  deferredPrompt = e;
  document.getElementById('installBtn').style.display = 'block';
});

document.getElementById('installBtn').addEventListener('click', async () => {
  if (deferredPrompt) {
    deferredPrompt.prompt();
    const { outcome } = await deferredPrompt.userChoice;
    console.log('User response to install prompt:', outcome);

    if (outcome === 'accepted') {
      askNotificationPermission();
    }

    deferredPrompt = null;
    document.getElementById('installBtn').style.display = 'none';
  }
});

function askNotificationPermission() {
  if ('Notification' in window) {
    Notification.requestPermission().then(permission => {
      if (permission === 'granted') {
        console.log('Notification permission granted!');
        showTestNotification();
      } else if (permission === 'denied') {
        console.log('Notification permission denied.');
      } else {
        console.log('Notification permission dismissed.');
      }
    }).catch(err => {
      console.error('Error requesting notification permission:', err);
    });
  } else {
    console.log('Notifications are not supported on this browser.');
  }
}

function showTestNotification() {
  if (Notification.permission === 'granted') {
    new Notification("Welcome!", {
      body: "Thanks for enabling notifications.",
      icon: "https://cdn-icons-png.flaticon.com/512/888/888879.png"
    });
  }
}

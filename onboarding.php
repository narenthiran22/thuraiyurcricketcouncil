<?php require_once ('includes/header.php'); ?>

<body class="bg-gradient-to-b from-green-100 to-green-400 font-sans">
    <div class="min-h-screen flex flex-col items-center justify-center px-4 text-center relative overflow-hidden">

        <!-- Animated gradient circle background -->
        <div class="absolute top-0 left-0 w-[300px] h-[300px] bg-green-300 rounded-full opacity-30 blur-3xl"></div>
        <div class="absolute bottom-0 right-0 w-[400px] h-[400px] bg-green-500 rounded-full opacity-20 blur-3xl"></div>

        <!-- Logo animation -->
        <img src="./assets/imgs/cric1.svg" class="w-60 mb-8 animate-fade-in-up" />

        <!-- Title with fade in -->
        <h1 class="text-3xl sm:text-4xl font-extrabold text-black mb-6 animate-fade-in-up delay-100">
            It's Not a Game,<br><span class="text-green-800">It's Emotion</span>
        </h1>

        <!-- Tagline -->
        <p class="mt-4 text-sm sm:text-base text-black animate-fade-in-up delay-200">
            from <br><strong class="text-green-900 text-xs"><h2>Thuraiyur Cricket Council</h2></strong>
        </p>

        <!-- Premium Loading Bar -->
        <div class="mt-10 w-64 h-3 rounded-full bg-green-200 overflow-hidden relative animate-fade-in-up delay-300">
            <div class="absolute h-full w-full shimmer bg-gradient-to-r from-green-400 via-green-600 to-green-400"></div>
        </div>

    </div>


    <!-- Custom Animations -->
    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmerMove {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .animate-fade-in-up {
            animation: fadeInUp 1s ease forwards;
            opacity: 0;
        }

        .delay-100 {
            animation-delay: 0.2s;
        }

        .delay-200 {
            animation-delay: 0.4s;
        }

        .delay-300 {
            animation-delay: 0.6s;
        }

        .shimmer {
            animation: shimmerMove 2s infinite;
            background-size: 200% 100%;
            filter: brightness(1.1);
        }
    </style>

    <script>
      document.addEventListener('DOMContentLoaded', () => {
        // Mark onboarding as completed
        sessionStorage.setItem('onboarding_done', 'true');

        // Redirect to home page after a delay
        setTimeout(() => {
          window.location.href = "./?p=home";
        }, 5000); // Adjust delay as needed
      });
    </script>
</body>

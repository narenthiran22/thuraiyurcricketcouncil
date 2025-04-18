<!-- Mobile Bottom Navigation Bar -->
<div class="fixed bottom-0 left-0 w-full bg-base-300 shadow-lg border-t border-gray-200" style="z-index: 99;">
  <div class="flex justify-around items-center py-2">
    <a href="./?p=home" id="nav-home" class="nav-item flex flex-col items-center">
      <i class="fas fa-home text-xl"></i>
      <span class="text-xs mt-1">Home</span>
    </a>
    <?php if ($_settings->userdata('status') == 1): ?>
      <a href="./?p=chats" id="nav-gallery" class="nav-item flex flex-col items-center">
        <i class="fas fa-comments"></i>
        <span class="text-xs mt-1">Chats</span>
      </a>
    <?php else: ?>
      <a href="javascript:void(0);" onclick="alert_toast('You..! login to access chats','warning')" class="nav-item flex flex-col items-center">
        <i class="fas fa-comments text-gray-400"></i>
        <span class="text-xs mt-1 text-gray-400">Chats</span>
      </a>
    <?php endif; ?>


    <a href="./?p=matches" id="nav-matches" class="nav-item flex flex-col items-center">
      <i class="fas fa-trophy text-xl"></i>
      <span class="text-xs mt-1">Matches</span>
    </a>
    <a href="./?p=team" id="nav-team" class="nav-item flex flex-col items-center">
      <i class="fas fa-users text-xl"></i>
      <span class="text-xs mt-1">Team</span>
    </a>
    <?php if($_settings->userdata('admin')==1): ?>
      <a href="./?p=admin" id="nav-admin" class="nav-item flex flex-col items-center">
        <i class="fas fa-user-shield text-xl"></i>
        <span class="text-xs mt-1">Admin</span>
      </a>
    <?php endif; ?>

      <a href="./?p=settings" id="nav-settings" class="nav-item flex flex-col items-center">
        <i class="fas fa-cog text-xl "></i>
        <span class="text-xs mt-1">Account</span>
      </a>
  </div>
</div>

<style>
  .nav-item {
    color: rgb(65, 64, 64);
    /* Default gray color */
  }

  .nav-item.active {
    color: #22c55e !important;
    /* Active green */
  }
</style>

<script>
  document.addEventListener("DOMContentLoaded", function() {
    let params = new URLSearchParams(window.location.search);
    let currentPage = params.get("p") || "home"; // Default to "home" if no parameter is found

    let isActive = false;

    document.querySelectorAll(".nav-item").forEach(item => {
      let itemPage = item.getAttribute("href").split("p=")[1]; // Extract page name

      // // Prevent click if already active
      // item.addEventListener("click", function(e) {
      //   if (itemPage === currentPage) {
      //     e.preventDefault(); // Block the click if already on current page
      //   }
      // });

      if (itemPage === currentPage) {
        item.classList.add("active");
        isActive = true;

        // Add animate-spin if currentPage is 'settings'
        if (currentPage === 'settings') {
          let icon = item.querySelector("i");
          if (icon) icon.classList.add("animate-spin");
        }
      }
    });

    if (!isActive) {
      document.querySelector(".fixed.bottom-0").style.display = "none";
    }
  });
</script>


<div id="confirm_modal" class="fixed bottom-0 left-1/2 -translate-x-1/2 w-full max-w-md bg-base-200 rounded-xl shadow-2xl border border-gray-200 hidden opacity-0 transition-transform transform translate-y-full" style="z-index:9999;">
  <div class="p-6 bg-base-100 rounded-xl">
    <div class="flex justify-center">
      <div class="">
        <i class="fas fa-exclamation-circle text-warning text-4xl"></i>
      </div>
    </div>
    <h3 class="font-bold text-xl text-center text-gray-800 mt-4">Are You Sure?</h3>
    <p id="confirm_message" class="py-3 text-center text-gray-600">Do you want to proceed with this action?</p>
    <div class="flex justify-center gap-4 mt-4">
      <button id="confirm_btn" class="btn btn-success px-6 rounded-full shadow-md transition-all">Confirm</button>
      <button onclick="closeConfirmModal()" class="btn btn-error px-6 rounded-full shadow-md transition-all">Cancel</button>
    </div>
  </div>
</div>


document.addEventListener(
  "wheel",
  function (event) {
    if (event.ctrlKey) event.preventDefault();
  },
  {
    passive: false,
  }
);

document.addEventListener("gesturestart", function (event) {
  event.preventDefault();
});

let lastTouchEnd = 0;
document.addEventListener(
  "touchend",
  function (event) {
    let now = new Date().getTime();
    if (now - lastTouchEnd <= 300) event.preventDefault();
    lastTouchEnd = now;
  },
  false
);

function openModal(content, type) {
  const title = type?.charAt(0).toUpperCase() + type?.slice(1) || "Modal";
  document.getElementById("modal-title").textContent = title;
  document.getElementById("modal-body").innerHTML = content;
  document.getElementById("unimodal").classList.add("modal-open");
}

function closemodal() {
  document.getElementById("unimodal").classList.remove("modal-open");
}

document.addEventListener("click", function (event) {
  const btn = event.target.closest(".open-modal");
  if (btn) {
    const id = btn.dataset.id;
    const type = btn.dataset.type || btn.dataset.url || "content";
    const url = `${type}.php?id=${id}`;
    fetch(url)
      .then((res) => res.text())
      .then((html) => openModal(html, type))
      .catch((err) => {
        console.error("Modal load failed:", err);
        openModal(
          '<p class="text-red-500 p-4">Failed to load content</p>',
          type
        );
      });
  }
});
window.onload = function () {
  document.querySelectorAll("input").forEach((input) => {
    input.setAttribute("autocomplete", "off");
    input.setAttribute("readonly", "readonly"); // Prevent autofill
    setTimeout(() => input.removeAttribute("readonly"), 100); // Restore usability
  });
};

function showModal(modalId) {
  // Close any open modals smoothly
  document.querySelectorAll(".show-modal").forEach((modal) => {
    modal.classList.add("hide-modal");
    setTimeout(() => {
      modal.classList.add("hidden");
      modal.classList.remove(
        "show-modal",
        "hide-modal",
        "bg-black/30",
        "backdrop-blur-sm"
      );
    }, 200);
  });

  // Open the selected modal
  let modal = document.getElementById(modalId);
  if (modal) {
    setTimeout(() => {
      modal.classList.remove("hidden");
      modal.classList.add(
        "show-modal",
        "bg-black/30" // 🔵 Light opacity
      );
    }, 200);
  } else {
    alert_toast(`"${modalId}" not found`, "error");
  }
}

function closeModal(modalId) {
  let modal = document.getElementById(modalId);
  if (modal) {
    modal.classList.add("hide-modal");
    setTimeout(() => {
      modal.classList.add("hidden");
      modal.classList.remove("show-modal", "hide-modal", "bg-black/30");
    }, 200);
  } else {
    alert_toast(`"${modalId}" not found`, "error");
  }
}

function conf(message, action) {
  const modal = $("#confirm_modal");
  const modalMessage = $("#confirm_message");
  const confirmBtn = $("#confirm_btn");

  // Set the message
  modalMessage.html(message);

  // Remove previous event listeners and add a new one
  confirmBtn.off("click").on("click", function () {
    action();
    closeConfirmModal();
  });

  // Show the modal with animation (slide up)
  modal.removeClass("hidden").addClass("bg-black/20");
  setTimeout(() => {
    modal.removeClass("opacity-0 translate-y-full").addClass("translate-y-0");
  }, 10);
}

function closeConfirmModal() {
  const modal = $("#confirm_modal");

  // Slide down animation before hiding
  modal.addClass("translate-y-full").removeClass("translate-y-0");

  setTimeout(() => {
    modal.addClass("hidden opacity-0").removeClass("bg-black/20"); // Hide completely after animation
  }, 300); // Matches animation duration
}

function start_loader() {
  if ($("#preloader").length == 0) {
    $("body").append(`
      <div id="preloader" class="fixed inset-0 flex items-center justify-center z-[9999]">
        <span class="loading loading-infinity loading-xl text-success"></span>
      </div>
    `);
  }
}

function end_loader() {
  $("#preloader").remove();
}

window.alert_toast = function (
  $msg = "TEST",
  $bg = "success",
  $pos = "top" // like WhatsApp
) {
  var textColor =
    {
      success: "#36d399",
      error: "#f87272",
      warning: "#fbbd23",
      question: "#abff8",
      info: "#abff8",
    }[$bg] || "black";

  const existingStyle = document.getElementById("swal-toast-style");
  if (existingStyle) existingStyle.remove();

  const style = document.createElement("style");
  style.id = "swal-toast-style";
  style.innerHTML = `
    @keyframes slideUp {
        from {
            bottom: 100px;
            opacity: 1;
        }
        to {
            bottom: 0px;
            opacity: 1;
        }
    }

    .swal-toast-full-width {
        width: 100% !important;
        max-width: 100% !important;
        left: 50% !important;
        transform: translateX(-50%) !important;
        position: fixed !important;
        margin-bottom: 50px;
        border-radius: 50px;
        text-align: center;
        justify-content: center;
        align-items: center;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        font-size: 12px;
        padding: 5px 10px;
        border: 1px solid ${textColor};
        animation: slideUp 0.9s ease-out;
        background: black;
        backdrop-filter: blur(5px);
        -webkit-backdrop-filter: blur(5px);
        z-index: 9999 !important; /* Add this line */
    }
`;

  document.head.appendChild(style);

  const audio = new Audio("assets/sounds/alert.mp3");
  audio.play().catch((e) => console.warn("Audio play failed", e));

  var Toast = Swal.mixin({
    toast: true,
    position: $pos,
    showConfirmButton: false,
    timer: 2000,
    iconColor: textColor,
    customClass: {
      popup: "swal-toast-full-width",
    },
  });

  Toast.fire({
    icon: $bg,
    title: `<span style="color: ${textColor}; font-weight: bold;">${$msg}</span>`,
  });
};

function switchTab(event, tabId) {
  // Hide all tab contents
  document
    .querySelectorAll(".tab-content")
    .forEach((tab) => tab.classList.add("hidden"));
  // Show the selected tab content
  document.getElementById(tabId).classList.remove("hidden");

  // Remove active class from all tabs
  document
    .querySelectorAll(".tabs a")
    .forEach((tab) => tab.classList.remove("tab-active"));
  // Add active class to the clicked tab
  event.currentTarget.classList.add("tab-active");
}

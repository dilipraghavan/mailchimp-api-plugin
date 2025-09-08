document.addEventListener("DOMContentLoaded", () => {
  const form = document.getElementById("mc_form");
  if (!form) return;

  const btn = document.getElementById("mc_submit_btn");
  const emailElement = document.getElementById("mc_email");
  const hpElement = document.getElementById("mc_hp");
  const consentElement = document.getElementById("mc_consent");
  const msgElement = document.querySelector(".mc-api-msg");

  form.addEventListener("submit", async (e) => {
    e.preventDefault();
    const hp = hpElement.value;
    if (hp) {
      return;
    }
    const consent = consentElement.checked;
    if (!consent) {
      msgElement.textContent = "Please accept the consent.";
      return;
    }

    const email = (emailElement.value || "").trim();
    if (!email) {
      msgElement.textContent = "Please enter your email.";
      return;
    }

    const url =
      form.dataset.endpoint ||
      (window.MC_API_CFG && window.MC_API_CFG.endpoint);
    const nonce = window.MC_API_CFG && window.MC_API_CFG.nonce;
    const data = {
      email,
      consent: true,
    };

    btn.disabled = true;
    msgElement.textContent =
      window?.MC_API_CFG?.msgs?.submitting || "Submitting...";

    try {
      const res = await fetch(url, {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-WP-Nonce": nonce || "",
        },
        body: JSON.stringify(data),
      });

      const payload = await res.json();
      if (res.ok) {
        msgElement.textContent = payload.message
          ? payload.message
          : window?.MC_API_CFG?.msgs?.ok || "Subscribed!";
        form.reset();
      } else {
        msgElement.textContent = payload.message
          ? payload.message
          : window?.MC_API_CFG?.msgs?.genericError || "Error";
      }
    } catch (e) {
      msgElement.textContent =
        window?.MC_API_CFG?.msgs?.genericError || "Network Error";
    } finally {
      btn.disabled = false;
    }
  });
});

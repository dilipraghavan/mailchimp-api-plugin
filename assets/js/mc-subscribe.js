(function () {
  function attachMcHandler(form) {
    if (!form || form.dataset.mcBound === "1") return;
    form.dataset.mcBound = "1";

    const btn = form.querySelector("#mc_submit_btn");
    const msg = form.querySelector(".mc-api-msg");
    const cfg = window.MC_API_CFG || {};
    const msgs = cfg.msgs || {};
    const originalBtnText = btn ? btn.textContent : "";

    form.addEventListener("submit", async (e) => {
      e.preventDefault();

      const fd = new FormData(form);
      if ((fd.get("hp") || "").trim() !== "") return; // honeypot

      const email = (fd.get("email") || "").trim();
      const consent = !!fd.get("consent");

      if (!consent) {
        msg.textContent = "Please accept the consent.";
        msg.className = "mc-api-msg mc-api-error";
        return;
      }
      if (!email) {
        msg.textContent = "Please enter your email.";
        msg.className = "mc-api-msg mc-api-error";
        return;
      }

      const body = {
        email,
        consent,
        hp: fd.get("hp") || "",
        mc_nonce: fd.get("mc_nonce") || "",
      };

      const url =
        form.dataset.endpoint ||
        (window.MC_API_CFG && window.MC_API_CFG.endpoint) ||
        "";
      if (!url) {
        msg.textContent = msgs.genericError || "Something went wrong.";
        msg.className = "mc-api-msg mc-api-error";
        return;
      }

      if (btn) {
        btn.disabled = true;
        btn.textContent = msgs.submitting || "Submitting...";
      }
      msg.textContent = "";
      msg.className = "mc-api-msg";

      try {
        const res = await fetch(url, {
          method: "POST",
          credentials: "same-origin",
          headers: {
            "Content-Type": "application/json",
            "X-WP-Nonce": (window.MC_API_CFG && window.MC_API_CFG.wpRest) || "",
          },
          body: JSON.stringify(body),
        });
        const payload = await res.json().catch(() => ({}));
        if (res.ok && payload?.success) {
          msg.textContent =
            payload.message || msgs.ok || "Thanks. Check your inbox.";
          msg.className = "mc-api-msg mc-api-success";
          form.reset();
        } else {
          msg.textContent =
            payload?.message || msgs.genericError || "Something went wrong.";
          msg.className = "mc-api-msg mc-api-error";
        }
      } catch (err) {
        console.error(err);
        msg.textContent = msgs.genericError || "Network Error";
        msg.className = "mc-api-msg mc-api-error";
      } finally {
        if (btn) {
          btn.disabled = false;
          btn.textContent = originalBtnText;
        }
      }
    });
  }

  function bindMCForm() {
    document.querySelectorAll("#mc_form").forEach(attachMcHandler);
  }

  document.addEventListener("DOMContentLoaded", bindMCForm);
})();

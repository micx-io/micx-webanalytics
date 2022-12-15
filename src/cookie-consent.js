/**
 * Cookie Consent
 *
 * A cookie consent where nothing can go wrong (Scripts are inside <template>)
 *
 * @author Matthias Leuffen <matthias@leuffen.de>
 */


class MicxCookieConsentElement extends HTMLElement {
    constructor() {
        super();
        this.config = %%CONFIG%%;
        this.mounted = false;
    }

    mountAnalytics(withTracking) {
      if (this.mounted === true)
        return;
      let endpoint_url="%%ENDPOINT_URL%%";
      let subscription_id = "%%SUBSCRIPTION_ID%%";

      let purl = endpoint_url + `wa.js?subscription_id=${subscription_id}&analytics`;

      let script = document.createElement("script");
      script.setAttribute("src", purl);
      this.appendChild(script);

      let tpl = this.querySelector("template");
      this.appendChild(tpl.content);

      this.mounted = true;
    }

    openConsent() {
        this.removeAttribute("hidden");
    }

    hideConsent() {
        this.innerHTML = "";
    }

    connectedCallback() {

      if (this.config.autostart === true) {
        window.setTimeout(() => {

          this.mountAnalytics(true);
        }, 50);
      }
      window.setTimeout(()=> {
        const locStorName = "MICX-COOKIE-CONSENT";
        const askAgain = 86400 * 1000;
        let consentData = {
          time: 0,
          consent: false
        };
        if (localStorage.getItem(locStorName)) {
          consentData = JSON.parse(localStorage.getItem(locStorName));
        }

        if (consentData.consent === false && consentData.time < (+ new Date()) - askAgain) {
          this.openConsent();
          consentData.time = +new Date();
          let consentBtn = this.querySelector("*[data-consent='accept']");
          let declineBtn = this.querySelector("*[data-consent='decline']");
          consentBtn.addEventListener("click", (e)=>{
            consentData.consent = true;
            localStorage.setItem(locStorName, JSON.stringify(consentData));
            this.mountAnalytics(true);
            this.hideConsent();
          });
          declineBtn.addEventListener("click", (e)=>{
            consentData.consent = false;
            localStorage.setItem(locStorName, JSON.stringify(consentData));
            this.hideConsent();
            this.mountAnalytics(false);
          });
        } else {
          if (consentData.consent === true) {
            this.mountAnalytics(true);
          } else {
            this.mountAnalytics(false);
          }
          this.hideConsent();
        }

      }, 2000);
  }
}

customElements.define("micx-cookie-consent", MicxCookieConsentElement);

(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let subscription_id = "%%SUBSCRIPTION_ID%%";

  let logUrl = endpoint_url + `log?subscription_id=${subscription_id}`;
  let params = new URLSearchParams(window.location.search);
  if ( ! params.has("micx-wa-session") &&  sessionStorage.getItem("MICX_WA_SESSION") === null) {
    fetch(logUrl, {
      method: "POST",
      cache: "no-cache",
      headers: {'Content-Type': "application/json"},
      body: JSON.stringify({
        href: window.location.href,
        user_agent: window.navigator.userAgent,
        language: window.navigator.language,
        screen: screen.width + "x" + screen.height,
        window:  window.innerWidth + "x"+ window.innerHeight,
      })
    })
  }

  if (params.has("micx-wa-session") || sessionStorage.getItem("MICX_WA_SESSION") !== null) {
      let div = document.createElement("div");
      let html = "";
      if (typeof jQuery === "undefined") {
          html +=`<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>`;
      }
      let purl = endpoint_url + `wa.js?subscription_id=${subscription_id}&player`;
      let script = document.createElement("script");
      script.setAttribute("src", purl);
      document.body.append(script);
      //html += `<script src="${purl}"></script>`;
      div.innerHTML = html;
      document.body.append(div);
  }

})();



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
    }

    mountAnalytics() {
      let endpoint_url="%%ENDPOINT_URL%%";
      let subscription_id = "%%SUBSCRIPTION_ID%%";
      let purl = endpoint_url + `wa.js?subscription_id=${subscription_id}&analytics`;

      let script = document.createElement("script");
      script.setAttribute("src", purl);
      this.appendChild(script);

      let tpl = this.querySelector("template");
      this.appendChild(tpl.content);
    }

    openConsent() {
        this.removeAttribute("hidden");
    }

    hideConsent() {
        this.innerHTML = "";
    }

    connectedCallback() {
      window.addEventListener("DOMContentLoaded", ()=> {
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
            this.mountAnalytics();
            this.hideConsent();
          });
          declineBtn.addEventListener("click", (e)=>{
            consentData.consent = false;
            localStorage.setItem(locStorName, JSON.stringify(consentData));
            this.hideConsent();
          });
        } else {
          if (consentData.consent === true) {
            this.mountAnalytics();
          }
          this.hideConsent();
        }

      });
  }
}

customElements.define("micx-cookie-consent", MicxCookieConsentElement);

(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let subscription_id = "%%SUBSCRIPTION_ID%%";

    let params = new URLSearchParams(window.location.search);
    if (params.has("micx-wa-session") || sessionStorage.getItem("MICX_WA_SESSION") !== null) {
        if (typeof jQuery === "undefined") {
            document.writeln(`<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>`);
        }
        let purl = endpoint_url + `wa.js?subscription_id=${subscription_id}&player`;
        document.writeln(`<script src="${purl}"></script>`);
    }

})();


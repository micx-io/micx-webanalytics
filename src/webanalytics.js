
/**
 * Micx.io WebAnalytics
 *
 * Usage: See https://github.com/micx-io/micx-webanalytics
 *
 * @licence MIT
 * @author Matthias Leuffen <m@tth.es>
 */


(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let rand_id="%%RAND%%";
  let server_date="%%SERVER_DATE%%";
  let subscription_id = "%%SUBSCRIPTION_ID%%"

  let startTime = +new Date();
  let wakeups = 0;

  let lsd = localStorage.getItem("MICX_ANALYTICS_" + subscription_id);
  if (lsd === null) {
    lsd = {
      "visitor_id_gmdate": server_date,
      "visitor_id": rand_id,
      "visitor_seq": 0
    }
  } else {
    lsd = JSON.parse(lsd);
  }
  lsd.visitor_seq++;
  localStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(lsd));

  let ssd = sessionStorage.getItem("MICX_ANALYTICS_" + subscription_id);
  if (ssd === null) {
    ssd = {
      "session_id_gmdate": server_date,
      "session_id": rand_id,
      "session_seq": 0
    }
  } else {
    ssd = JSON.parse(ssd);
  }
  ssd.session_seq++;
  sessionStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(ssd));

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState !== 'hidden')
      return;

    let data = {
      ...lsd,
      ...ssd,

      href: window.location.href,
      user_agent: window.navigator.userAgent,
      language: window.navigator.language,
      screen: screen.height + "x" + screen.width,
      duration: (+new Date() - startTime) / 1000,
      wakeups: wakeups++
    }

    navigator.sendBeacon(endpoint_url, JSON.stringify(data));
  });

})();

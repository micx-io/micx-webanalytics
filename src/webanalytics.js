
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
  let endpoint_key = "%%ENDPOINT_KEY%%"

  let startTime = +new Date();
  let wakeups = 0;

  let params = new URLSearchParams(window.location.search);
  if (params.has("micx-wa-session")) {
    sessionStorage.setItem("MICX_WA_SESSION", JSON.stringify({
        "session_id": params.get("micx-wa-session"),
        "endpoint_key": params.get("micx-wa-key"),
        "session_seq": 1
    }));
  }



  if (sessionStorage.getItem("MICX_WA_SESSION")) {
    return;
  }
  let trim = (num) => {
    return Math.trunc(num * 10) / 10;
  }

  let timeofs = () => {
    return trim((+new Date() - startTime) / 1000);
  }

  let lsd = localStorage.getItem("MICX_ANALYTICS_" + subscription_id);
  if (lsd === null) {
    lsd = {
      "visitor_id_gmdate": server_date,
      "visitor_id": rand_id,
      "visitor_seq": 0,
      "visits": 0,
      "last_visit_gmdate": server_date
    }
  } else {
    lsd = JSON.parse(lsd);
  }
  lsd.visitor_seq++;

  let ssd = sessionStorage.getItem("MICX_ANALYTICS_" + subscription_id);
  if (ssd === null) {
    ssd = {
      "session_id_gmdate": server_date,
      "session_id": rand_id,
      "session_seq": 0,
      "endpoint_key": endpoint_key,
      "conversions": {},
      "track": []
    }
    lsd.visits++;
  } else {
    lsd.last_visit_gmdate = server_date;
    ssd = JSON.parse(ssd);
    ssd.conversions = {};
    console.log("reload track")
    ssd.track = [{s:timeofs(), d: 0, ts: window.scrollY, te: window.scrollY}]
  }
  ssd.session_seq++;
  sessionStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(ssd));
  localStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(lsd));



  let s_debounce = null;
  let s_evt = null;
  window.addEventListener("scroll", (e) => {
    if (s_debounce !== null) {
      window.clearTimeout(s_debounce);
    }
    if (s_evt === null) {
      s_evt = {s:timeofs(), d: null, ts: window.scrollY, te: null}
    }

    s_debounce = window.setTimeout(() => {
      s_evt.d = trim(timeofs() - s_evt.s);
      s_evt.te = window.scrollY;
      if (ssd.track.length < 500)
        ssd.track.push(s_evt);
      s_evt = null;
    }, 200);

  })

  document.addEventListener("DOMContentLoaded", ()=> {
    for (let el of document.querySelectorAll("*[micx-wa-conversion]")) {
      el.addEventListener("click", (e) => {
        let cid = e.target.getAttribute("micx-wa-conversion");
        ssd.conversions[cid] = timeofs();
        //ssd.track.push([])
      })
    }
  });

  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState !== 'hidden')
      return;

    let data = {
      ...lsd,
      ...ssd,

      href: window.location.href,
      user_agent: window.navigator.userAgent,
      language: window.navigator.language,
      screen: screen.width + "x" + screen.height,
      window:  window.innerWidth + "x"+ window.innerHeight,
      duration: (+new Date() - startTime) / 1000,
      wakeups: wakeups++
    }

    data.track.push({s:timeofs(), d: 0, ts: window.scrollY, te: window.scrollY});

    console.log("send track");
    navigator.sendBeacon(endpoint_url + "&endpoint_key=" + ssd.endpoint_key, JSON.stringify(data));
  });

})();

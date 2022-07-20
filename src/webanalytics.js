
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
  let page_id = "undefined"
  let metaLastModified = document.head.querySelector("meta[name='last-modified']")
  if (metaLastModified !== null)
    page_id = metaLastModified.getAttribute("content");

  let startTime = +new Date();
  let wakeups = 0;

  let params = new URLSearchParams(window.location.search);

  if (params.has("micx-wa-disable"))
    localStorage.setItem("MICX_WA_DISABLED", params.get("micx-wa-disable"));

  if (params.has("micx-wa-session") || sessionStorage.getItem("MICX_WA_SESSION") !== null || localStorage.getItem("MICX_WA_DISABLED") === "1") {


    console.warn("Micx WA disabled");
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
      "visitor_cpg": params.get("_cpg"),
      "visitor_tg": params.get("_tg"),
      "visitor_keyword": params.get("_keyword"),
      "visitor_location": params.get("_loc"),
      "visitor_device": params.get("_device"),
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
      "page_id": page_id,
      "session_seq": 0,
      "endpoint_key": endpoint_key,
      "conversions": {},
      "mouse_track": 0,
      "mouse_clicks": 0,
      "scroll_track": 0,
      "key_downs": 0,
      "track": []
    }
    lsd.visits++;
  } else {
    lsd.last_visit_gmdate = server_date;
    ssd = JSON.parse(ssd);
    ssd.conversions = {};
    ssd.track = [{s:timeofs(), d: 0, x: window.scrollX, y: window.scrollY, z: window.devicePixelRatio}]
  }
  ssd.session_seq++;
  sessionStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(ssd));
  localStorage.setItem("MICX_ANALYTICS_" + subscription_id, JSON.stringify(lsd));

  document.addEventListener("mousedown", (e)=>{
    ssd.track.push({s:timeofs(), d: 0.2, x: e.clientX, y: e.clientY, k: true});
    ssd.mouse_clicks++;
  });

  document.addEventListener("mousemove", (e)=>{
    ssd.mouse_track++;
  });

  document.addEventListener("keydown", (e)=>{
    ssd.track.push({s:timeofs(), d: 0.2, x: e.clientX, y: e.clientY, key: e.key});
    ssd.key_downs++;
  });

  let s_debounce = null;
  let s_evt = null;
  window.addEventListener("scroll", (e) => {
    ssd.scroll_track++;
    if (s_debounce !== null) {
      window.clearTimeout(s_debounce);
    }
    if (s_evt === null) {
      s_evt = {s:timeofs(), d: null, x: null, y: null, z: null}
    }

    s_debounce = window.setTimeout(() => {
      s_evt.d = trim(timeofs() - s_evt.s);
      s_evt.y = window.scrollY;
      s_evt.x = window.scrollX;
      s_evt.z = window.devicePixelRatio;

      if (ssd.track.length < 500)
        ssd.track.push(s_evt);
      s_evt = null;
    }, 200);

  })

  window.setTimeout(()=> {
    for (let el of document.querySelectorAll("*[micx-wa-conversion]")) {
      el.addEventListener("click", (e) => {
        let cid = e.target.getAttribute("micx-wa-conversion");
        ssd.conversions[cid] = timeofs();
      })
    }
  }, 1000);

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

    data.track.push({s:timeofs(), d: 0, x: window.scrollX, y: window.scrollY, z: window.devicePixelRatio});
    navigator.sendBeacon(endpoint_url + `emit?subscription_id=${subscription_id}&endpoint_key=${ssd.endpoint_key}`, JSON.stringify(data));
  });

})();

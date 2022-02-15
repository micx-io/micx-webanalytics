
/**
 * Micx.io WebAnalytics
 *
 * Usage: See https://github.com/micx-io/micx-formmailer
 *
 * @licence MIT
 * @author Matthias Leuffen <m@tth.es>
 */


(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let startTime = +new Date();


  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState !== 'hidden')
      return;


    let data = {
      href: window.location.href,
      user_agent: window.navigator.userAgent,
      language: window.navigator.language,
      screen: screen.height + "x" + screen.width,
      duration: (+new Date() - startTime) / 1000
    }

    navigator.sendBeacon(endpoint_url, JSON.stringify(data));
  });

})();

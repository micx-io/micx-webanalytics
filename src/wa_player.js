
(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let startTime = +new Date();
  let params = new URLSearchParams(window.location.search);

  let trim = (num) => {
    return Math.trunc(num * 10) / 10;
  }

  let timeofs = () => {
    return trim((+new Date() - startTime) / 1000);
  }

  let play = (track) => {
    console.log ("play track:", track, track.length);

    let sTo = (frame) => {
      console.log("frame", frame);
      if (typeof frame === "undefined") {
        qLoadSession(true);
        return;
      }
      $("html, body").animate({scrollTop: frame.te,}, frame.d * 1000);

      window.setTimeout(() => {
        sTo(track.shift());
      }, (frame.s - timeofs()) * 1000)
    }
    let frame = track.shift()
    $("html, body").scrollTop = frame.ts;
    sTo(frame);
  }

  let qLoadSession = (next) => {
    let rd = JSON.parse(sessionStorage.getItem("MICX_WA_SESSION"));
    if (next === true)
      rd.session_seq++;
    sessionStorage.setItem("MICX_WA_SESSION", JSON.stringify(rd));

    fetch(endpoint_url + `&session_id=${rd.session_id}&session_seq=${rd.session_seq}&endpoint_key=${rd.endpoint_key}`)
      .then(response => response.json())
      .then(data => {
        console.log(data);
        if (data.sequence_end === true) {
          sessionStorage.removeItem("MICX_WA_SESSION");
          alert("Sequence ended");
          window.close();
          return;
        }
        if (params.has("micx-wa-session")) {
          let dimensions = data.window.split("x");
          let y = parseInt(dimensions[0]);
          let x = parseInt(dimensions[1]);
          window.open(data.href, null, `width=${y} height=${x}`);
          return;
        }

        if (window.location.href !== data.href) {
          window.location.href = data.href;
        } else {
          play(data.track);
        }
      });


  }

  if (sessionStorage.getItem("MICX_WA_SESSION")) {
    if (typeof jQuery === "undefined") {
      document.writeln(`<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>`);
    }
    document.addEventListener("DOMContentLoaded", ()=> {
      console.log(window.innerHeight, window.innerWidth);


      qLoadSession()
    });
  }

})();

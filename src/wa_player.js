
(()=>{
  let endpoint_url="%%ENDPOINT_URL%%";
  let subscription_id = "%%SUBSCRIPTION_ID%%"
  let endpoint_key = "%%ENDPOINT_KEY%%"

  let startTime = +new Date();
  let params = new URLSearchParams(window.location.search);

  if (params.has("micx-wa-session")) {
    if (! params.has("popup")) {
      document.body.append(document.createElement("micx-wa-player"));
    } else {
      sessionStorage.setItem("MICX_WA_SESSION", JSON.stringify({
        "session_id": params.get("micx-wa-session"),
        "endpoint_key": params.get("micx-wa-key"),
        "session_seq": 1
      }));
    }
  }


  let clickDiv = null;

  let trim = (num) => {
    return Math.trunc(num * 10) / 10;
  }

  let timeofs = () => {
    return trim((+new Date() - startTime) / 1000);
  }

  let isRealLink = (element) => {
      if (element instanceof HTMLElement && element.hasAttribute("href") && ! element.getAttribute("href").startsWith("javascript:"))
        return true;
      if (element.parentElement !== null)
        return isRealLink(element.parentElement);
      return false;
  }

  let play = (track) => {
    console.log ("play track:", track, track.length);

    let sTo = (frame) => {
      if (typeof frame === "undefined") {
        qLoadSession(true);
        return;
      }
      if (frame.k === true) {
        clickDiv.style.display = "block";
        clickDiv.style.top = frame.y + "px";
        clickDiv.style.left = frame.x + "px";
        window.setTimeout(() => {
          clickDiv.style.display = "none";
          let elem = document.elementFromPoint(frame.x, frame.y);
          if (elem === null)
            return;
          if ( isRealLink(elem) )
            return;
          elem.focus();
          elem.click();

        }, 200)
      } else if(typeof frame.key !== "undefined") {
        let fe = document.activeElement;
        if (fe === null)
          return;
        if (typeof fe.value !== "undefined")
          fe.value += frame.key
      } else {
        $("html, body")
          .animate({scrollTop: frame.y}, frame.d * 1000)
          .animate({scrollLeft: frame.x}, frame.d * 1000);
      }


      window.setTimeout(() => {
        sTo(track.shift());
      }, (frame.s - timeofs() + frame.d) * 1000)
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

    fetch(endpoint_url + `emit?subscription_id=${subscription_id}&session_id=${rd.session_id}&session_seq=${rd.session_seq}&endpoint_key=${rd.endpoint_key}`)
      .then(response => response.json())
      .then(data => {
        console.log(data);
        if (data.sequence_end === true) {
          sessionStorage.removeItem("MICX_WA_SESSION");
          alert("Sequence ended");
          window.close();
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


    let pointer = document.createElement("div");
    document.body.append(pointer);
    let shadow = pointer.attachShadow({mode: "closed"});
    shadow.innerHTML = `
        <div style='position:fixed;box-shadow: 0 0 5px black;width:21px;height:21px;border-radius: 20px;margin-top:-10px;margin-left:-10px;border: 1px solid #888888;background-color:white;z-index: 999999;'>
            <div style="width:9px;height:9px;margin:5px;background-color: #009999;border-radius: 10px;"></div>
        </div>`;

    clickDiv = shadow.firstElementChild;


    document.addEventListener("DOMContentLoaded", ()=> {
      console.log(window.innerHeight, window.innerWidth);
      qLoadSession()
    });
  }


  class MicxWaPlayerCompontent extends HTMLElement {

    constructor() {
      super();
      this.endpoint_url="%%ENDPOINT_URL%%";
      this.shadow = this.attachShadow({mode: "closed"});
    }

    connectedCallback() {
      fetch(endpoint_url + `emit?subscription_id=${subscription_id}&session_id=${params.get("micx-wa-session")}&session_seq=1&endpoint_key=${params.get("micx-wa-key")}`)
        .then(resp=>resp.json())
        .then((data)=> {
          let dimensions = data.window.split("x");
          let y = parseInt(dimensions[0]);
          let x = parseInt(dimensions[1]);

          this.shadow.innerHTML = `
              <link type="text/css" rel="stylesheet" href="${this.endpoint_url}player?css">

              <div class="player">
                <h1>Replay Session: <span data="session_id_gmdate"></span></h1>
                <p>
                    First visit: <span data="visitor_id_gmdate"></span>
                    #Visit: <span data="visits"></span>
                    #_cpg: <span data="visitor_cpg"></span>
                    #_tg: <span data="visitor_tg"></span>
                </p>
                <p>Last visit: <span data="last_visit_gmdate"></span></p>
                <p>User-Agent: <span data="user_agent"></span> Language: <span data="language"></span></p>
                <p>HREF: <span data="href"></span> (Duration:<span data="duration"></span>sec)</p>

                <a id="openLink" href="javascript:;">Start player in new window</a>
              </div>`;

          for(let el of this.shadow.querySelectorAll("*[data]")) {
            el.textContent = data[el.getAttribute("data")];
          }

          this.shadow.getElementById("openLink").addEventListener("click", (e)=>{
            window.open(window.location.href + "&popup=1", "_blanc", `width=${y} height=${x}`);
          });
        });




    }
  }
  customElements.define("micx-wa-player", MicxWaPlayerCompontent);
})();




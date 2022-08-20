# micx-webanalytics
Website Statistics


## AdWords Template:

```
{lpurl}?_cpg=<EINFÜGEN>&_keyword={keyword}&_device={device}&_loc={loc_physical_ms}
```

## Tags

```
data-ws-cid         Unique Click ID for click recording
```


## Cookie Consent Template


```html
<script src="https://<hostname>/v1/webanalytics/wa.js?subscription_id=demo"></script>

<micx-cookie-consent hidden="hidden">
    <div class="content h-100" style="position: fixed; top:0;left:0;right:0;bottom:0;z-index: 99999;overflow:auto">
        <div style="position:absolute;background-color: #6c757d; opacity: 50%;top:0;left:0;right:0;bottom: 0"></div>
        <div class="container h-100">
            <div class="row h-100">
                <div class="card col position-absolute top-50 start-50 translate-middle col-lg-8 ">

                    <div class="card-body" style="opacity: 100;">
                        <h3 class="card-title">Einwilligung zur Nutzung von Cookies</h3>
                        <p class="card-text">
                            Wir speichern auf Ihrem Gerät anonymisierte Nutzungsdaten. Dies hilft uns,
                            unser Angebot ständig zu verbessern.
                        </p>


                        <p class="card-body text-center">
                            <button data-consent="decline" class="btn-link btn text-muted m-3">Cookies ablehnen</button>
                            <button data-consent="accept" class="btn-primary btn"><span class="fw-bold">Zustimmen und fortfahren</span> </button>
                        </p>
                    </div>
                </div>
                <!-- End Card -->
            </div>
        </div>
    </div>
    <template>
        <!-- third party analytics to mount -->
    </template>
</micx-cookie-consent>
```

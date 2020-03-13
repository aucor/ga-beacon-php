# PHP Google Analytics Beacon

This is a PHP fork of [ga-beacon by Ilya Grigorik](https://github.com/igrigorik/ga-beacon) most of the logic and readme instructions are also by Ilya Grigorik.

Sometimes it is impossible to embed the JavaScript tracking code provided by Google Analytics: the host page does not allow arbitrary JavaScript, and there is no Google Analytics integration. However, not all is lost! **If you can embed a simple image (pixel tracker), then you can beacon data to Google Analytics.** For a great, hands-on explanation of how this works, check out the following guides:

* [Using a Beacon Image for GitHub, Website and Email Analytics](http://www.sitepoint.com/using-beacon-image-github-website-email-analytics/)
(also see FAQ below regarding GitHub)
* [Tracking Google Sheet views with Google Analytics using GA Beacon](http://mashe.hawksey.info/2014/02/tracking-google-sheet-views-with-google-analytics/)

### Requirements

* PHP 7+
* cURL support in PHP (most enviroments have it by default)

### Setup instructions

First, log in to your Google Analytics account and [set up a new property](https://support.google.com/analytics/answer/1042508?hl=en):

* Select "Website", use new "Universal Analytics" tracking
* **Website name:** anything you want (e.g. GitHub projects)
* **WebSite URL: the url of your website that runs PHP
* Click "Get Tracking ID", copy the `UA-XXXXX-X` ID on next page

Next, add the `ga-beacon.php` file to your server

* Get the URL like `https://example.com/ga-beacon.php`

Finally, add a tracking image to the pages you want to track:

* Format for source `https://example.com/ga-beacon.php?account={tracking id}&path={path}`
* {tracking id} is the `UA-XXXXX-X` from Google Analytics
  * You can also add the account straight into file if you only need to use one account and want to protect the endpoint for misuse.
* {path} is an arbitrary path as `/insert/any/path`. For best results specify a meaningful and self-descriptive path. You have to do this manually, the beacon won't automatically record the page path it's embedded on.
  * It is recommended to pass the path through some urlencoding function like PHP's `urlencode('/insert/any/path'))

Add the tracker to website / newsletter / whatever:

```html
<img alt="" src="https://example.com/ga-beacon.php?account=UA-XXXXX-X&path=/blog/hello-world" />
```

**Protip:** It's good idea to add opacity in case browser has difficulty showing the image so there won't be any broken image icons visible.

To de-priorize tracking, add native lazyload (may cause less data collected):
```html
<img loading="lazy" alt="" src="https://example.com/ga-beacon.php?account=UA-XXXXX-X&path=/blog/hello-world" />
```

### FAQ

- **How does this work?** Google Analytics provides a [measurement protocol](https://developers.google.com/analytics/devguides/collection/protocol/v1/devguide) which allows us to POST arbitrary visit data directly to Google servers, and that's exactly what GA Beacon does: we include an image request on our pages which hits the GA Beacon service, and GA Beacon POSTs the visit data to Google Analytics to record the visit. As a result, if you can embed an image, you can beacon data to Google Analytics.

- **Why do we need to proxy?** Google Analytics supports reporting of visit data [via GET requests](https://developers.google.com/analytics/devguides/collection/protocol/v1/reference#transport), but unfortunately we can't use that directly because we need to generate and report a unique visitor ID for each hit - e.g. some pages do not allow us to run JS on the client to generate the ID. To address this, we proxy the request through ga-beacon.appspot.com, which in turn is responsible for generating the unique visitor ID (server generated UUID), setting the appropriate cookies for repeat hits, and reporting the hits to Google Analytics.

- **What about referrals and other visitor information?** Unfortunately the static tracking pixel approach limits the information we can collect about the visit. For example, referral information can't be passed to the tracking pixel because we can't execute JavaScript. As a result, the available metrics are restricted to unique visitors, pageviews, and the User-Agent and IP address of the visitor.

## Debugging

You can debug the requests by visiting the URL that is used in images. If server returns other status than 2xx your environment probably is not meetin the requirements. If there are problems with receiving data in Google Analytics you should check that the domain set in Google Analytics is the same where this ga-beacon is hosted. You can easily see all the values passed from variable `$this->debug`.

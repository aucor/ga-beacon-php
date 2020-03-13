<?php
/**
 * GA Beacon
 *
 * Send pageview to Google Analytics via img file.
 *
 * @version 1.0.0
 * @see https://github.com/aucor/ga-beacon-php
 * @see https://github.com/igrigorik/ga-beacon
 */

class GA_Beacon {

  // set explicit account if needed
  private $account = '';

  private $beacon_url = 'https://www.google-analytics.com/collect';
  private $debug = [];


  function __construct() {
    if ($this->is_valid_request()) {
      $this->send_pageview();
    }
  }

  /**
   * Validate request
   *
   * @return bool
   */
  protected function is_valid_request() {

    if (!isset($_SERVER['REQUEST_URI']) || empty($_SERVER['REQUEST_URI'])) {
      throw new Exception('Missing request parameters');
      return false;
    }

    if (!isset($_GET['path'])) {
      throw new Exception('Missing path of the page view');
      return false;
    }

    if (!isset($_GET['account']) && empty($this->account)) {
      throw new Exception('Missing account');
      return false;
    }

    return true;

  }

  /**
   * Get account
   *
   * @return string account UA code
   */
  protected function get_account() {

    if (empty($this->account)) {
      $this->account = $_GET['account'];
    }
    return trim($this->account);

  }

  /**
   * Get path
   *
   * @return string account UA code
   */
  protected function get_path() {

    $path = trim(urldecode($_GET['path']));
    if (empty($path)) {
      $path = '/';
    }
    return $path;

  }

  /**
   * Get UUID
   *
   * @return string randomized uuid
   */
  protected function get_uuid() {

    if (isset($_COOKIE['cid']) && !empty($_COOKIE['cid'])) {
      return (string) $_COOKIE['cid'];
    }

    $cid = (string) random_bytes(16);
    $_COOKIE['cid'] = $cid;
    setcookie('cid', $cid, time()+24*60*60);

    return $cid;

  }

  /**
   * Get IP
   *
   * @return string ip address
   */
  protected function get_ip() {

    if (isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR'])) {
      return $_SERVER['REMOTE_ADDR'];
    }
    return '';

  }

  /**
   * Send GA Pageview
   */
  protected function send_pageview() {

    // GA Protocol reference: https://developers.google.com/analytics/devguides/collection/protocol/v1/reference
    $payload = [
      'v'        => 1,                      // protocol version = 1
      't'        => 'pageview',             // hit type
      'tid'      => $this->get_account(),   // tracking / property ID
      'cid'      => $this->get_uuid(),      // unique client ID (server generated UUID)
      'dp'       => $this->get_path(),      // page path
      'uip'      => $this->get_ip()         // IP address of the user
    ];

    // allow query param override to report arbitrary values to GA
    foreach ($_GET as $key => $value) {
      if (!in_array($key, ['account', 'path', 'tid'])) {
        $payload[$key] = $value;
      }
    }

    $headers = [
      'User-Agent: ' . isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
      'Content-Type: application/x-www-form-urlencoded'
    ];

    $this->debug['payload'] = $payload;
    $this->debug['headers'] = $headers;

    $ch = curl_init($this->beacon_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $this->debug['status'] = $status;
    $this->debug['response'] = $response;

    // debug
    // var_dump($this->debug);
    // die();

    if ($status >= 200 & $status < 300) {

      // send image metadata to browser
      header('Content-Type: image/gif');
      header('Cache-Control: no-cache, no-store, must-revalidate, private');
      header('Expires: ' . gmdate('D, d M Y H:i:s \G\M\T', time()));
      header('CID: ' . $this->get_uuid());

      // send 1x1 transparent gif
      echo base64_decode('R0lGODlhAQABAJAAAP8AAAAAACH5BAUQAAAALAAAAAABAAEAAAICBAEAOw==');
      exit;

    } else {
      throw new Exception((string) $response);
    }

  }

}

// self init
new GA_Beacon();

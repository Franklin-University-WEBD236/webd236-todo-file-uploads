<?php
class View {
  
  public function __construct() {
    
  }
  
  public function flash($message) {
    $_SESSION['flash'] = $message;
  }

  public function redirect($url) {
    header("Location: $url");
    exit();
  }

  public function redirectRelative($url) {
    redirect(url($url));
  }

  public function url($url) {
    $requestURI = explode('/', $_SERVER['REQUEST_URI']);
    $scriptName = explode('/', $_SERVER['SCRIPT_NAME']);

    $dir = array();
    for ($i = 0; $i < sizeof($scriptName); $i++) {
      if ($requestURI[$i] == $scriptName[$i]) {
        $dir[] = $requestURI[$i];
      } else {
        break;
      }
    }
    return implode('/', $dir) . '/' . $url;
  }

  private function __importTemplate($matches) {
    $fileName = trim($matches[1]);
    if (!file_exists($fileName)) {
      die("Template $fileName doesn't exist.");
    }
    $contents = file_get_contents($fileName);
    $contents = preg_replace_callback('/%%\s*(.*)\s*%%/', array($this, '__importTemplate'), $contents);
    return $contents;
  }

  private function __resolveRelativeUrls($matches) {
    return $this->url($matches[1]);
  }

  private function __cacheName($view) {
    $cachePath = explode('/', $view);
    $idx = sizeof($cachePath) - 1;
    $cachePath[$idx] = 'cache_' . $cachePath[$idx];
    return implode('/', $cachePath);
  }

  public function renderTemplate($view, $params) {
    $useCache = false;

    if (!file_exists($view)) {
      die("File $view doesn't exist.");
    }
    # do we have a cached version?
    clearstatcache();
    $cacheName = $this->__cacheName($view);
    if ($useCache && file_exists($cacheName) && (filemtime($cacheName) >= filemtime($view))) {
      $contents = file_get_contents($cacheName);
    } else {
      # we need to build the file (doesn't exist or template is newer)
      $contents = $this->__importTemplate(array('unused', $view));

      $contents = preg_replace_callback('/@@\s*(.*)\s*@@/U', array($this, '__resolveRelativeUrls'), $contents);

      $patterns = array(
        array('src' => '/{{/', 'dst' => '<?php echo('),
        array('src' => '/}}/', 'dst' => '); ?>'),
        array('src' => '/\[\[/', 'dst' => '<?php '),
        array('src' => '/\]\]/', 'dst' => '?>')
      );
      foreach ($patterns as $pattern) {
        $contents = preg_replace($pattern['src'], $pattern['dst'], $contents);
      }
      file_put_contents($cacheName, $contents);
    }
    extract($params);
    ob_start();
    eval("?>" . $contents);
    $result = ob_get_contents();
    ob_end_clean();
    echo $result;
  }

  
}
?>
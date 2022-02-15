<?php
/**
 * Pico Cache plugin
 * Name "PicoZCache" is to be loaded as last.
 *
 * @author Maximilian Beck before 2.0, Nepose since 2.0
 * @link https://github.com/Nepose/PicoCache
 * @license http://opensource.org/licenses/MIT
 * @version 2.0
 */
class PicoZCache extends AbstractPicoPlugin
{

    const API_VERSION=2;
    protected $dependsOn = array();

    private $cacheDir = 'content/cache/';
    private $cacheTime = 604800; // 60*60*24*7, seven days
    private $doCache = true;
    private $cacheXHTML = false;
    private $cacheIgnoreUrlRegex = null;
    private $cacheFileName;

    public function onConfigLoaded(array &$settings)
    {
        global $config;
        if (isset($config['cache_dir'])) {

            // ensure cache_dir ends with '/'
            $lastchar = substr($config['cache_dir'], -1);
            if ($lastchar !== '/') {
                $config['cache_dir'] = $config['cache_dir'].'/';
            }
            $this->cacheDir = $config['cache_dir'];
        }
        if (isset($config['cache_time'])) {
            $this->cacheTime = $config['cache_time'];
        }
        if (isset($config['cache_enabled'])) {
            $this->doCache = $config['cache_enabled'];
        }
        if (isset($config['cache_xhtml_output'])) {
            $this->cacheXHTML = $config['cache_xhtml_output'];
        }
        if (isset($config['cache_ignore_url_regex'])) {
            $this->cacheIgnoreUrlRegex = $config['cache_ignore_url_regex'];
        }
    }

    public function onRequestUrl(&$url)
    {
        if ($this->cacheIgnoreUrlRegex && preg_match($this->cacheIgnoreUrlRegex, $url)) {
            // Skip cache for url matching ignore regex
            return;
        }
        $this->cacheFileName = $this->cacheDir . preg_replace('/[^A-Za-z0-9_\-]/', '_', $url) . '.html';
        $name = $url == "" ? "index" : $url;
        $this->cacheFileName = $this->cacheDir . preg_replace('/[^A-Za-z0-9_\-]/', '_', $name) . '.html';

        //if a cached file exists and the cacheTime is not expired, load the file and exit
        if ($this->doCache && file_exists($this->cacheFileName) && (time() - filemtime($this->cacheFileName)) < $this->cacheTime) {
            header("Expires: " . gmdate("D, d M Y H:i:s", $this->cacheTime + filemtime($this->cacheFileName)) . " GMT");
            ($this->cacheXHTML) ? header('Content-Type: application/xhtml+xml') : header('Content-Type: text/html');
            die(readfile($this->cacheFileName));
        }
    }

    public function on404ContentLoaded(&$rawContent)
    {
        //don't cache error pages. This prevents filling up the cache with non existent pages
        $this->doCache = false;
    }

    public function onPageRendered(&$output)
    {
        if ($this->doCache && $this->cacheFileName) {
            if (!is_dir($this->cacheDir)) {
                mkdir($this->cacheDir, 0755, true);
            }
            file_put_contents($this->cacheFileName, $output);
        }
    }

}

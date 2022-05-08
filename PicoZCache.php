<?php
/**
 * Pico Cache plugin
 * Name "PicoZCache" is to be loaded as last.
 *
 * @author Maximilian Beck before 2.0, Nepose since 2.0
 * @link https://github.com/Nepose/PicoCache
 * Improvements by various authoors, gathered by https://github.com/ohnonot (2022)
 * @license http://opensource.org/licenses/MIT
 * @version 2.0
 */
class PicoZCache extends AbstractPicoPlugin
{

    const API_VERSION=2;
    protected $dependsOn = array();

    private $doCache = true;
    private $FileName;
    protected $enabled = false;

    public function onConfigLoaded(array &$config)
    {
        // ensure cache_dir ends with '/'
        $this->Dir = rtrim($this->getPluginConfig('dir', 'content/zcache/','content/zcache/'),'/').'/';
        $this->Time = $this->getPluginConfig('expires', 0);
        $this->XHTML = $this->getPluginConfig('xhtml_output', false);
        $this->Exclude = $this->getPluginConfig('exclude', null);
        $this->ExcludeRegex = $this->getPluginConfig('exclude_regex', null);
        $this->IgnoreQuery = $this->getPluginConfig('ignore_query', false);
        $this->IgnoreQueryExclude = $this->getPluginConfig('ignore_query_exclude', null);
    }

    public function onRequestUrl(&$url)
    {
        $name = $url == "" ? "index" : $url;

        // Skip cache for url matching an excluded page
    	if($this->Exclude && in_array($name,$this->Exclude)) {
            $this->doCache = false;
            return;
        }
        // Skip cache for url matching exclude regex // untested!
        if ($this->ExcludeRegex && preg_match($this->ExcludeRegex, $url)) {
            $this->doCache = false;
            return;
        }
        // add query to name if so configured
    	if( $this->IgnoreQuery === false || in_array($name,$this->IgnoreQueryExclude) ) {
            $query = (!empty($_GET)) ? '__'.md5(serialize($_GET)) : null;
            $name = $name.$query;
        }

        //replace any character except numbers and digits with a '-' to form valid file names
        $this->FileName = $this->Dir . preg_replace('/[^A-Za-z0-9_\-]/', '_', $name) . '.html';

        //if a cached file exists and the cacheTime is not expired, load the file and exit
        if (file_exists($this->FileName)) {
            if ($this->Time > 0) {
                //~ echo time().'<br/>';
                //~ echo filemtime($this->FileName).'<br/>';
                //~ echo $this->Time.'<br/>';
                header("Expires: " . gmdate("D, d M Y H:i:s", $this->Time + filemtime($this->FileName)) . " GMT");
                ($this->XHTML) ? header('Content-Type: application/xhtml+xml') : header('Content-Type: text/html');
                if(time() - filemtime($this->FileName) > $this->Time) return;
            }
            die(readfile($this->FileName));
        }
    }

    public function on404ContentLoaded(&$rawContent)
    {
        //don't cache error pages. This prevents filling up the cache with non existent pages
        $this->doCache = false;
    }

    public function onPageRendered(&$output)
    {
        if ($this->doCache) {
            if (!is_dir($this->Dir)) {
                mkdir($this->Dir, 0755, true);
            }
            file_put_contents($this->FileName, $output);
        }
    }

}

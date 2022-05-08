# Pico Cache

Plugin adds simple function of server-side caching your website to plain HTML / XHTML (.html with correct header) type.

## Installation

To install the Pico Cache plugin, simply download the `PicoZCache.php` and put it in the plugins directory
`{picoInstallation}/plugins/`.

## Configuration

Default values are shown. Arrays have no default values.

The plugin will cache pages with different queries individually. Page queries are little strings added to a
page request:  
`http://dom.com/tags?q=picocms&page=3` Here, the page is `tags` and the query is `q=picocms&page=3`.
 
```yaml
PicoZCache:
    enabled: false

    dir: content/zcache        # Directory where cache should be saved. If your PHP has permission to do so,
                               # this can be a directory outside your webroot (leading / vs. no leading /).

    expires: 0                 # Interval between caching (cached files will be overwritten after that many seconds,
                               # also sends Expires header to client). The default 0 is to never overwite cached files.
                               # This is useful if you have some method of watching for changes (see script below).

    xhtml_output: false        # If true, XHTML Content-Type header will be sent when loading cache page

    exclude:                   # Never cache these pages (as requested by browser, but without queries)
        - contact
        - feed
        - index                # special name, also resolves to /

    exclude_regex: no default  # will be compared to $url with preg_match() (as above, but without the index exception)
                               # you can use both exclude and exclude_regex, but exclude will be evaluated first.

    ignore_query: false        # If enabled, pages with different queries will not be cached individually.
    ignore_query_exclude:
        - tags                 # These pages will be cached individually with their queries
        - search
        - index
```

## Cache clearing

To clear the cache, remove the files from the cache folder, or delete the whole cache folder (it will be recreated).

I recommend to set up a little daemon script that will clear out the cache whenever content changes, e.g.:

~~~ sh
#!/bin/sh

# provide first the dir to be watched, second the dir to be cleared
dir="$1"; cache="$2"

printf "Directory to watch: %s\nCache to delete: %s\n" "$dir" "$cache"

delete() {
    printf '%s: %s\n' "Events" "$1"
    [ "${1##*,}" = ISDIR ] && printf "Not interested in mere directories.\n" && return
    printf "File changes detected! Let's delete stuff...\n"
    rm -v "$cache"/*.html 2>&1
}

while :; do

result="$(inotifywait --event modify --event create --event delete --format '%e' "$dir")"
delete "$result"

done
~~~

## Common Pitfalls

+ Make sure the directory in which the cache directory shall be created has the appropriate permissions.
+ Make sure the cache directory, if created manually, has the right permission for the server to read and write files to.
+ If your site uses multiple protocols, set the *base_url* parameter in your *config.yml* to be protocol independent, like `base_url: '//example.com';`

## Difference between this plugin and Twig caching function

Pico CMS offers Twig caching, which caches Twig templates as plain PHP files. Of course this omits stage of Twig parsing, but such cache still requires Pico to enable all other dependencies, libraries, plugins and do Markdown parsing.

On the other hand comes this plugin, which caches entire pages to HTML files. This omits both parsing by Twig and Parsedown and additionally lets Pico stop doing its job halfway and load the HTML file immediately when the URL address is known. The plugin just saves HTML source of every visited page at first visit so on subsequent visits this HTML source will be shown instead of parsing the content.

On my system the decrease in response time is staggering, almost ten-fold.

## Credits

Forked and re-forked from these repos:  
https://github.com/glumb/pico_cache  
https://github.com/Nepose/pico_cache  
https://github.com/Tetras-Libre/pico_cache


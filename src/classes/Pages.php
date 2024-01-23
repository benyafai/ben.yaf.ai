<?php

declare(strict_types=1);

namespace Yafai;

use Parsedown;
use Yafai\API;

/**
 * Builds Markdown files into pages.
 */
class Pages
{
    public $Parsedown;
    public $API;
    public $siteMeta;
    public array $allPosts;
     /**
     * Constructor
     */
    public function __construct(Parsedown $Parsedown, API $API)
    {
        $this->Parsedown = $Parsedown;
        $this->API = $API;
        $this->siteMeta = include_once(__DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "config.php");
    }

    /**
     * Render our markdown file into a page.
     *
     * @param object $request  The request.
     * @param object $response The response.
     * @param string $page     The markdown filename to render.
     *
     * @return object
     */
    public function renderPage(object $request, object $response, string $page = null): object
    {
        if ($page) {
            $page = strtolower($page);
            $pagePath = __DIR__ . "/../../pages/$page.md";
            $redirects = require_once(__DIR__ . "/../../pages/.redirects.php");
            if (isset($redirects->$page)) {
                $pagePath = __DIR__ . "/../../pages/" . $redirects->$page . ".md";
            }
            if (file_exists($pagePath)) {
                $post = file_get_contents($pagePath);
                $post = $this->parseMetaData($post);
                $markdown = $this->Parsedown->text($post->markdown);
                $markdown = str_replace("href=\"/", "href=\"https://" . $_SERVER["HTTP_HOST"] . "/", $markdown);
                $markdown = $this->myShortCodes($markdown, filemtime($pagePath));
                return $request->getAttribute("view")->render($response, "markdown.phtml", [
                    "markdown" => $this->Parsedown->text($markdown),
                    "postMeta" => $post->postMeta,
                    "siteMeta" => $this->siteMeta,
                    "sharelink" => $page == "index" ? false : true,
                ]);
            }
        }
        return $request->getAttribute("view")->render($response, "404.phtml")->withStatus(404);
    }

    /**
     * Parse our posts
     */
    public function parsePosts($furtherPath = null)
    {
        $path = __DIR__ . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "pages";
        if (!is_null($furtherPath)) {
            $path .= DIRECTORY_SEPARATOR . $furtherPath;
        }
        $files = array_diff(scandir($path), array('.', '..', '.redirects.php', 'index.md', 'now.md'));
        foreach ($files as $file) {
            if (!is_dir($path . DIRECTORY_SEPARATOR . $file)) {
                $post = file_get_contents($path . DIRECTORY_SEPARATOR . $file);
                $post = $this->parseMetaData($post);
                if (isset($post->postMeta->Draft) && $post->postMeta->Draft == "true") {
                    continue;
                } else {
                    $modified = isset($post->postMeta->Date) ? strtotime($post->postMeta->Date) : filemtime($path . DIRECTORY_SEPARATOR . $file);
                    $markdown = $this->Parsedown->text($post->markdown);
                    $markdown = str_replace("href=\"/", "href=\"https://" . $_SERVER["HTTP_HOST"] . "/", $markdown);
                    $markdown = $this->myShortCodes($markdown, filemtime($path . DIRECTORY_SEPARATOR . $file));
                    $this->allPosts[$modified] = (object) [
                        "file" => ( !is_null($furtherPath) ? $furtherPath . DIRECTORY_SEPARATOR : null ) . str_replace(".md", "", $file),
                        "date" => $modified,
                        "path" => $path . DIRECTORY_SEPARATOR . $file,
                        "postMeta" => $post->postMeta,
                        "markdown" => $markdown,
                    ];
                }
            } else {
                // echo ($path . DIRECTORY_SEPARATOR . $file);
                $this->parsePosts($file);
            }
        }
        // ksort($posts);

        // return (object) $posts;
    }

    /**
     * Convert our curly brace images to inline SVG
     *
     * @param string $markdown The markdown input
     * @param int    $modified The time the ffile was last updated
     *
     * @return string
     */
    public function myShortCodes(string $markdown, int $modified = 0): string
    {
        preg_match_all('({([a-zA-Z0-9- :]*)})', $markdown, $matches);
        foreach ($matches[1] as $match) {
            if ($match == 'mastoStatus') {
                $mastoStatus = '<div id="status">';
                $mastoStatus .= $this->API->getMastodonStatus(true);
                $mastoStatus .= '</div>';
                $markdown = str_replace('{' . $match . '}', $mastoStatus, $markdown);
            }
            if ($match == 'recentPosts') {
                $recentPosts = '';
                $this->parsePosts();
                krsort($this->allPosts);
                foreach ($this->allPosts as $post) {
                    $recentPosts .= $this->Parsedown->text('_' . date("d M Y", $post->date) . ' •_ ' .
                    '[' . $post->postMeta->Title . '](https://' . $_SERVER['HTTP_HOST'] . '/' . $post->file
                    . ')');
                }
                $markdown = str_replace('{' . $match . '}', $recentPosts, $markdown);
            }
            if ($match == 'lastUpdated') {
                $lastUpdated = '/Now was more like <span title="';
                $lastUpdated .= date("Y-m-d H:i:s", $modified);
                $lastUpdated .= '">';
                $lastUpdated .= $this->howLongAgo($modified);
                $lastUpdated .= '</span>';
                $markdown = str_replace('{' . $match . '}', $lastUpdated, $markdown);
            }
            if (file_exists(__DIR__ . "/../svg/$match.svg")) {
                $markdown = str_replace('{' . $match . '}', file_get_contents(__DIR__ . "/../svg/$match.svg"), $markdown);
            }
        }
        return $markdown;
    }

    /**
     * Parse our metadata
     *
     * @param string $markdown The page we are parsing
     *
     * @return object
     */
    public function parseMetaData($markdown): object
    {
        if (substr($markdown, 0, 3) == "---") {
            $endOfMeta = strpos($markdown, "---", 1);
            $meta = substr($markdown, 4, $endOfMeta - 5);
            $meta = explode("\n", $meta);
            $postMeta = new \stdClass();
            foreach ($meta as $key => $value) {
                preg_match('/^(\w*):\s?(.*)/', $value, $meta_array);
                list($fullString, $metaKey, $metaValue) = $meta_array;
                $postMeta->$metaKey = $metaValue;
                unset($meta[$key]);
            }
            $markdown = substr($markdown, $endOfMeta + 3);
        }
        return (object) [
            "markdown" => $markdown,
            "postMeta" => $postMeta,
        ];
    }

    /**
     * Return our page list as an RSS feed.
     *
     * @param object $request  The request.
     * @param object $response The response.
     *
     * @return object
     */
    public function rss(object $request, object $response): object
    {
        $rss = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<rss version=\"2.0\" xmlns:dc=\"http://purl.org/dc/elements/1.1/\" xml:base=\"https://"
            . $_SERVER["HTTP_HOST"] . "\" xmlns:atom=\"http://www.w3.org/2005/Atom\">
    <channel> 
        <title>" . $this->siteMeta->siteTitle  . "</title>
        <link>https://" . $_SERVER["HTTP_HOST"] . "</link>
        <atom:link href=\"https://" . $_SERVER["HTTP_HOST"] . "/rss\" rel=\"self\" type=\"application/rss+xml\"/>
        <description>" . $this->siteMeta->siteDescription . "</description>
        <language>en-gb</language>";
        $this->parsePosts();
        krsort($this->allPosts);
        foreach ($this->allPosts as $post) {
            $rss .= "
        <item>
            <title>" . $post->postMeta->Title . "</title>
            <pubDate>" . date("r", $post->date) . "</pubDate>
            <link>https://" . $_SERVER["HTTP_HOST"] . "/" . $post->file . "</link>
            <description>" . str_replace("<", "&lt;", str_replace(">", "&gt;", $post->markdown)) . "</description>
            <dc:creator>" . $this->siteMeta->siteAuthor . "</dc:creator>
            <guid>https://" . $_SERVER["HTTP_HOST"] . "/" . $post->file . "</guid>
        </item>";
        }
        $rss .= "
    </channel>
</rss>";
        $response->getBody()->write($rss);
        return $response->withHeader("Content-Type", "application/xml");
    }

    /**
     * Convert a timestamp to a 'time ago'
     *
     * @param string $timestamp How long ago was this $timestamp.
     *
     * @return string
     */
    public function howLongAgo($timestamp): string
    {
        $time_difference = time() - $timestamp; // strtotime($timestamp);
        if ($time_difference < 1) {
            return 'less than 1 second ago';
        }
        $condition = array( 12 * 30 * 24 * 60 * 60  =>  'year',
                            30 * 24 * 60 * 60       =>  'month',
                            24 * 60 * 60            =>  'day',
                            60 * 60                 =>  'hour',
                            60                      =>  'minute',
                            1                       =>  'second'
        );
        foreach ($condition as $secs => $str) {
            $d = $time_difference / $secs;
            if ($d >= 1) {
                $t = round($d);
                return  $t . ' ' . $str . ( $t > 1 ? 's' : '' ) . ' ago';
            }
        }
    }

    /**
     * Render our homepage markdown file into a page.
     *
     * @param object $request  The request.
     * @param object $response The response.
     *
     * @return object
     */
    public function renderHomePage(object $request, object $response): object
    {
        return $this->renderPage($request, $response, "index");
    }
}

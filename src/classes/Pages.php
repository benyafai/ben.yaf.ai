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
     /**
     * Gathers OAUTH data ready to manage requests.
     */
    public function __construct(Parsedown $Parsedown, API $API)
    {
        $this->Parsedown = $Parsedown;
        $this->API = $API;
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
            $pagePath = __DIR__ . "/../../pages/$page.md";

            if (file_exists($pagePath)) {
                $markdown = file_get_contents($pagePath);
                $markdown = $this->Parsedown->text($markdown);
                $markdown = $this->myShortCodes($markdown, filemtime($pagePath));
                return $request->getAttribute("view")->render($response, "markdown.phtml", [
                    "markdown" => $this->Parsedown->text($markdown),
                    "sharelink" => $page == "index" ? false : true,
                ]);
            }
        }
        return $request->getAttribute("view")->render($response, "404.phtml", [
            "four0four" => true,
        ])->withStatus(404);
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
}

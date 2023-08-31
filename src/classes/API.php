<?php

declare(strict_types=1);

namespace Yafai;

use Parsedown;

/**
 * API class
 */
class API
{
    protected $OAUTH;
    protected $Parsedown;
     /**
     * Gathers OAUTH data ready to manage requests
     */
    public function __construct(Parsedown $Parsedown)
    {
        $this->OAUTH = json_decode(file_get_contents(__DIR__ . '/../auth/keys.json'));
        $this->Parsedown = $Parsedown;
    }

     /**
     * Talks to the mastodon API
     *
     * @param string  $endpoint The API endpoint to query.
     * @param string  $method   The curl method, defaults to GET.
     * @param bool    $auth     Specify if we should provide auth for this request.
     * @param array   $data     An optional array of data to post.
     *
     * @return array
     */
    public function getData(string $endpoint, string $method = 'GET', bool $auth = false, array $data = null)
    {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $endpoint);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $headers = [];
        if ($auth) {
            $headers[] = 'Authorization: Bearer ' . $this->OAUTH->mastodon->access;
        }
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        }
        if ($headers) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        }
        $reply = curl_exec($curl);
        if (!$reply) {
            return json_encode(['ok' => false, 'curl_error_code' => curl_errno($curl), 'curl_error' => curl_error($curl)]);
        }
        curl_close($curl);
        $response = json_decode($reply);
        return $response;
    }

     /**
     * Gets the most recent mastodon status, unless an ID is specified
     *
     * @param bool   $includeInteractions Choose if we should fetch favourites.
     * @param string $id                  The id of the status to fetch
     *
     * @return string
     */
    public function getMastodonStatus(bool $includeInteractions = false, string $id = null): string
    {
        $statuses = $this->getData($this->OAUTH->mastodon->uri . '/api/v1/accounts/' . $this->OAUTH->mastodon->myaccount . '/statuses');
        foreach ($statuses as $status) {
            if (!$status->in_reply_to_id && $status->content) {
                $formatted = $this->replaceEmojis($status);
                foreach ($status->media_attachments as $media_attachment) {
                    if ($media_attachment->type == 'image') {
                        $formatted .= '<div title="' . $media_attachment->description . '"
                            style="background-image:url(' . $media_attachment->url . '); display: inline-block; 
                            width: 100%; aspect-ratio: 16 / 9; background-size: cover; border-radius: 1em 0.2em 1.3em 0.6em;
                            background-position-y: ' . ($media_attachment->meta->focus->y * 55) . '%; "></div>';
                    }
                }
                if ($includeInteractions) {
                    $favourites = $this->getData($this->OAUTH->mastodon->uri . '/api/v1/statuses/' . $status->id . '/favourited_by');
                    $favourites = $this->styleFavourites($favourites);
                    $boosts = $this->getData($this->OAUTH->mastodon->uri . '/api/v1/statuses/' . $status->id . '/reblogged_by');
                    $boosts = $this->styleBoosts($boosts);
                    if (count($favourites) > 0 || count($boosts) > 0) {
                        $formatted .= '<div class="interactions">';
                        $formatted .= implode('', $favourites);
                        $formatted .= implode('', $boosts);
                        $formatted .= '</div>';
                    }
                }
                return $formatted;
            }
        return '';
        }
    }

     /**
     * Replaces empoji shortcodes with inline images
     */
    public function replaceEmojis($status)
    {
        $formatted = $status->content;
        if (count($status->emojis) > 0) {
            foreach ($status->emojis as $emoji) {
                $formatted = str_replace(
                    ':' . $emoji->shortcode . ':',
                    '<img class="emoji" loading="lazy" src="' . $emoji->url . '" alt="' . $emoji->shortcode . '"/>',
                    $formatted
                );
            }
        }
        return $formatted;
    }

    /**
     * Populates a list of favourites as image icons with links.
     */
    public function styleFavourites($favourites)
    {
        $formatted = [];
        if (count($favourites) > 0) {
            foreach ($favourites as $favourite) {
                $icon = '<a href="' . $favourite->url . '" title="Favourited by ' . $favourite->acct . '"><img loading="lazy" alt="' .
                        $favourite->acct . '" height="2rem" width="2rem" src="' . $favourite->avatar . '" /></a>';
                $formatted[] = $icon;
            }
        }
        return $formatted;
    }

    /**
     * Populates a list of boosts as image icons with links.
     */
    public function styleBoosts($boosts)
    {
        $formatted = [];
        if (count($boosts) > 0) {
            foreach ($boosts as $boost) {
                $icon = '<a href="' . $boost->url . '" title="Boosted by ' . $boost->acct . '"><img loading="lazy" alt="' .
                        $boost->acct . '" height="2rem" width="2rem" src="' . $boost->avatar . '" /></a>';
                $formatted[] = $icon;
            }
        }
        return $formatted;
    }

     /**
     * Process a status.lol webhook
     *
     * @param object $request  The request.
     * @param object $response The response.
     *
     * @return object
     */
    public function statuslol(object $request, object $response): object
    {
        $contentType = $request->getHeaderLine('Content-Type');
        if (strstr($contentType, 'application/json')) {
            $post = json_decode(file_get_contents('php://input'));
            if (json_last_error() === JSON_ERROR_NONE) {
                $visibility = 'public';
                    // 'Direct' means sending message as a private message.
                    // The four tiers of visibility for toots are public , unlisted, private, and direct

                $language = 'en'; // en for English, zh for Chinese, de for German etc.
                $statusText = $post->status_emoji ? $post->status_emoji . ' ' : '';
                $statusText .= $post->status_text;

                $statusData = [
                    'status'     => $statusText,
                    'visibility' => $visibility,
                    'language'   => $language,
                ];

                $this->getData($this->OAUTH->mastodon->uri . '/api/v1/statuses', 'POST', true, $statusData);
                return $response
                    ->withStatus(200);
            }
        }
        return $response
            ->withStatus(406);
    }
}

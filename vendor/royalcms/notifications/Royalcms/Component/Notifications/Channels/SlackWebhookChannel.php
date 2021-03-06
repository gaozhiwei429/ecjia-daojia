<?php

namespace Royalcms\Component\Notifications\Channels;

use GuzzleHttp\Client as HttpClient;
use Royalcms\Component\Notifications\Notification;
use Royalcms\Component\Notifications\Messages\SlackMessage;
use Royalcms\Component\Notifications\Messages\SlackAttachment;

class SlackWebhookChannel
{
    /**
     * The HTTP client instance.
     *
     * @var \GuzzleHttp\Client
     */
    protected $http;

    /**
     * Create a new Slack channel instance.
     *
     * @param  \GuzzleHttp\Client  $http
     * @return void
     */
    public function __construct(HttpClient $http)
    {
        $this->http = $http;
    }

    /**
     * Send the given notification.
     *
     * @param  mixed  $notifiable
     * @param  \Royalcms\Component\Notifications\Notification  $notification
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function send($notifiable, Notification $notification)
    {
        if (! $url = $notifiable->routeNotificationFor('slack')) {
            return;
        }

        $message = $notification->toSlack($notifiable);

        $this->http->post($url, $this->buildJsonPayload($message));
    }

    /**
     * Build up a JSON payload for the Slack webhook.
     *
     * @param  \Royalcms\Component\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function buildJsonPayload(SlackMessage $message)
    {
        $optionalFields = array_filter([
            'username' => data_get($message, 'username'),
            'icon_emoji' => data_get($message, 'icon'),
            'channel' => data_get($message, 'channel'),
        ]);

        return array_merge([
            'json' => array_merge([
                'text' => $message->content,
                'attachments' => $this->attachments($message),
            ], $optionalFields),
        ], $message->http);
    }

    /**
     * Format the message's attachments.
     *
     * @param  \Royalcms\Component\Notifications\Messages\SlackMessage  $message
     * @return array
     */
    protected function attachments(SlackMessage $message)
    {
        return collect($message->attachments)->map(function ($attachment) use ($message) {
            return array_filter([
                'color' => $message->color(),
                'title' => $attachment->title,
                'text' => $attachment->content,
                'title_link' => $attachment->url,
                'fields' => $this->fields($attachment),
            ]);
        })->all();
    }

    /**
     * Format the attachment's fields.
     *
     * @param  \Royalcms\Component\Notifications\Messages\SlackAttachment  $attachment
     * @return array
     */
    protected function fields(SlackAttachment $attachment)
    {
        return collect($attachment->fields)->map(function ($value, $key) {
            return ['title' => $key, 'value' => $value, 'short' => true];
        })->values()->all();
    }
}

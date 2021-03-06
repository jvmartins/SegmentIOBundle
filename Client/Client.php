<?php
namespace Vouchedfor\SegmentIOBundle\Client;

use Vouchedfor\SegmentIOBundle\Consumer\AbstractConsumer;
use Vouchedfor\SegmentIOBundle\Consumer\Socket;

/**
 * Class Client
 */
class Client
{
    const VERSION = '1.1.3';

    private $consumer;

    /**
     * Create a new analytics object with your app's secret key
     *
     * @param AbstractConsumer $consumer
     * @param array $options
     */
    public function __construct(AbstractConsumer $consumer, $options = array())
    {
        $this->consumer = $consumer;
    }

    public function __destruct()
    {
        $this->consumer->__destruct();
    }

    /**
     * Tracks a user action
     *
     * @param  array $message
     * @return boolean whether the track call succeeded
     */
    public function track(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'track';

        return $this->consumer->track($message);
    }

    /**
     * Tags traits about the user.
     *
     * @param  array $message
     * @return boolean whether the track call succeeded
     */
    public function identify(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'identify';

        return $this->consumer->identify($message);
    }

    /**
     * Tags traits about the group.
     *
     * @param  array $message
     * @return boolean whether the group call succeeded
     */
    public function group(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'group';

        return $this->consumer->group($message);
    }

    /**
     * Tracks a page view.
     *
     * @param  array $message
     * @return boolean whether the page call succeeded
     */
    public function page(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'page';

        return $this->consumer->page($message);
    }

    /**
     * Tracks a screen view.
     *
     * @param  array $message
     * @return boolean whether the screen call succeeded
     */
    public function screen(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'screen';

        return $this->consumer->screen($message);
    }

    /**
     * Aliases from one user id to another
     *
     * @param  array $message
     * @return boolean whether the alias call succeeded
     */
    public function alias(array $message)
    {
        $message = $this->message($message);
        $message['type'] = 'alias';

        return $this->consumer->alias($message);
    }

    /**
     * Flush any async consumers
     */
    public function flush()
    {
        if (!method_exists($this->consumer, 'flush')) {
            return;
        }
        $this->consumer->flush();
    }

    /**
     * Formats a timestamp by making sure it is set
     * and converting it to iso8601.
     *
     * The timestamp can be time in seconds `time()` or `microseconds(true)`.
     * any other input is considered an error and the method will return a new date.
     *
     * Note: php's date() 'u' format (for microseconds) has a bug in it
     * it always shows `.000` for microseconds since `date()` only accepts
     * ints, so we have to construct the date ourselves if microtime is passed.
     *
     * @param int $ts - time in seconds (time())
     *
     * @return bool|string
     */
    private function formatTime($ts)
    {
        // time()
        if ($ts == null) {
            $ts = time();
        }
        if (is_integer($ts)) {
            return date('c', $ts);
        }

        // anything else return a new date.
        if (!is_float($ts)) {
            return date('c');
        }

        // fix for floatval casting in send.php
        $parts = explode('.', (string)$ts);
        if (!isset($parts[1])) {
            return date('c', (int)$parts[0]);
        }

        // microtime(true)
        $sec = (int)$parts[0];
        $usec = (int)$parts[1];
        $fmt = sprintf('Y-m-d\TH:i:s%sP', $usec);

        return date($fmt, (int)$sec);
    }

    /**
     * Add common fields to the given `message`
     *
     * @param array $msg
     * @return array
     */

    private function message($msg)
    {
        if (!isset($msg['context'])) {
            $msg['context'] = array();
        }
        if (!isset($msg['timestamp'])) {
            $msg['timestamp'] = null;
        }
        $msg['context'] = array_merge($msg['context'], $this->getContext());
        $msg['timestamp'] = $this->formatTime($msg['timestamp']);
        $msg['messageId'] = self::messageId();

        return $msg;
    }

    /**
     * Generate a random messageId.
     *
     * https://gist.github.com/dahnielson/508447#file-uuid-php-L74
     *
     * @return string
     */

    private static function messageId()
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Add the segment.io context to the request
     * @return array additional context
     */
    private function getContext()
    {
        return array(
            'library' => array(
                'name' => 'analytics-php',
                'version' => self::VERSION,
            ),
        );
    }
}

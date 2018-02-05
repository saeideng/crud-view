<?php
namespace CrudView\Dashboard\Module;

use Cake\Utility\Hash;
use InvalidArgumentException;

class LinkItem extends DashboardModule
{
    /**
     * Constructor
     *
     * @param string|array $title The content to be wrapped by `<a>` tags.
     *   Can be an array if $url is null. If $url is null, $title will be used as both the URL and title.
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array $options Array of options and HTML attributes.
     */
    public function __construct($title, $url, $options = [])
    {
        parent::__construct($title);
        $this->setValid([
            'url',
            'options',
        ]);

        if (empty($title)) {
            throw new InvalidArgumentException('Missing title for LinkItem action');
        }
        if (empty($url)) {
            throw new InvalidArgumentException('Missing url for LinkItem action');
        }

        $this->set('url', $url);
        $this->set('options', $options);
    }

    /**
     * url property setter
     *
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @return string|array
     */
    protected function _setUrl($url)
    {
        if ($url === null || empty($url)) {
            throw new InvalidArgumentException('Invalid url specified for LinkItem');
        }

        return $url;
    }

    /**
     * options property setter
     *
     * @param array $options Array of options and HTML attributes.
     * @return string|array
     */
    protected function _setOptions($options)
    {
        if (empty($options)) {
            $options = [];
        }

        $url = $this->get('url');
        if (!is_array($url)) {
            $isHttp = substr($url, 0, 7) === 'http://';
            $isHttps = substr($url, 0, 8) === 'https://';
            if ($isHttp || $isHttps) {
                $options += ['target' => '_blank'];
            }
        }

        return $options;
    }
}

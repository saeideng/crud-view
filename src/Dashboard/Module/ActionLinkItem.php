<?php
namespace CrudView\Dashboard\Module;

use Cake\Collection\Collection;
use Cake\Utility\Hash;
use InvalidArgumentException;

class ActionLinkItem extends LinkItem
{
    /**
     * Constructor
     *
     * @param string|array $title The content to be wrapped by `<a>` tags.
     *   Can be an array if $url is null. If $url is null, $title will be used as both the URL and title.
     * @param string|array|null $url Cake-relative URL or array of URL parameters, or
     *   external URL (starts with http://)
     * @param array $options Array of options and HTML attributes.
     * @param array $actions Array of ActionItems
     */
    public function __construct($title, $url, $options = [], array $actions = [])
    {
        parent::__construct($title, $url, $options);
        $this->setValid([
            'actions',
        ]);

        $this->set('actions', $actions);
    }

    /**
     * options property setter
     *
     * @param array $actions Array of options and HTML attributes.
     * @return array
     */
    protected function _setActions($actions)
    {
        return (new Collection($actions))->map(function ($value) {
            if ($value instanceof LinkItem) {
                $options = (array)$value->get('options') + ['class' => ['btn btn-default']];
                $value->set('options', $options);

                return $value;
            }
            $value += ['title' => null, 'url' => null, 'options' => ['class' => ['btn btn-default']]];

            return new LinkItem($value['title'], $value['url'], $value['options']);
        })->toArray();
    }
}

<?php
namespace CrudView\Dashboard\Module;

use Cake\Utility\Hash;
use InvalidArgumentException;

class LinkTableModule extends DashboardModule
{
    /**
     * Constructor
     *
     * @param string $title The name of the group
     * @param array $controllerList Array of controller data
     */
    public function __construct($title, array $controllerList = [])
    {
        parent::__construct($title);
        $this->setValid([
            'links',
        ]);

        foreach ($controllerList as $data) {
            $this->addLinks($data);
        }
    }

    /**
     * Adds a link
     *
     * @param array|LinkItem $data LinkItem or array of data that can be used to construct a LinkItem
     * @return $this
     */
    public function addLinks($data)
    {
        $link = $data;
        if (!($link instanceof LinkItem)) {
            $title = Hash::get($data, 'title', null);
            $url = Hash::get($data, 'url', null);
            $options = Hash::get($data, 'options', null);
            $actions = Hash::get($data, 'actions', null);
            if ($actions === null) {
                $link = new LinkItem($title, $url, $options);
            } else {
                $link = new ActionLinkItem($title, $url, $options, $actions);
            }
        }

        $links = $this->get('links');
        $links[] = $link;
        $this->set('links', $links);

        return $this;
    }
}

Customizing the Dashboard Page
==============================

The "dashboard" can be used to display a default landing page for CrudView-powered
admin sites. It is made of several ``\CrudView\Dashboard\Module\DashboardModule``
instances, and can be extended to display items other than what is shipped with CrudView.

To use the "Dashboard", the custom ``DashboardAction`` needs to be mapped:

.. code-block:: php

    public function initialize()
    {
        parent::initialize();

        $this->Crud->mapAction('dashboard', 'CrudView.Dashboard');
    }

Browsing to this mapped action will result in a blank page. To customize it, a
``\CrudView\Dashboard\Dashboard`` can be configured on the ``scaffold.dashboard`` key:

.. code-block:: php

    public function dashboard()
    {
        $dashboard = new \\CrudView\\Dashboard\\Dashboard();
        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }


The ``\CrudView\Dashboard\Dashboard`` instance takes two arguments:

- ``title``: The title for the dashboard view. Defaults to ``Dashboard``.
- ``columns`` A number of columns to display on the view. Defaults to ``1``.

.. code-block:: php

    public function dashboard()
    {
        // setting both the title and the number of columns
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 12);
        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

Adding Modules to the Dashboard
-------------------------------

Any number of modules may be added to the Dashboard. All modules *must* extend the
``\CrudView\Dashboard\Module\DashboardModule`` class.

Modules can be added via the ``Dashboard::addToColumn()`` method. It takes a module
instance and a column number as arguments.

.. code-block:: php

    $someModule = new ModuleClass;
    $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 2);

    // add to the first column
    $dashboard->addToColumn($someModule);

    // configure the column to add to
    $dashboard->addToColumn($someModule, 2);

CrudView ships with the ``LinkListModule`` module by default.

Module\\LinkListModule
~~~~~~~~~~~~~~~~~~~~

This can be used to display links to items in your application or offiste.

.. code-block:: php

    public function dashboard()
    {
        // setting both the title and the number of columns
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 1);
        $dashboard->addToColumn(new \\CrudView\\Dashboard\\Module\\LinkListModule('Important Links'));

        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

In the above examples, only a title to the ``LinkListModule``, which will
show a single subheading for your Dashboard. The ``LinkListModule`` also accepts a
second argument that contains an array of link information. Links containing urls
for external websites will open in a new window by default.



.. code-block:: php

    public function dashboard()
    {
        // setting both the title and the number of columns
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 1);
        $dashboard->addToColumn(new \\CrudView\\Dashboard\\Module\\LinkListModule('Important Links', [
            [
                'title' => 'Example',
                'url' => 'https://example.com',
                'options' => ['optional' => 'array', 'of' => 'Html::link', 'options']
            ],
        ]));

        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

Link information can be presented either via an array - as shown above - or a
``LinkItem`` object:

.. code-block:: php

    public function dashboard()
    {
        // setting both the title and the number of columns
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 1);
        $dashboard->addToColumn(new \\CrudView\\Dashboard\\Module\\LinkListModule('Important Links', [
            new LinkItem('Example', 'https://example.com', ['target' => '_blank']),
        ]));

        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

There is also a special kind of ``LinkItem`` called an ``ActionLinkItem``. This
has an ``actions`` key made of ``LinkItem`` instances.

.. code-block:: php

    public function dashboard()
    {
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 1);
        $dashboard->addToColumn(new \\CrudView\\Dashboard\\Module\\LinkListModule('Title', [
            [
                'title' => 'Posts',
                'url' => ['controller' => 'Posts'],
                'actions' => [
                    [
                        'title' => 'Add',
                        'url' => ['controller' => 'Posts', 'action' => 'add'],
                    ],
                ],
            ],
        ]));

        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

As before, the ``ActionLinkItem`` can be represented via an object.

.. code-block:: php

    public function dashboard()
    {
        $dashboard = new \\CrudView\\Dashboard\\Dashboard(__('Site Administration'), 1);
        $dashboard->addToColumn(new \\CrudView\\Dashboard\\Module\\LinkListModule('Title', [
            new ActionLinkItem('Posts', ['controller' => 'Posts'], [], [
                LinkItem('Add', ['controller' => 'Posts', 'action' => 'add']),
            ]),
        ]));

        $this->Crud->action()->setConfig('scaffold.dashboard', $dashboard);
        return $this->Crud->execute();
    }

.. include:: /_partials/pages/form/viewblocks.rst
.. include:: /_partials/pages/form/elements.rst

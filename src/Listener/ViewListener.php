<?php
namespace CrudView\Listener;

use Cake\Collection\Collection;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Utility\Hash;
use Cake\Utility\Inflector;
use CrudView\Listener\Traits\FormTypeTrait;
use CrudView\Listener\Traits\IndexTypeTrait;
use CrudView\Listener\Traits\SidebarNavigationTrait;
use CrudView\Listener\Traits\SiteTitleTrait;
use CrudView\Listener\Traits\UtilityNavigationTrait;
use CrudView\Traits\CrudViewConfigTrait;
use Crud\Listener\BaseListener;

class ViewListener extends BaseListener
{
    use CrudViewConfigTrait;
    use FormTypeTrait;
    use IndexTypeTrait;
    use SidebarNavigationTrait;
    use SiteTitleTrait;
    use UtilityNavigationTrait;

    /**
     * Default associations config
     *
     * @var array
     */
    protected $associations = null;

    /**
     * [beforeFind description]
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function beforeFind(Event $event)
    {
        $related = $this->_getRelatedModels();
        if ($related === []) {
            $this->associations = [];
        } else {
            $this->associations = $this->_associations(array_keys($related));
        }

        if (!$event->getSubject()->query->contain()) {
            $event->getSubject()->query->contain($related);
        }
    }

    /**
     * [beforePaginate description]
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function beforePaginate(Event $event)
    {
        $related = $this->_getRelatedModels();
        if ($related === []) {
            $this->associations = [];
        } else {
            $this->associations = $this->_associations(array_keys($related));
        }

        if (!$event->getSubject()->query->contain()) {
            $event->getSubject()->query->contain($this->_getRelatedModels(['manyToOne', 'oneToOne']));
        }
    }

    /**
     * beforeRender event
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function beforeRender(Event $event)
    {
        if ($this->_controller()->name === 'Error') {
            return;
        }

        if (!empty($event->getSubject()->entity)) {
            $this->_entity = $event->getSubject()->entity;
        }

        if ($this->associations === null) {
            $this->associations = $this->_associations(array_keys($this->_getRelatedModels()));
        }

        $this->ensureConfig();

        $this->beforeRenderFormType();
        $this->beforeRenderIndexType();
        $this->beforeRenderSiteTitle();
        $this->beforeRenderUtilityNavigation();
        $this->beforeRenderSidebarNavigation();

        $controller = $this->_controller();
        $controller->set('actionConfig', $this->_action()->getConfig());
        $controller->set('title', $this->_getPageTitle());
        $controller->set('breadcrumbs', $this->_getBreadcrumbs());

        $associations = $this->associations;
        $controller->set(compact('associations'));

        $fields = $this->_scaffoldFields($associations);
        $controller->set('fields', $fields);
        $controller->set('formTabGroups', $this->_getFormTabGroups($fields));

        $controller->set('blacklist', $this->_blacklist());
        $controller->set('actions', $this->_getControllerActions());
        $controller->set('bulkActions', $this->_getBulkActions());
        $controller->set('viewblocks', $this->_getViewBlocks());
        $controller->set('actionGroups', $this->_getActionGroups());
        $controller->set($this->_getPageVariables());
    }

    /**
     * Make sure flash messages are properly handled by BootstrapUI.FlashHelper
     *
     * @param \Cake\Event\Event $event Event.
     * @return void
     */
    public function setFlash(Event $event)
    {
        unset($event->getSubject()->params['class']);
        $event->getSubject()->element = ltrim($event->getSubject()->type);
    }

    /**
     * Returns the sites title to show on scaffolded view
     *
     * @return string
     */
    protected function _getPageTitle()
    {
        $action = $this->_action();

        $title = $action->getConfig('scaffold.page_title');
        if (!empty($title)) {
            return $title;
        }

        $scope = $action->getConfig('scope');

        $request = $this->_request();
        $actionName = Inflector::humanize(Inflector::underscore($request->action));
        $controllerName = $this->_controllerName();

        if ($scope === 'table') {
            if ($actionName === 'Index') {
                return $controllerName;
            }

            return sprintf('%s %s', $controllerName, $actionName);
        }

        $primaryKeyValue = $this->_primaryKeyValue();
        if ($primaryKeyValue === null) {
            return sprintf('%s %s', $actionName, $controllerName);
        }

        $displayFieldValue = $this->_displayFieldValue();
        if ($displayFieldValue === null || $this->_table()->getDisplayField() == $this->_table()->getPrimaryKey()) {
            return sprintf('%s %s #%s', $actionName, $controllerName, $primaryKeyValue);
        }

        return sprintf('%s %s #%s: %s', $actionName, $controllerName, $primaryKeyValue, $displayFieldValue);
    }

    /**
     * Get breadcrumns.
     *
     * @return array
     */
    protected function _getBreadcrumbs()
    {
        $action = $this->_action();

        return $action->getConfig('scaffold.breadcrumbs') ?: [];
    }

    /**
     * Get a list of relevant models to contain using Containable
     *
     * If the user hasn't configured any relations for an action
     * we assume all relations will be used.
     *
     * The user can choose to suppress specific relations using the blacklist
     * functionality.
     *
     * @param string[] $associationTypes List of association types.
     * @return array
     */
    protected function _getRelatedModels($associationTypes = [])
    {
        $models = $this->_action()->getConfig('scaffold.relations');

        if ($models === false) {
            return [];
        }

        if (empty($models)) {
            $associations = [];
            if (empty($associationTypes)) {
                $associations = $this->_table()->associations();
            } else {
                foreach ($associationTypes as $assocType) {
                    $associations = array_merge(
                        $associations,
                        $this->_table()->associations()->type($assocType)
                    );
                }
            }

            $models = [];
            foreach ($associations as $assoc) {
                $models[] = $assoc->name();
            }
        }

        $models = Hash::normalize($models);

        $blacklist = $this->_action()->getConfig('scaffold.relations_blacklist');
        if (!empty($blacklist)) {
            $blacklist = Hash::normalize($blacklist);
            $models = array_diff_key($models, $blacklist);
        }

        foreach ($models as $key => $value) {
            if (!is_array($value)) {
                $models[$key] = [];
            }
        }

        return $models;
    }

    /**
     * Get list of blacklisted fields from config.
     *
     * @return array
     */
    protected function _blacklist()
    {
        return (array)$this->_action()->getConfig('scaffold.fields_blacklist');
    }

    /**
     * Publish fairly static variables needed in the view
     *
     * @return array
     */
    protected function _getPageVariables()
    {
        $table = $this->_table();
        $controller = $this->_controller();
        $scope = $this->_action()->getConfig('scope');

        $data = [
            'modelClass' => $controller->modelClass,
            'modelSchema' => $table->getSchema(),
            'displayField' => $table->getDisplayField(),
            'singularHumanName' => Inflector::humanize(Inflector::underscore(Inflector::singularize($controller->modelClass))),
            'pluralHumanName' => Inflector::humanize(Inflector::underscore($controller->name)),
            'singularVar' => Inflector::singularize($controller->name),
            'pluralVar' => Inflector::variable($controller->name),
            'primaryKey' => $table->getPrimaryKey(),
        ];

        if ($scope === 'entity') {
            $data += [
                'primaryKeyValue' => $this->_primaryKeyValue()
            ];
        }

        return $data;
    }

    /**
     * Returns fields to be displayed on scaffolded template
     *
     * @param array $associations Associations list.
     * @return array
     */
    protected function _scaffoldFields(array $associations = [])
    {
        $action = $this->_action();
        $scaffoldFields = (array)$action->getConfig('scaffold.fields');
        if (!empty($scaffoldFields)) {
            $scaffoldFields = Hash::normalize($scaffoldFields);
        }

        if (empty($scaffoldFields) || $action->getConfig('scaffold.autoFields')) {
            $cols = $this->_table()->getSchema()->columns();
            $cols = Hash::normalize($cols);

            $scope = $action->getConfig('scope');
            if ($scope === 'entity' && !empty($associations['manyToMany'])) {
                foreach ($associations['manyToMany'] as $alias => $options) {
                    $cols[sprintf('%s._ids', $options['entities'])] = [
                        'multiple' => true
                    ];
                }
            }

            $scaffoldFields = array_merge($cols, $scaffoldFields);
        }

        // Check for blacklisted fields
        $blacklist = $action->getConfig('scaffold.fields_blacklist');
        if (!empty($blacklist)) {
            $scaffoldFields = array_diff_key($scaffoldFields, array_combine($blacklist, $blacklist));
        }

        // Make sure all array values are an array
        foreach ($scaffoldFields as $field => $options) {
            if (!is_array($options)) {
                $scaffoldFields[$field] = (array)$options;
            }

            $scaffoldFields[$field] += ['formatter' => null];
        }

        $fieldSettings = $action->getConfig('scaffold.field_settings');
        if (empty($fieldSettings)) {
            $fieldSettings = [];
        }
        $fieldSettings = array_intersect_key($fieldSettings, $scaffoldFields);
        $scaffoldFields = Hash::merge($scaffoldFields, $fieldSettings);

        return $scaffoldFields;
    }

    /**
     * Get the controller name based on the Crud Action scope
     *
     * @return string
     */
    protected function _controllerName()
    {
        $inflections = [
            'underscore',
            'humanize',
        ];

        if ($this->_action()->scope() === 'entity') {
            $inflections[] = 'singularize';
        }

        $baseName = $this->_controller()->name;
        foreach ($inflections as $inflection) {
            $baseName = Inflector::$inflection($baseName);
        }

        return $baseName;
    }

    /**
     * Returns groupings of action types on the scaffolded view
     * Includes derived actions from scaffold.action_groups
     *
     * @return array
     */
    protected function _getControllerActions()
    {
        $table = $entity = [];

        $actions = $this->_getAllowedActions();
        foreach ($actions as $actionName => $config) {
            list($scope, $actionConfig) = $this->_getControllerActionConfiguration($actionName, $config);
            ${$scope}[$actionName] = $actionConfig;
        }

        $actionBlacklist = [];
        $groups = $this->_action()->getConfig('scaffold.action_groups') ?: [];
        foreach ($groups as $group) {
            $group = Hash::normalize($group);
            foreach ($group as $actionName => $config) {
                if (isset($table[$actionName]) || isset($entity[$actionName])) {
                    continue;
                }
                if ($config === null) {
                    $config = [];
                }
                list($scope, $actionConfig) = $this->_getControllerActionConfiguration($actionName, $config);
                $realAction = Hash::get($actionConfig, 'url.action', $actionName);
                if (!isset(${$scope}[$realAction])) {
                    continue;
                }

                $actionBlacklist[] = $realAction;
                ${$scope}[$actionName] = $actionConfig;
            }
        }

        foreach ($actionBlacklist as $actionName) {
            unset($table[$actionName]);
            unset($entity[$actionName]);
        }

        return compact('table', 'entity');
    }

    /**
     * Returns url action configuration for a given action.
     *
     * This is used to figure out how a given action should be linked to.
     *
     * @param string $actionName Action name.
     * @param array $config Config array.
     * @return array
     */
    protected function _getControllerActionConfiguration($actionName, $config)
    {
        $realAction = Hash::get($config, 'url.action', $actionName);
        if ($this->_crud()->isActionMapped($realAction)) {
            $action = $this->_action($realAction);
            $class = get_class($action);
            $class = substr($class, strrpos($class, '\\') + 1);

            if ($class === 'DeleteAction') {
                $config += ['method' => 'DELETE'];
            }

            if (!isset($config['scope'])) {
                $config['scope'] = $class === 'AddAction' ? 'table' : $action->scope();
            }
        }

        // apply defaults if necessary
        $scope = isset($config['scope']) ? $config['scope'] : 'entity';
        $method = isset($config['method']) ? $config['method'] : 'GET';

        $title = !empty($config['link_title']) ? $config['link_title'] : Inflector::humanize(Inflector::underscore($actionName));
        $url = ['action' => $realAction];
        if (isset($config['url'])) {
            $url = $config['url'] + $url;
        }

        $actionConfig = [
            'title' => $title,
            'url' => $url,
            'method' => $method,
            'options' => array_diff_key(
                $config,
                array_flip(['method', 'scope', 'link_title', 'url', 'scaffold', 'callback'])
            )
        ];
        if (!empty($config['callback'])) {
            $actionConfig['callback'] = $config['callback'];
        }

        return [$scope, $actionConfig];
    }

    /**
     * Returns a list of action configs that are allowed to be shown
     *
     * @return array
     */
    protected function _getAllowedActions()
    {
        $actions = $this->_action()->getConfig('scaffold.actions');
        if ($actions === null) {
            $actions = array_keys($this->_crud()->getConfig('actions'));
        }

        $extraActions = $this->_action()->getConfig('scaffold.extra_actions') ?: [];

        $allActions = array_merge(
            $this->_normalizeActions($actions),
            $this->_normalizeActions($extraActions)
        );

        $blacklist = (array)$this->_action()->getConfig('scaffold.actions_blacklist');
        $blacklist = array_combine($blacklist, $blacklist);
        foreach ($this->_crud()->getConfig('actions') as $action => $config) {
            if ($config['className'] === 'Crud.Lookup') {
                $blacklist[$action] = $action;
            }
        }

        return array_diff_key($allActions, $blacklist);
    }

    /**
     * Convert mixed action configs to unified structure
     *
     * [
     *   'ACTION_1' => [..config...],
     *   'ACTION_2' => [..config...],
     *   'ACTION_N' => [..config...]
     * ]
     *
     * @param array $actions Actions
     * @return array
     */
    protected function _normalizeActions($actions)
    {
        $normalized = [];
        foreach ($actions as $key => $config) {
            if (is_array($config)) {
                $normalized[$key] = $config;
            } else {
                $normalized[$config] = [];
            }
        }

        return $normalized;
    }

    /**
     * Returns associations for controllers models.
     *
     * @param array $whitelist Whitelist of associations to return.
     * @return array Associations for model
     */
    protected function _associations(array $whitelist = [])
    {
        $table = $this->_table();

        $associationConfiguration = [];

        $associations = $table->associations();

        $keys = $associations->keys();
        if (!empty($whitelist)) {
            $keys = array_intersect($keys, array_map('strtolower', $whitelist));
        }
        foreach ($keys as $associationName) {
            $association = $associations->get($associationName);
            $type = $association->type();

            if (!isset($associationConfiguration[$type])) {
                $associationConfiguration[$type] = [];
            }

            $assocKey = $association->name();
            $associationConfiguration[$type][$assocKey]['model'] = $assocKey;
            $associationConfiguration[$type][$assocKey]['type'] = $type;
            $associationConfiguration[$type][$assocKey]['primaryKey'] = $association->target()->getPrimaryKey();
            $associationConfiguration[$type][$assocKey]['displayField'] = $association->target()->getDisplayField();
            $associationConfiguration[$type][$assocKey]['foreignKey'] = $association->foreignKey();
            $associationConfiguration[$type][$assocKey]['propertyName'] = $association->property();
            $associationConfiguration[$type][$assocKey]['plugin'] = pluginSplit($association->className())[0];
            $associationConfiguration[$type][$assocKey]['controller'] = $assocKey;
            $associationConfiguration[$type][$assocKey]['entity'] = Inflector::singularize(Inflector::underscore($assocKey));
            $associationConfiguration[$type][$assocKey]['entities'] = Inflector::underscore($assocKey);

            $associationConfiguration[$type][$assocKey] = array_merge($associationConfiguration[$type][$assocKey], $this->_action()->getConfig('association.' . $assocKey) ?: []);
        }

        return $associationConfiguration;
    }

    /**
     * Derive the Model::primaryKey value from the current context
     *
     * If no value can be found, NULL is returned
     *
     * @return mixed
     */
    protected function _primaryKeyValue()
    {
        return $this->_deriveFieldFromContext($this->_table()->getPrimaryKey());
    }

    /**
     * Derive the Model::displayField value from the current context
     *
     * If no value can be found, NULL is returned
     *
     * @return string|null
     */
    protected function _displayFieldValue()
    {
        return $this->_deriveFieldFromContext($this->_table()->getDisplayField());
    }

    /**
     * Extract a field value from a either ServerRequest::getData()
     * or Controller::$viewVars for the current model + the supplied field
     *
     * @param string $field Name of field.
     * @return mixed
     */
    protected function _deriveFieldFromContext($field)
    {
        $controller = $this->_controller();
        $entity = $this->_entity();
        $request = $this->_request();
        $value = $entity->get($field);

        if ($value) {
            return $value;
        }

        $path = "{$controller->modelClass}.{$field}";
        if (!empty($request->getData())) {
            $value = Hash::get((array)$request->getData(), $path);
        }

        $singularVar = Inflector::variable($controller->modelClass);
        if (!empty($controller->viewVars[$singularVar])) {
            $value = $entity->get($field);
        }

        return $value;
    }

    /**
     * Get view blocks.
     *
     * @return array
     */
    protected function _getViewBlocks()
    {
        $action = $this->_action();

        return $action->getConfig('scaffold.viewblocks') ?: [];
    }

    /**
     * Get bulk actions blocks.
     *
     * @return array
     */
    protected function _getBulkActions()
    {
        $action = $this->_action();

        return $action->getConfig('scaffold.bulk_actions') ?: [];
    }

    /**
     * Get action groups
     *
     * @return array
     */
    protected function _getActionGroups()
    {
        $action = $this->_action();
        $groups = $action->getConfig('scaffold.action_groups') ?: [];

        $groupedActions = [];
        foreach ($groups as $group) {
            $groupedActions[] = array_keys(Hash::normalize($group));
        }

        // add "primary" actions (primary should rendered as separate buttons)
        $groupedActions = (new Collection($groupedActions))->unfold()->toList();
        $groups['primary'] = array_diff(array_keys($this->_getAllowedActions()), $groupedActions);

        return $groups;
    }

    /**
     * Get field tab groups
     *
     * @param array $fields Form fields.
     * @return array
     */
    protected function _getFormTabGroups(array $fields = [])
    {
        $action = $this->_action();
        $groups = $action->getConfig('scaffold.form_tab_groups');

        if (empty($groups)) {
            return [];
        }

        $groupedFields = (new Collection($groups))->unfold()->toList();
        $unGroupedFields = array_diff(array_keys($fields), $groupedFields);

        if ($unGroupedFields) {
            $primayGroup = $action->getConfig('scaffold.form_primary_tab') ?: __d('crud', 'Primary');

            $groups = [$primayGroup => $unGroupedFields] + $groups;
        }

        return $groups;
    }
}

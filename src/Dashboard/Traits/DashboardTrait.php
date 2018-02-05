<?php
namespace CrudView\Dashboard\Traits;

use InvalidArgumentException;

trait DashboardTrait
{
    /**
     * Holds all properties and their values for this entity
     *
     * @var array
     */
    protected $_properties = [];

    /**
     * List of valid fields
     *
     * @var array
     */
    protected $_valid = [];

    /**
     * Returns the value of a property by name
     *
     * @param string $property the name of the property to retrieve
     * @return mixed
     * @throws \InvalidArgumentException if an empty property name is passed
     */
    public function &get($property)
    {
        if (!strlen((string)$property)) {
            throw new InvalidArgumentException('Cannot get an empty property');
        }

        if (!isset($this->_valid[$property])) {
            throw new InvalidArgumentException('Cannot get invalid property');
        }

        $value = null;
        if (isset($this->_properties[$property])) {
            $value =& $this->_properties[$property];
        }

        $getter = '_get' . ucfirst($property);
        if (method_exists($this, $getter)) {
            $value = $this->{$setter}($value);
        }

        return $value;
    }

    /**
     * Checks if a property is valid for the instance
     *
     * @param string $property name of a property
     * @return bool
     */
    public function isValid($property)
    {
        return isset($this->_valid[$property]);
    }

    /**
     * Sets a single property inside this entity.
     *
     * ### Example:
     *
     * ```
     * $entity->set('name', 'Andrew');
     * ```
     *
     * It is also possible to mass-assign multiple properties to this entity
     * with one call by passing a hashed array as properties in the form of
     * property => value pairs
     *
     * ### Example:
     *
     * ```
     * $entity->set(['name' => 'andrew', 'id' => 1]);
     * echo $entity->name // prints andrew
     * echo $entity->id // prints 1
     * ```
     *
     * @param string|array $property the name of property to set or a list of
     * properties with their respective values
     * @param mixed $value The value to set to the property
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function set($property, $value = null)
    {
        if (is_string($property) && $property !== '') {
            $property = [$property => $value];
        }

        if (!is_array($property)) {
            throw new InvalidArgumentException('Cannot set an empty property');
        }

        foreach ($property as $p => $value) {
            if (empty($this->_valid[$p])) {
                throw new InvalidArgumentException('Cannot set invalid property');
            }

            $setter = '_set' . ucfirst($p);
            if (method_exists($this, $setter)) {
                $value = $this->{$setter}($value);
            }
            $this->_properties[$p] = $value;
        }

        return $this;
    }

    /**
     * Marks a property as valid for the instance
     *
     * @param string $property name of a property
     * @return $this
     * @throws \InvalidArgumentException
     */
    protected function setValid($property)
    {
        if (is_string($property) && $property !== '') {
            $property = (array)$property;
        }

        if (!is_array($property)) {
            throw new InvalidArgumentException('Cannot set an empty property');
        }

        foreach ($property as $p) {
            $this->_valid[$p] = true;
        }

        return $this;
    }

    /**
     * Get the printable version of this object.
     *
     * @return array
     */
    public function __debugInfo()
    {
        return [
            '_properties' => $this->_properties,
            '_valid' => $this->_valid,
        ];
    }
}
